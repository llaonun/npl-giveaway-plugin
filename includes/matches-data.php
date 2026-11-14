<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full NPL Season 3 fixture list, verified from nplcricket.com.
 * Times are Nepal Time (NPT). Venue for every match: TU Ground, Kirtipur.
 */
function npl_giveaway_match_data() {
	return array(
		array( 'no' => 1,  'label' => 'Match 1',      'a' => 'Lumbini Lions',        'b' => 'Sudurpaschim Royals',  'date' => '2026-10-26 16:30:00', 'stage' => 'league' ),
		array( 'no' => 2,  'label' => 'Match 2',      'a' => 'Biratnagar Kings',     'b' => 'Chitwan Rhinos',       'date' => '2026-10-27 12:30:00', 'stage' => 'league' ),
		array( 'no' => 3,  'label' => 'Match 3',      'a' => 'Karnali Yaks',         'b' => 'Janakpur Bolts',       'date' => '2026-10-27 16:30:00', 'stage' => 'league' ),
		array( 'no' => 4,  'label' => 'Match 4',      'a' => 'Pokhara Avengers',     'b' => 'Sudurpaschim Royals',  'date' => '2026-10-28 16:30:00', 'stage' => 'league' ),
		array( 'no' => 5,  'label' => 'Match 5',      'a' => 'Janakpur Bolts',       'b' => 'Kathmandu Gorkhas',    'date' => '2026-10-29 12:30:00', 'stage' => 'league' ),
		array( 'no' => 6,  'label' => 'Match 6',      'a' => 'Karnali Yaks',         'b' => 'Biratnagar Kings',     'date' => '2026-10-29 16:30:00', 'stage' => 'league' ),
		array( 'no' => 7,  'label' => 'Match 7',      'a' => 'Lumbini Lions',        'b' => 'Chitwan Rhinos',       'date' => '2026-10-31 12:30:00', 'stage' => 'league' ),
		array( 'no' => 8,  'label' => 'Match 8',      'a' => 'Sudurpaschim Royals',  'b' => 'Biratnagar Kings',     'date' => '2026-10-31 16:30:00', 'stage' => 'league' ),
		array( 'no' => 9,  'label' => 'Match 9',      'a' => 'Janakpur Bolts',       'b' => 'Pokhara Avengers',     'date' => '2026-11-01 16:30:00', 'stage' => 'league' ),
		array( 'no' => 10, 'label' => 'Match 10',     'a' => 'Kathmandu Gorkhas',    'b' => 'Karnali Yaks',         'date' => '2026-11-02 12:30:00', 'stage' => 'league' ),
		array( 'no' => 11, 'label' => 'Match 11',     'a' => 'Sudurpaschim Royals',  'b' => 'Chitwan Rhinos',       'date' => '2026-11-02 16:30:00', 'stage' => 'league' ),
		array( 'no' => 12, 'label' => 'Match 12',     'a' => 'Biratnagar Kings',     'b' => 'Janakpur Bolts',       'date' => '2026-11-03 12:30:00', 'stage' => 'league' ),
		array( 'no' => 13, 'label' => 'Match 13',     'a' => 'Lumbini Lions',        'b' => 'Pokhara Avengers',     'date' => '2026-11-03 16:30:00', 'stage' => 'league' ),
		array( 'no' => 14, 'label' => 'Match 14',     'a' => 'Lumbini Lions',        'b' => 'Janakpur Bolts',       'date' => '2026-11-05 12:30:00', 'stage' => 'league' ),
		array( 'no' => 15, 'label' => 'Match 15',     'a' => 'Kathmandu Gorkhas',    'b' => 'Biratnagar Kings',     'date' => '2026-11-05 16:30:00', 'stage' => 'league' ),
		array( 'no' => 16, 'label' => 'Match 16',     'a' => 'Sudurpaschim Royals',  'b' => 'Karnali Yaks',         'date' => '2026-11-06 12:30:00', 'stage' => 'league' ),
		array( 'no' => 17, 'label' => 'Match 17',     'a' => 'Chitwan Rhinos',       'b' => 'Pokhara Avengers',     'date' => '2026-11-06 16:30:00', 'stage' => 'league' ),
		array( 'no' => 18, 'label' => 'Match 18',     'a' => 'Kathmandu Gorkhas',    'b' => 'Lumbini Lions',        'date' => '2026-11-07 16:30:00', 'stage' => 'league' ),
		array( 'no' => 19, 'label' => 'Match 19',     'a' => 'Pokhara Avengers',     'b' => 'Karnali Yaks',         'date' => '2026-11-09 12:30:00', 'stage' => 'league' ),
		array( 'no' => 20, 'label' => 'Match 20',     'a' => 'Chitwan Rhinos',       'b' => 'Kathmandu Gorkhas',    'date' => '2026-11-09 16:30:00', 'stage' => 'league' ),
		array( 'no' => 21, 'label' => 'Match 21',     'a' => 'Sudurpaschim Royals',  'b' => 'Janakpur Bolts',       'date' => '2026-11-10 16:30:00', 'stage' => 'league' ),
		array( 'no' => 22, 'label' => 'Match 22',     'a' => 'Biratnagar Kings',     'b' => 'Pokhara Avengers',     'date' => '2026-11-12 12:30:00', 'stage' => 'league' ),
		array( 'no' => 23, 'label' => 'Match 23',     'a' => 'Karnali Yaks',         'b' => 'Lumbini Lions',        'date' => '2026-11-12 16:30:00', 'stage' => 'league' ),
		array( 'no' => 24, 'label' => 'Match 24',     'a' => 'Kathmandu Gorkhas',    'b' => 'Sudurpaschim Royals',  'date' => '2026-11-13 12:30:00', 'stage' => 'league' ),
		array( 'no' => 25, 'label' => 'Match 25',     'a' => 'Janakpur Bolts',       'b' => 'Chitwan Rhinos',       'date' => '2026-11-13 16:30:00', 'stage' => 'league' ),
		array( 'no' => 26, 'label' => 'Match 26',     'a' => 'Biratnagar Kings',     'b' => 'Lumbini Lions',        'date' => '2026-11-14 16:30:00', 'stage' => 'league' ),
		array( 'no' => 27, 'label' => 'Match 27',     'a' => 'Chitwan Rhinos',       'b' => 'Karnali Yaks',         'date' => '2026-11-15 12:30:00', 'stage' => 'league' ),
		array( 'no' => 28, 'label' => 'Match 28',     'a' => 'Pokhara Avengers',     'b' => 'Kathmandu Gorkhas',    'date' => '2026-11-15 16:30:00', 'stage' => 'league' ),
		array( 'no' => 29, 'label' => 'Qualifier 1',  'a' => '1st Place',            'b' => '2nd Place',            'date' => '2026-11-17 16:30:00', 'stage' => 'playoff' ),
		array( 'no' => 30, 'label' => 'Eliminator',   'a' => '3rd Place',            'b' => '4th Place',            'date' => '2026-11-18 16:30:00', 'stage' => 'playoff' ),
		array( 'no' => 31, 'label' => 'Qualifier 2',  'a' => 'Loser of Qualifier 1', 'b' => 'Winner of Eliminator', 'date' => '2026-11-19 16:30:00', 'stage' => 'playoff' ),
		array( 'no' => 32, 'label' => 'Final',        'a' => 'Winner of Qualifier 1','b' => 'Winner of Qualifier 2','date' => '2026-11-21 16:30:00', 'stage' => 'playoff' ),
	);
}
