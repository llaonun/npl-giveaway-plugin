<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'npl_giveaway_admin_menu' );
function npl_giveaway_admin_menu() {
	add_menu_page( 'NPL Giveaway', 'NPL Giveaway', 'manage_options', 'npl-giveaway', 'npl_giveaway_admin_matches_page', 'dashicons-trophy', 30 );
	add_submenu_page( 'npl-giveaway', 'Matches & Results', 'Matches & Results', 'manage_options', 'npl-giveaway', 'npl_giveaway_admin_matches_page' );
	add_submenu_page( 'npl-giveaway', 'Participants', 'Participants', 'manage_options', 'npl-giveaway-participants', 'npl_giveaway_admin_participants_page' );
	add_submenu_page( 'npl-giveaway', 'Predictions', 'Predictions', 'manage_options', 'npl-giveaway-predictions', 'npl_giveaway_admin_predictions_page' );
}

function npl_giveaway_admin_matches_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	global $wpdb;
	$t = npl_giveaway_tables();

	if ( isset( $_POST['npl_admin_save'] ) && check_admin_referer( 'npl_admin_matches' ) ) {
		$ids = isset( $_POST['winner'] ) && is_array( $_POST['winner'] ) ? array_map( 'intval', array_keys( $_POST['winner'] ) ) : array();
		foreach ( $ids as $match_id ) {
			$match = npl_giveaway_get_match( $match_id );
			if ( ! $match ) {
				continue;
			}
			$team_a       = sanitize_text_field( wp_unslash( $_POST['team_a'][ $match_id ] ?? $match->team_a ) );
			$team_b       = sanitize_text_field( wp_unslash( $_POST['team_b'][ $match_id ] ?? $match->team_b ) );
			$winner       = sanitize_text_field( wp_unslash( $_POST['winner'][ $match_id ] ?? '' ) );
			$margin_type  = sanitize_text_field( wp_unslash( $_POST['margin_type'][ $match_id ] ?? '' ) );
			$margin_value = (int) ( $_POST['margin_value'][ $match_id ] ?? 0 );
			if ( $team_a === '' ) {
				$team_a = $match->team_a;
			}
			if ( $team_b === '' ) {
				$team_b = $match->team_b;
			}
			if ( $winner !== '' && $winner !== 'No Result' && $winner !== $team_a && $winner !== $team_b ) {
				$winner = '';
			}
			if ( $margin_type !== 'runs' && $margin_type !== 'wickets' ) {
				$margin_type = '';
			}
			if ( $margin_type === '' ) {
				$margin_value = 0;
			}
			$wpdb->update( $t['matches'], array(
				'team_a'       => $team_a,
				'team_b'       => $team_b,
				'winner'       => $winner,
				'margin_type'  => $margin_type,
				'margin_value' => $margin_value,
				'updated_at'   => current_time( 'mysql' ),
			), array( 'id' => $match_id ) );
			npl_giveaway_score_match( $match_id );
		}
		echo '<div class="notice notice-success"><p>Results saved and points recalculated.</p></div>';
	}
	if ( isset( $_POST['npl_admin_recalc'] ) && check_admin_referer( 'npl_admin_matches' ) ) {
		npl_giveaway_score_all();
		echo '<div class="notice notice-success"><p>All points recalculated.</p></div>';
	}

	$matches = npl_giveaway_get_matches();
	echo '<div class="wrap"><h1>NPL Giveaway — Matches &amp; Results</h1>';
	echo '<p>Enter the result after each match and save — points are calculated automatically (10 for the correct winner, 5 bonus for the correct margin). For playoff matches, set the two teams here once they are known after the league stage ends on 15 November.</p>';
	echo '<form method="post" style="margin-bottom:12px">' . wp_nonce_field( 'npl_admin_matches', '_wpnonce', true, false ) . '<button type="submit" name="npl_admin_recalc" class="button">Recalculate all points</button></form>';
	echo '<form method="post">' . wp_nonce_field( 'npl_admin_matches', '_wpnonce', true, false );
	echo '<table class="widefat fixed striped"><thead><tr><th style="width:110px">Match</th><th>Teams (edit for playoffs)</th><th style="width:170px">Date (NPT)</th><th style="width:190px">Winner</th><th style="width:170px">Margin</th></tr></thead><tbody>';
	foreach ( $matches as $m ) {
		$open = npl_giveaway_match_is_open( $m );
		echo '<tr>'
			. '<td>' . esc_html( $m->label ) . '<br><span style="color:#666">' . ( $open ? 'Predictions open' : 'Closed' ) . '</span></td>'
			. '<td><input type="text" name="team_a[' . (int) $m->id . ']" value="' . esc_attr( $m->team_a ) . '" size="18"> vs '
			. '<input type="text" name="team_b[' . (int) $m->id . ']" value="' . esc_attr( $m->team_b ) . '" size="18"></td>'
			. '<td>' . esc_html( npl_giveaway_format_match_time( $m ) ) . '</td>'
			. '<td><select name="winner[' . (int) $m->id . ']"><option value="">— not played —</option>'
			. '<option value="' . esc_attr( $m->team_a ) . '"' . selected( $m->winner, $m->team_a, false ) . '>' . esc_html( $m->team_a ) . '</option>'
			. '<option value="' . esc_attr( $m->team_b ) . '"' . selected( $m->winner, $m->team_b, false ) . '>' . esc_html( $m->team_b ) . '</option>'
			. '<option value="No Result"' . selected( $m->winner, 'No Result', false ) . '>No Result</option></select></td>'
			. '<td><select name="margin_type[' . (int) $m->id . ']"><option value="">—</option><option value="runs"' . selected( $m->margin_type, 'runs', false ) . '>runs</option><option value="wickets"' . selected( $m->margin_type, 'wickets', false ) . '>wickets</option></select> '
			. '<input type="number" name="margin_value[' . (int) $m->id . ']" min="0" max="200" value="' . (int) $m->margin_value . '" style="width:70px"></td>'
			. '</tr>';
	}
	echo '</tbody></table>';
	echo '<p><button type="submit" name="npl_admin_save" class="button button-primary">Save all results</button></p>';
	echo '</form></div>';
}

function npl_giveaway_admin_participants_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	global $wpdb;
	$t = npl_giveaway_tables();

	if ( isset( $_GET['npl_delete_participant'] ) && check_admin_referer( 'npl_delete_participant_' . (int) $_GET['npl_delete_participant'] ) ) {
		$del_id = (int) $_GET['npl_delete_participant'];
		$wpdb->delete( $t['predictions'], array( 'participant_id' => $del_id ), array( '%d' ) );
		$wpdb->delete( $t['participants'], array( 'id' => $del_id ), array( '%d' ) );
		echo '<div class="notice notice-success"><p>Participant and their predictions deleted.</p></div>';
	}

	$rows = $wpdb->get_results( "SELECT p.*, COALESCE(SUM(pr.points),0) AS points, COUNT(pr.id) AS npred FROM {$t['participants']} p LEFT JOIN {$t['predictions']} pr ON pr.participant_id = p.id GROUP BY p.id ORDER BY p.created_at DESC" );
	echo '<div class="wrap"><h1>NPL Giveaway — Participants</h1>';
	if ( empty( $rows ) ) {
		echo '<p>No signups yet.</p></div>';
		return;
	}
	echo '<table class="widefat fixed striped"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Username</th><th>Screenshot</th><th>Payment</th><th>Account detail</th><th>Points</th><th>Predictions</th><th>Joined</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$del_url = wp_nonce_url( admin_url( 'admin.php?page=npl-giveaway-participants&npl_delete_participant=' . (int) $r->id ), 'npl_delete_participant_' . (int) $r->id );
		$shot    = ! empty( $r->screenshot_url ) ? '<a href="' . esc_url( $r->screenshot_url ) . '" target="_blank">View</a>' : '—';
		echo '<tr><td>' . esc_html( $r->name ) . '</td><td>' . esc_html( $r->email ) . '</td><td>' . esc_html( $r->phone ) . '</td><td>' . esc_html( $r->social_username ) . '</td><td>' . $shot . '</td><td>' . esc_html( ucfirst( $r->pay_method ) ) . '</td><td>' . esc_html( $r->pay_detail ) . '</td><td>' . (int) $r->points . '</td><td>' . (int) $r->npred . '</td><td>' . esc_html( $r->created_at ) . '</td><td><a href="' . esc_url( $del_url ) . '" onclick="return confirm(\'Delete this participant and all their predictions? This cannot be undone.\')">Delete</a></td></tr>';
	}
	echo '</tbody></table></div>';
}

function npl_giveaway_admin_predictions_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	global $wpdb;
	$t       = npl_giveaway_tables();
	$filter  = isset( $_GET['match_id'] ) ? (int) $_GET['match_id'] : 0;
	$matches = npl_giveaway_get_matches();
	echo '<div class="wrap"><h1>NPL Giveaway — Predictions</h1>';
	echo '<form method="get"><input type="hidden" name="page" value="npl-giveaway-predictions"><select name="match_id"><option value="0">All matches</option>';
	foreach ( $matches as $m ) {
		echo '<option value="' . (int) $m->id . '"' . selected( $filter, $m->id, false ) . '>' . esc_html( $m->label ) . ' — ' . esc_html( $m->team_a ) . ' vs ' . esc_html( $m->team_b ) . '</option>';
	}
	echo '</select> <button class="button">Filter</button></form>';
	$where = $filter ? $wpdb->prepare( 'WHERE pr.match_id = %d', $filter ) : '';
	$rows  = $wpdb->get_results( "SELECT pr.*, p.name, p.email, m.label, m.team_a, m.team_b FROM {$t['predictions']} pr JOIN {$t['participants']} p ON p.id = pr.participant_id JOIN {$t['matches']} m ON m.id = pr.match_id $where ORDER BY pr.created_at DESC LIMIT 500" );
	if ( empty( $rows ) ) {
		echo '<p>No predictions yet.</p></div>';
		return;
	}
	echo '<table class="widefat fixed striped"><thead><tr><th>Participant</th><th>Match</th><th>Pick</th><th>Margin</th><th>Points</th><th>When</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr><td>' . esc_html( $r->name ) . '<br><span style="color:#666">' . esc_html( $r->email ) . '</span></td><td>' . esc_html( $r->label ) . '<br>' . esc_html( $r->team_a ) . ' vs ' . esc_html( $r->team_b ) . '</td><td>' . esc_html( $r->pick ) . '</td><td>' . esc_html( $r->margin_value ) . ' ' . esc_html( $r->margin_type ) . '</td><td>' . (int) $r->points . '</td><td>' . esc_html( $r->created_at ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}
