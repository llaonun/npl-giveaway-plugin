<?php
/**
 * Plugin Name: NPL Giveaway
 * Description: Signup, match predictions, points and leaderboard for the NPL Season 3 giveaway by LekhaSetu.
 * Version: 1.0.3
 * Author: LekhaSetu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NPL_GIVEAWAY_VERSION', '1.0.3' );
define( 'NPL_GIVEAWAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'NPL_TZ', 'Asia/Kathmandu' );
define( 'NPL_PRIZE', 1000 );
define( 'NPL_POINTS_WINNER', 10 );
define( 'NPL_POINTS_MARGIN', 5 );

require_once NPL_GIVEAWAY_PATH . 'includes/matches-data.php';
require_once NPL_GIVEAWAY_PATH . 'includes/db.php';
require_once NPL_GIVEAWAY_PATH . 'includes/scoring.php';
require_once NPL_GIVEAWAY_PATH . 'includes/shortcodes.php';
require_once NPL_GIVEAWAY_PATH . 'includes/admin.php';
require_once NPL_GIVEAWAY_PATH . 'includes/rest.php';

register_activation_hook( __FILE__, 'npl_giveaway_activate' );
function npl_giveaway_activate() {
	npl_giveaway_create_tables();
	npl_giveaway_seed_matches();
}

add_action( 'wp_enqueue_scripts', 'npl_giveaway_plugin_assets' );
function npl_giveaway_plugin_assets() {
	wp_enqueue_style( 'npl-giveaway', plugins_url( 'assets/npl.css', __FILE__ ), array(), NPL_GIVEAWAY_VERSION );
	wp_enqueue_script( 'npl-giveaway', plugins_url( 'assets/npl.js', __FILE__ ), array(), NPL_GIVEAWAY_VERSION, true );
}
