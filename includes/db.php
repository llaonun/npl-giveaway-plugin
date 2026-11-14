<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function npl_giveaway_tables() {
	global $wpdb;
	return array(
		'participants' => $wpdb->prefix . 'npl_participants',
		'matches'      => $wpdb->prefix . 'npl_matches',
		'predictions'  => $wpdb->prefix . 'npl_predictions',
	);
}

function npl_giveaway_create_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t       = npl_giveaway_tables();
	$charset = $wpdb->get_charset_collate();

	$sql1 = "CREATE TABLE {$t['participants']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(120) NOT NULL,
		email varchar(150) NOT NULL,
		phone varchar(20) NOT NULL,
		pay_method varchar(20) NOT NULL,
		pay_detail varchar(150) NOT NULL,
		social_username varchar(100) NOT NULL DEFAULT '',
		screenshot_url varchar(255) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY email (email)
	) $charset;";

	$sql2 = "CREATE TABLE {$t['matches']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		match_no int NOT NULL,
		label varchar(60) NOT NULL,
		team_a varchar(80) NOT NULL,
		team_b varchar(80) NOT NULL,
		match_date datetime NOT NULL,
		venue varchar(150) NOT NULL,
		stage varchar(20) NOT NULL,
		winner varchar(80) DEFAULT '',
		margin_type varchar(10) DEFAULT '',
		margin_value int DEFAULT 0,
		updated_at datetime NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY match_no (match_no)
	) $charset;";

	$sql3 = "CREATE TABLE {$t['predictions']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		participant_id bigint(20) unsigned NOT NULL,
		match_id bigint(20) unsigned NOT NULL,
		pick varchar(80) NOT NULL,
		margin_type varchar(10) NOT NULL,
		margin_value int NOT NULL,
		points int NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY participant_match (participant_id, match_id)
	) $charset;";

	dbDelta( $sql1 );
	dbDelta( $sql2 );
	dbDelta( $sql3 );
}

function npl_giveaway_seed_matches() {
	global $wpdb;
	$t     = npl_giveaway_tables();
	$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['matches']}" );
	if ( $count > 0 ) {
		return;
	}
	foreach ( npl_giveaway_match_data() as $m ) {
		$wpdb->insert( $t['matches'], array(
			'match_no'   => $m['no'],
			'label'      => $m['label'],
			'team_a'     => $m['a'],
			'team_b'     => $m['b'],
			'match_date' => $m['date'],
			'venue'      => 'Tribhuvan University International Cricket Ground, Kirtipur',
			'stage'      => $m['stage'],
			'updated_at' => current_time( 'mysql' ),
		) );
	}
}

function npl_giveaway_get_matches() {
	global $wpdb;
	$t = npl_giveaway_tables();
	return $wpdb->get_results( "SELECT * FROM {$t['matches']} ORDER BY match_date ASC, match_no ASC" );
}

function npl_giveaway_get_match( $id ) {
	global $wpdb;
	$t = npl_giveaway_tables();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['matches']} WHERE id = %d", $id ) );
}

function npl_giveaway_match_timestamp( $match ) {
	$dt = new DateTime( $match->match_date, new DateTimeZone( NPL_TZ ) );
	return $dt->getTimestamp();
}

function npl_giveaway_match_is_open( $match ) {
	return time() < npl_giveaway_match_timestamp( $match );
}

function npl_giveaway_teams_known( $match ) {
	foreach ( array( $match->team_a, $match->team_b ) as $team ) {
		if ( stripos( $team, 'place' ) !== false || stripos( $team, 'winner of' ) !== false || stripos( $team, 'loser of' ) !== false ) {
			return false;
		}
	}
	return true;
}

function npl_giveaway_get_participant_by_email( $email ) {
	global $wpdb;
	$t = npl_giveaway_tables();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['participants']} WHERE email = %s", $email ) );
}

function npl_giveaway_get_prediction( $participant_id, $match_id ) {
	global $wpdb;
	$t = npl_giveaway_tables();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['predictions']} WHERE participant_id = %d AND match_id = %d", $participant_id, $match_id ) );
}

function npl_giveaway_get_leaderboard() {
	global $wpdb;
	$t = npl_giveaway_tables();
	return $wpdb->get_results(
		"SELECT p.id, p.name, p.email, COALESCE(SUM(pr.points), 0) AS points,
			COALESCE(SUM(CASE WHEN pr.points >= 10 THEN 1 ELSE 0 END), 0) AS correct,
			COUNT(pr.id) AS predictions
		FROM {$t['participants']} p
		LEFT JOIN {$t['predictions']} pr ON pr.participant_id = p.id
		GROUP BY p.id
		HAVING predictions > 0
		ORDER BY points DESC, correct DESC, p.name ASC"
	);
}

function npl_giveaway_get_next_match() {
	foreach ( npl_giveaway_get_matches() as $m ) {
		if ( npl_giveaway_match_is_open( $m ) && npl_giveaway_teams_known( $m ) ) {
			return $m;
		}
	}
	return null;
}

function npl_giveaway_format_match_time( $match ) {
	$dt = new DateTime( $match->match_date, new DateTimeZone( NPL_TZ ) );
	return $dt->format( 'D, j M Y · g:i A' );
}
