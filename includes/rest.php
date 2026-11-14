<?php
/**
 * NPL Giveaway — REST API (v1.0.3).
 *
 * Used by the automated results bot: it reads which matches still need a
 * result and posts the official result once a match has finished, which
 * auto-scores every prediction for that match. Admin-only (Application
 * Password for the site admin).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'npl_giveaway_register_rest' );

function npl_giveaway_register_rest() {
	register_rest_route( 'npl-giveaway/v1', '/matches', array(
		'methods'             => 'GET',
		'callback'            => 'npl_giveaway_rest_matches',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );
	register_rest_route( 'npl-giveaway/v1', '/result', array(
		'methods'             => 'POST',
		'callback'            => 'npl_giveaway_rest_result',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	) );
}

function npl_giveaway_rest_matches() {
	global $wpdb;
	$t    = npl_giveaway_tables();
	$rows = $wpdb->get_results( "SELECT * FROM {$t['matches']} ORDER BY match_no ASC" );
	$out  = array();
	foreach ( $rows as $m ) {
		$out[] = array(
			'match_no'           => (int) $m->match_no,
			'team_a'             => $m->team_a,
			'team_b'             => $m->team_b,
			'match_date'         => $m->match_date,
			'venue'              => $m->venue,
			'result_winner'      => $m->winner,
			'result_margin_type' => $m->margin_type,
			'result_margin'      => $m->margin_value ? (int) $m->margin_value : null,
			'has_result'         => ! empty( $m->winner ),
		);
	}
	return rest_ensure_response( $out );
}

function npl_giveaway_rest_result( WP_REST_Request $req ) {
	$p        = $req->get_json_params();
	$match_no = isset( $p['match_no'] ) ? (int) $p['match_no'] : 0;
	$winner   = isset( $p['winner'] ) ? sanitize_text_field( $p['winner'] ) : '';
	$mtype    = isset( $p['margin_type'] ) ? sanitize_text_field( $p['margin_type'] ) : '';
	$margin   = isset( $p['margin'] ) ? (int) $p['margin'] : 0;

	if ( ! $match_no || ! $winner || ! in_array( $mtype, array( 'runs', 'wickets' ), true ) || $margin < 1
		|| ( 'runs' === $mtype && $margin > 200 )
		|| ( 'wickets' === $mtype && $margin > 10 ) ) {
		return new WP_Error( 'npl_bad_input', 'Invalid result data.', array( 'status' => 400 ) );
	}

	global $wpdb;
	$t = npl_giveaway_tables();
	$m = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['matches']} WHERE match_no = %d", $match_no ) );
	if ( ! $m ) {
		return new WP_Error( 'npl_no_match', 'Match not found.', array( 'status' => 404 ) );
	}
	if ( $winner !== $m->team_a && $winner !== $m->team_b ) {
		return new WP_Error( 'npl_bad_winner', 'Winner must be one of the two teams.', array( 'status' => 400 ) );
	}

	$wpdb->update(
		$t['matches'],
		array(
			'winner'       => $winner,
			'margin_type'  => $mtype,
			'margin_value' => $margin,
		),
		array( 'id' => $m->id ),
		array( '%s', '%s', '%d' ),
		array( '%d' )
	);
	$scored = npl_giveaway_score_match( (int) $m->id );

	return rest_ensure_response( array(
		'ok'                 => true,
		'match_no'           => $match_no,
		'winner'             => $winner,
		'margin_type'        => $mtype,
		'margin'             => $margin,
		'predictions_scored' => $scored,
	) );
}
