<?php

/*
Plugin Name: Hi Poll
Plugin URI:
Description: This plugin helps you to create beautiful and useful polls.
Author: WK
Version: 0.1.0
*/

// Include HiPoll functions
include_once __DIR__ . '/includes/functions.php';

// Include main class
include_once hipoll_get_path('includes/class-hipoll.php');

// Include action hooks
include_once hipoll_get_path('includes/action-hooks.php');

// Create plugin's tables in database
register_activation_hook(__FILE__, 'hipoll_after_activate');
function hipoll_after_activate() {
	global $wpdb;

	$hipoll_votes = $wpdb->prefix . 'hipoll_votes';
    $hipoll_options = $wpdb->prefix . 'hipoll_options';

	$charset_collate = $wpdb->get_charset_collate();

	$hipoll_votes_sql = "CREATE TABLE $hipoll_votes (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		user_id varchar(11) NOT NULL,
		poll_id varchar(11) NOT NULL,
		option_id varchar(11) NOT NULL,
        date datetime NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

    $hipoll_options_sql = "CREATE TABLE $hipoll_options (
		id int(11) NOT NULL AUTO_INCREMENT,
		poll_id varchar(11) NOT NULL,
        option_name varchar(1000) NOT NULL,
		rank varchar(11) NOT NULL,
		count varchar(1000) NOT NULL DEFAULT '0',
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $hipoll_votes_sql );
    dbDelta( $hipoll_options_sql );

}
