<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function npl_giveaway_score_match( $match_id ) {
	global $wpdb;
	$t     = npl_giveaway_tables();
	$match = npl_giveaway_get_match( $match_id );
	if ( ! $match ) {
		return 0;
	}
	$preds = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['predictions']} WHERE match_id = %d", $match_id ) );
	foreach ( $preds as $pr ) {
		$points = 0;
		if ( $match->winner !== '' && $match->winner !== 'No Result' ) {
			if ( $pr->pick === $match->winner ) {
				$points += NPL_POINTS_WINNER;
				if ( $match->margin_type !== '' && $pr->margin_type === $match->margin_type && (int) $pr->margin_value === (int) $match->margin_value ) {
					$points += NPL_POINTS_MARGIN;
				}
			}
		}
		$wpdb->update( $t['predictions'], array( 'points' => $points ), array( 'id' => $pr->id ), array( '%d' ), array( '%d' ) );
	}
	return count( $preds );
}

function npl_giveaway_score_all() {
	foreach ( npl_giveaway_get_matches() as $m ) {
		npl_giveaway_score_match( $m->id );
	}
}
