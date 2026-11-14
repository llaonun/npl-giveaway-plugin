<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['npl_notice'] = '';

function npl_giveaway_notice( $msg, $type = 'success' ) {
	$GLOBALS['npl_notice'] = '<div class="npl-notice npl-' . esc_attr( $type ) . '">' . $msg . '</div>';
}

add_action( 'init', 'npl_giveaway_handle_forms' );
function npl_giveaway_handle_forms() {
	if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
		return;
	}

	// --- Signup ---
	if ( isset( $_POST['npl_signup_submit'] ) ) {
		if ( ! isset( $_POST['npl_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['npl_signup_nonce'] ) ), 'npl_signup' ) ) {
			npl_giveaway_notice( 'Security check failed. Please try again.', 'error' );
			return;
		}
		$name       = sanitize_text_field( wp_unslash( $_POST['npl_name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['npl_email'] ?? '' ) );
		$phone      = preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_POST['npl_phone'] ?? '' ) );
		$pay_method = sanitize_text_field( wp_unslash( $_POST['npl_pay_method'] ?? '' ) );
		$pay_detail = sanitize_text_field( wp_unslash( $_POST['npl_pay_detail'] ?? '' ) );
		$social_username = sanitize_text_field( wp_unslash( $_POST['npl_social_username'] ?? '' ) );

		if ( strlen( $name ) < 2 ) {
			npl_giveaway_notice( 'Please enter your full name.', 'error' );
			return;
		}
		if ( ! is_email( $email ) ) {
			npl_giveaway_notice( 'Please enter a valid email address.', 'error' );
			return;
		}
		if ( ! preg_match( '/^9[0-9]{9}$/', $phone ) ) {
			npl_giveaway_notice( 'Please enter a valid 10-digit Nepali mobile number starting with 9.', 'error' );
			return;
		}
		if ( ! in_array( $pay_method, array( 'esewa', 'khalti', 'bank' ), true ) ) {
			npl_giveaway_notice( 'Please choose a payment option.', 'error' );
			return;
		}
		if ( $pay_detail === '' ) {
			npl_giveaway_notice( 'Please enter your payment account detail (eSewa ID, Khalti ID or bank account number).', 'error' );
			return;
		}
		if ( strlen( $social_username ) < 2 ) {
			npl_giveaway_notice( 'Please enter your Instagram / Facebook username.', 'error' );
			return;
		}
		global $wpdb;
		$t        = npl_giveaway_tables();
		$existing = npl_giveaway_get_participant_by_email( $email );
		if ( $existing ) {
			setcookie( 'npl_participant', $email, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
			npl_giveaway_notice( 'This email is already registered. You are signed in as ' . esc_html( $existing->name ) . ' — <a href="' . esc_url( home_url( '/predict/' ) ) . '">go to Predict</a>.', 'success' );
			return;
		}
		// Follow screenshot: image only (JPG/PNG/WebP), uploaded to the media uploads dir.
		$screenshot_url = '';
		if ( empty( $_FILES['npl_screenshot'] ) || ! is_array( $_FILES['npl_screenshot'] ) || (int) $_FILES['npl_screenshot']['error'] !== UPLOAD_ERR_OK ) {
			npl_giveaway_notice( 'Please upload a screenshot showing you follow LekhaSetu on Instagram and Facebook.', 'error' );
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload = wp_handle_upload( $_FILES['npl_screenshot'], array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'webp'     => 'image/webp',
			),
		) );
		if ( isset( $upload['error'] ) || empty( $upload['url'] ) ) {
			npl_giveaway_notice( 'The screenshot could not be uploaded. Please use a JPG, PNG or WebP image under 5 MB.', 'error' );
			return;
		}
		$screenshot_url = $upload['url'];

		$ok = $wpdb->insert( $t['participants'], array(
			'name'            => $name,
			'email'           => $email,
			'phone'           => $phone,
			'pay_method'      => $pay_method,
			'pay_detail'      => $pay_detail,
			'social_username' => $social_username,
			'screenshot_url'  => $screenshot_url,
			'created_at'      => current_time( 'mysql' ),
		) );
		if ( $ok ) {
			setcookie( 'npl_participant', $email, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
			npl_giveaway_notice( 'Welcome, ' . esc_html( $name ) . '! You are in. <a href="' . esc_url( home_url( '/predict/' ) ) . '">Make your first prediction</a>.', 'success' );
		} else {
			npl_giveaway_notice( 'Something went wrong saving your signup. Please try again.', 'error' );
		}
		return;
	}

	// --- Prediction ---
	if ( isset( $_POST['npl_predict_submit'] ) ) {
		if ( ! isset( $_POST['npl_predict_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['npl_predict_nonce'] ) ), 'npl_predict' ) ) {
			npl_giveaway_notice( 'Security check failed. Please try again.', 'error' );
			return;
		}
		$email       = sanitize_email( wp_unslash( $_POST['npl_email'] ?? '' ) );
		$participant = npl_giveaway_get_participant_by_email( $email );
		if ( ! $participant ) {
			npl_giveaway_notice( 'This email is not registered. Please <a href="' . esc_url( home_url( '/sign-up/' ) ) . '">sign up</a> first.', 'error' );
			return;
		}
		$match_id = (int) ( $_POST['npl_match_id'] ?? 0 );
		$match    = npl_giveaway_get_match( $match_id );
		if ( ! $match ) {
			npl_giveaway_notice( 'Match not found.', 'error' );
			return;
		}
		if ( ! npl_giveaway_match_is_open( $match ) ) {
			npl_giveaway_notice( 'Predictions are closed for this match.', 'error' );
			return;
		}
		$pick = sanitize_text_field( wp_unslash( $_POST['npl_pick'] ?? '' ) );
		if ( $pick !== $match->team_a && $pick !== $match->team_b ) {
			npl_giveaway_notice( 'Please pick one of the two teams.', 'error' );
			return;
		}
		$margin_type  = sanitize_text_field( wp_unslash( $_POST['npl_margin_type'] ?? '' ) );
		$margin_value = (int) ( $_POST['npl_margin_value'] ?? 0 );
		if ( $margin_type !== 'runs' && $margin_type !== 'wickets' ) {
			npl_giveaway_notice( 'Please choose a margin type (runs or wickets).', 'error' );
			return;
		}
		if ( $margin_type === 'runs' && ( $margin_value < 1 || $margin_value > 200 ) ) {
			npl_giveaway_notice( 'For a runs margin, enter 1–200.', 'error' );
			return;
		}
		if ( $margin_type === 'wickets' && ( $margin_value < 1 || $margin_value > 10 ) ) {
			npl_giveaway_notice( 'For a wickets margin, enter 1–10.', 'error' );
			return;
		}

		global $wpdb;
		$t        = npl_giveaway_tables();
		$existing = npl_giveaway_get_prediction( $participant->id, $match_id );
		$data     = array(
			'participant_id' => $participant->id,
			'match_id'       => $match_id,
			'pick'           => $pick,
			'margin_type'    => $margin_type,
			'margin_value'   => $margin_value,
			'created_at'     => current_time( 'mysql' ),
		);
		if ( $existing ) {
			$wpdb->update( $t['predictions'], $data, array( 'id' => $existing->id ) );
			npl_giveaway_notice( 'Prediction updated: ' . esc_html( $pick ) . ' by ' . esc_html( $margin_value ) . ' ' . esc_html( $margin_type ) . '.', 'success' );
		} else {
			$wpdb->insert( $t['predictions'], $data );
			npl_giveaway_notice( 'Prediction saved: ' . esc_html( $pick ) . ' by ' . esc_html( $margin_value ) . ' ' . esc_html( $margin_type ) . '.', 'success' );
		}
		return;
	}
}

add_shortcode( 'npl_signup', 'npl_giveaway_signup_shortcode' );
function npl_giveaway_signup_shortcode() {
	$out   = $GLOBALS['npl_notice'];
	$email = isset( $_COOKIE['npl_participant'] ) ? sanitize_email( wp_unslash( $_COOKIE['npl_participant'] ) ) : '';
	$participant = $email ? npl_giveaway_get_participant_by_email( $email ) : null;
	if ( $participant ) {
		$out .= '<div class="npl-card"><h3>You are signed up, ' . esc_html( $participant->name ) . '.</h3>'
			. '<p>Email: ' . esc_html( $participant->email ) . ' · Phone: ' . esc_html( $participant->phone ) . ' · Payment: ' . esc_html( ucfirst( $participant->pay_method ) ) . ( ! empty( $participant->social_username ) ? ' · Username: ' . esc_html( $participant->social_username ) : '' ) . '</p>'
			. '<p><a class="btn btn-teal" href="' . esc_url( home_url( '/predict/' ) ) . '">Make a prediction</a> '
			. '<a class="btn btn-gold" href="' . esc_url( home_url( '/leaderboard/' ) ) . '">View leaderboard</a></p></div>';
		return $out;
	}
	$out .= '<form method="post" enctype="multipart/form-data" class="npl-form" action="' . esc_url( get_permalink() ) . '">'
		. wp_nonce_field( 'npl_signup', 'npl_signup_nonce', true, false )
		. '<div class="npl-field"><label for="npl_name">Full name</label><input id="npl_name" name="npl_name" type="text" required minlength="2" placeholder="Your full name"></div>'
		. '<div class="npl-field"><label for="npl_email">Email address</label><input id="npl_email" name="npl_email" type="email" required placeholder="you@example.com"></div>'
		. '<div class="npl-field"><label for="npl_phone">Phone number</label><input id="npl_phone" name="npl_phone" type="tel" required pattern="[0-9]{10}" placeholder="98XXXXXXXX"></div>'
		. '<div class="npl-field"><label for="npl_social_username">Instagram / Facebook username</label><input id="npl_social_username" name="npl_social_username" type="text" required minlength="2" placeholder="@yourusername"></div>'
		. '<div class="npl-field"><label for="npl_screenshot">Follow screenshot (JPG, PNG or WebP)</label><input id="npl_screenshot" name="npl_screenshot" type="file" accept="image/*" required><p class="npl-fine">Upload a screenshot showing you follow @lekhasetu on Instagram and Lekha Setu on Facebook.</p></div>'
		. '<div class="npl-field"><span class="npl-label">Preferred payment option (for the Rs. 1,000 prize)</span><div class="npl-radio-pills">'
		. '<label><input type="radio" name="npl_pay_method" value="esewa" required> eSewa</label>'
		. '<label><input type="radio" name="npl_pay_method" value="khalti"> Khalti</label>'
		. '<label><input type="radio" name="npl_pay_method" value="bank"> Bank</label>'
		. '</div></div>'
		. '<div class="npl-field"><label for="npl_pay_detail">Payment account detail</label><input id="npl_pay_detail" name="npl_pay_detail" type="text" required placeholder="Your eSewa ID / Khalti ID / bank account no."></div>'
		. '<button type="submit" name="npl_signup_submit" class="btn btn-gold">Sign up &amp; start predicting</button>'
		. '<p class="npl-fine">One entry per person. Your details are used only for this giveaway and for paying the winner.</p>'
		. '</form>';
	return $out;
}

add_shortcode( 'npl_matches', 'npl_giveaway_matches_shortcode' );
function npl_giveaway_matches_shortcode() {
	$matches = npl_giveaway_get_matches();
	if ( empty( $matches ) ) {
		return '<p>No matches scheduled yet.</p>';
	}
	$upcoming = array();
	$done     = array();
	foreach ( $matches as $m ) {
		if ( npl_giveaway_match_is_open( $m ) && $m->winner === '' ) {
			$upcoming[] = $m;
		} else {
			$done[] = $m;
		}
	}
	$out = '';
	if ( $upcoming ) {
		$out .= '<h2>Upcoming matches</h2><div class="npl-matches">';
		foreach ( $upcoming as $m ) {
			$out .= npl_giveaway_match_card( $m );
		}
		$out .= '</div>';
	}
	if ( $done ) {
		$out .= '<h2>Completed matches</h2><div class="npl-matches">';
		foreach ( array_reverse( $done ) as $m ) {
			$out .= npl_giveaway_match_card( $m );
		}
		$out .= '</div>';
	}
	return $out;
}

function npl_giveaway_match_card( $m ) {
	$open   = npl_giveaway_match_is_open( $m ) && $m->winner === '';
	$result = '';
	if ( $m->winner !== '' && $m->winner !== 'No Result' ) {
		$result = '<p class="npl-result">' . esc_html( $m->winner ) . ' won by ' . esc_html( $m->margin_value ) . ' ' . esc_html( $m->margin_type ) . '</p>';
	} elseif ( $m->winner === 'No Result' ) {
		$result = '<p class="npl-result">No result</p>';
	}
	$badge = $open ? '<span class="npl-badge npl-open">Predictions open</span>' : '<span class="npl-badge npl-closed">Closed</span>';
	$cta   = $open && npl_giveaway_teams_known( $m ) ? '<a class="btn btn-teal" href="' . esc_url( home_url( '/predict/' ) ) . '">Predict</a>' : '';
	return '<article class="npl-match-card">'
		. '<div class="npl-match-head"><span>' . esc_html( $m->label ) . '</span>' . $badge . '</div>'
		. '<h3>' . esc_html( $m->team_a ) . ' <span class="npl-vs">vs</span> ' . esc_html( $m->team_b ) . '</h3>'
		. '<p class="npl-meta">' . esc_html( npl_giveaway_format_match_time( $m ) ) . ' NPT · ' . esc_html( $m->venue ) . '</p>'
		. $result . $cta
		. '</article>';
}

add_shortcode( 'npl_predict', 'npl_giveaway_predict_shortcode' );
function npl_giveaway_predict_shortcode() {
	$out   = $GLOBALS['npl_notice'];
	$email = '';
	if ( isset( $_COOKIE['npl_participant'] ) ) {
		$email = sanitize_email( wp_unslash( $_COOKIE['npl_participant'] ) );
	}
	if ( isset( $_POST['npl_lookup'] ) && isset( $_POST['npl_lookup_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['npl_lookup_nonce'] ) ), 'npl_lookup' ) ) {
		$email = sanitize_email( wp_unslash( $_POST['npl_email'] ?? '' ) );
	}
	if ( isset( $_GET['email'] ) ) {
		$email = sanitize_email( wp_unslash( $_GET['email'] ) );
	}

	if ( $email === '' ) {
		$out .= '<p>Enter the email you signed up with to see your predictions.</p>'
			. '<form method="post" class="npl-form npl-inline" action="' . esc_url( get_permalink() ) . '">'
			. wp_nonce_field( 'npl_lookup', 'npl_lookup_nonce', true, false )
			. '<div class="npl-field"><label for="npl_email">Registered email</label><input id="npl_email" name="npl_email" type="email" required placeholder="you@example.com"></div>'
			. '<button type="submit" name="npl_lookup" class="btn btn-teal">Find my predictions</button></form>'
			. '<p>Not signed up yet? <a href="' . esc_url( home_url( '/sign-up/' ) ) . '">Sign up here</a>.</p>';
		return $out;
	}
	$participant = npl_giveaway_get_participant_by_email( $email );
	if ( ! $participant ) {
		$out .= '<div class="npl-notice npl-error">No signup found for ' . esc_html( $email ) . '. Please <a href="' . esc_url( home_url( '/sign-up/' ) ) . '">sign up</a> first.</div>';
		return $out;
	}

	global $wpdb;
	$t      = npl_giveaway_tables();
	$totals = $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(points),0) AS points, COUNT(*) AS n FROM {$t['predictions']} WHERE participant_id = %d", $participant->id ) );
	$out   .= '<div class="npl-card"><h3>Predicting as ' . esc_html( $participant->name ) . '</h3><p>Points so far: <strong>' . (int) $totals->points . '</strong> · Predictions made: ' . (int) $totals->n . ' · <a href="' . esc_url( home_url( '/leaderboard/' ) ) . '">Leaderboard</a></p></div>';

	$matches  = npl_giveaway_get_matches();
	$upcoming = array();
	$past     = array();
	foreach ( $matches as $m ) {
		if ( npl_giveaway_match_is_open( $m ) && $m->winner === '' ) {
			$upcoming[] = $m;
		} else {
			$past[] = $m;
		}
	}
	if ( $upcoming ) {
		$out .= '<h2>Open for predictions</h2>';
		foreach ( $upcoming as $m ) {
			if ( ! npl_giveaway_teams_known( $m ) ) {
				$out .= '<article class="npl-match-card"><div class="npl-match-head"><span>' . esc_html( $m->label ) . '</span><span class="npl-badge npl-closed">Teams TBD</span></div>'
					. '<h3>' . esc_html( $m->team_a ) . ' <span class="npl-vs">vs</span> ' . esc_html( $m->team_b ) . '</h3>'
					. '<p class="npl-meta">' . esc_html( npl_giveaway_format_match_time( $m ) ) . ' NPT</p>'
					. '<p>Teams will be confirmed after the league stage — predictions open once the teams are set.</p></article>';
				continue;
			}
			$existing = npl_giveaway_get_prediction( $participant->id, $m->id );
			$out     .= npl_giveaway_predict_form( $participant, $m, $existing );
		}
	} else {
		$out .= '<p>No matches are open for predictions right now.</p>';
	}
	if ( $past ) {
		$shown = false;
		$past_html = '';
		foreach ( array_reverse( $past ) as $m ) {
			$pr = npl_giveaway_get_prediction( $participant->id, $m->id );
			if ( ! $pr ) {
				continue;
			}
			$shown = true;
			$res   = ( $m->winner !== '' ) ? esc_html( $m->winner ) . ' won by ' . esc_html( $m->margin_value ) . ' ' . esc_html( $m->margin_type ) : 'Result awaited';
			$past_html .= '<article class="npl-match-card"><div class="npl-match-head"><span>' . esc_html( $m->label ) . '</span></div>'
				. '<h3>' . esc_html( $m->team_a ) . ' vs ' . esc_html( $m->team_b ) . '</h3>'
				. '<p>Your pick: <strong>' . esc_html( $pr->pick ) . '</strong> by ' . esc_html( $pr->margin_value ) . ' ' . esc_html( $pr->margin_type ) . '</p>'
				. '<p>Result: ' . $res . ' · Points earned: <strong>' . (int) $pr->points . '</strong></p></article>';
		}
		if ( $shown ) {
			$out .= '<h2>Your past predictions</h2><div class="npl-matches">' . $past_html . '</div>';
		}
	}
	return $out;
}

function npl_giveaway_predict_form( $participant, $m, $existing ) {
	$pick  = $existing ? $existing->pick : '';
	$mt    = $existing ? $existing->margin_type : 'runs';
	$mv    = $existing ? (int) $existing->margin_value : '';
	$title = $existing ? 'Your prediction (you can change it until the match starts)' : 'Make your prediction';
	$html  = '<article class="npl-match-card npl-predict-card">'
		. '<div class="npl-match-head"><span>' . esc_html( $m->label ) . '</span><span class="npl-badge npl-open">Open until ' . esc_html( npl_giveaway_format_match_time( $m ) ) . ' NPT</span></div>'
		. '<h3>' . esc_html( $m->team_a ) . ' <span class="npl-vs">vs</span> ' . esc_html( $m->team_b ) . '</h3>'
		. '<p class="npl-meta">' . esc_html( npl_giveaway_format_match_time( $m ) ) . ' NPT</p>'
		. '<p><strong>' . esc_html( $title ) . '</strong></p>'
		. '<form method="post" class="npl-form" action="' . esc_url( get_permalink() ) . '">'
		. wp_nonce_field( 'npl_predict', 'npl_predict_nonce', true, false )
		. '<input type="hidden" name="npl_email" value="' . esc_attr( $participant->email ) . '">'
		. '<input type="hidden" name="npl_match_id" value="' . (int) $m->id . '">'
		. '<div class="npl-field"><span class="npl-label">Winner</span><div class="npl-radio-pills">'
		. '<label><input type="radio" name="npl_pick" value="' . esc_attr( $m->team_a ) . '"' . checked( $pick, $m->team_a, false ) . ' required> ' . esc_html( $m->team_a ) . '</label>'
		. '<label><input type="radio" name="npl_pick" value="' . esc_attr( $m->team_b ) . '"' . checked( $pick, $m->team_b, false ) . '> ' . esc_html( $m->team_b ) . '</label>'
		. '</div></div>'
		. '<div class="npl-field npl-margin-row"><span class="npl-label">Winning margin</span>'
		. '<select name="npl_margin_type"><option value="runs"' . selected( $mt, 'runs', false ) . '>by runs</option><option value="wickets"' . selected( $mt, 'wickets', false ) . '>by wickets</option></select>'
		. '<input type="number" name="npl_margin_value" min="1" max="200" value="' . esc_attr( $mv ) . '" required placeholder="e.g. 25">'
		. '</div>'
		. '<button type="submit" name="npl_predict_submit" class="btn btn-gold">' . ( $existing ? 'Update prediction' : 'Save prediction' ) . '</button>'
		. '</form></article>';
	return $html;
}

add_shortcode( 'npl_leaderboard', 'npl_giveaway_leaderboard_shortcode' );
function npl_giveaway_leaderboard_shortcode() {
	$rows = npl_giveaway_get_leaderboard();
	if ( empty( $rows ) ) {
		return '<p>No predictions yet — the leaderboard fills up once the first predictions are in. <a href="' . esc_url( home_url( '/predict/' ) ) . '">Be the first to predict</a>.</p>';
	}
	$out  = '<table class="npl-table"><thead><tr><th>Rank</th><th>Name</th><th>Points</th><th>Correct picks</th><th>Predictions</th></tr></thead><tbody>';
	$rank = 0;
	foreach ( $rows as $r ) {
		$rank++;
		$cls  = $rank === 1 ? ' class="npl-first"' : ( $rank === 2 ? ' class="npl-second"' : ( $rank === 3 ? ' class="npl-third"' : '' ) );
		$out .= '<tr' . $cls . '><td>' . $rank . '</td><td>' . esc_html( $r->name ) . '</td><td><strong>' . (int) $r->points . '</strong></td><td>' . (int) $r->correct . '</td><td>' . (int) $r->predictions . '</td></tr>';
	}
	$out .= '</tbody></table><p class="npl-fine">10 points for the correct winner, 5 bonus points for the correct margin. The highest total after the final on 21 November 2026 wins Rs. 1,000.</p>';
	return $out;
}
