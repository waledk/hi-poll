<?php

/*
* HiPoll action hooks
*/

// Admin init action
add_action('current_screen', 'hipoll_define_page', 0);
function hipoll_define_page() {

	$hipoll_page = '';
	if ( get_current_screen()->id == 'hipoll_page_hi_poll_new' ) {
		$hipoll_page = 'new-poll';
	} else {
		if ( isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] == 'edit' && is_numeric($_GET['id']) ) {
			$hipoll_page = 'edit-poll';
		} else {
			$hipoll_page = 'all-polls';
		}
	}

	define('HIPOLL_ADMIN_PAGE', $hipoll_page);
}

// Create admin menu page
add_action('admin_menu', 'hipoll_admin_menu');
function hipoll_admin_menu() {

	add_menu_page(
		__('HiPoll', 'hi-poll'), // Page title
		__('HiPoll', 'hi-poll'), // Menu title
		'edit_posts', // Capability
		'hi_poll', // Page slug
		array('HiPoll', 'polls_page') // Callback
	);
	add_submenu_page(
		'hi_poll', // Parent page slug
		__('All Polls', 'hi-poll'), // Page title
		__('All Polls', 'hi-poll'), // Menu title
		'edit_posts', // Capability
		'hi_poll', // Page slug
		array('HiPoll', 'polls_page') // Callback
	);
	add_submenu_page(
		'hi_poll', // Parent page slug
		__('New Poll', 'hi-poll'), // Page title
		__('New Poll', 'hi-poll'), // Menu title
		'edit_posts', // Capability
		'hi_poll_new', // Page slug
		array('HiPoll', 'new_poll_page') // Callback
	);
}

// Frontend scripts
add_action('wp_enqueue_scripts', 'hipoll_frontend_scripts');
function hipoll_frontend_scripts() {
	wp_enqueue_style('hipoll_frontend', hipoll_get_url('assets/css/frontend.css'), [], '1.0.0');
	wp_enqueue_script('jquery');
	wp_enqueue_script('hipoll_frontend', hipoll_get_url('assets/js/frontend.js'), ['jquery'], '1.0.0');
	wp_localize_script('hipoll_frontend', 'hipoll',
		array(
			'ajax_url' => admin_url('admin-ajax.php')
		)
	);
}

// Backend scripts
add_action('admin_enqueue_scripts', 'hipoll_backend_scripts');
function hipoll_backend_scripts() {
	wp_enqueue_media();
	wp_enqueue_style('hipoll_backend', hipoll_get_url('assets/css/backend.css'), [], '1.0.0');
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('hipoll_backend', hipoll_get_url('assets/js/backend.js'), ['jquery', 'jquery-ui-sortable'], '1.0.0');
	if ( HIPOLL_ADMIN_PAGE == 'new-poll' || HIPOLL_ADMIN_PAGE == 'edit-poll' ) {
		wp_enqueue_style('hipoll_frontend', hipoll_get_url('assets/css/frontend.css'), [], '1.0.0');
	}
}

// Create custom post type for Polls
add_action('init', 'hipoll_create_custom_post_type');
function hipoll_create_custom_post_type() {
	register_post_type('poll',
		array(
			'labels' => array(
				'name' => __('Polls', 'hi-poll'),
				'singular_name' => __('Poll', 'hi-poll')
			),
			'public' => false,
			'has_archive' => false,
		)
	);
}

add_action('current_screen', function () {
	if ( HIPOLL_ADMIN_PAGE == 'all-polls' ) {
		// Check if URL contains a poll's id to delete from the database.
		if ( isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && is_numeric($_GET['id']) ) {
			if ( hipoll_delete_poll($_GET['id']) ) {
				$redirect_to = menu_page_url('hi_poll', false);
				if ( wp_redirect($redirect_to) ) {
					exit;
				}
			}
		}
	} elseif ( HIPOLL_ADMIN_PAGE == 'new-poll' || HIPOLL_ADMIN_PAGE == 'edit-poll' ) {

		// When press on Save/Update button
		if ( isset($_POST['hipoll_save']) || isset($_POST['hipoll_update']) ) {

			$fields = $_POST['hipoll_fields'];

			$post_args = array(
				'post_title' => $fields['title'],
				'post_status' => $fields['status'],
				'post_type' => 'poll',
			);
			$poll_metadata = array(
				'style' => $fields['style'],
				'attachment' => $fields['attachment'],
				'visitors_voting' => $fields['visitors_voting'],
			);

			if ( isset($_POST['hipoll_save']) ) {
				// Insert a new poll
				$post_id = wp_insert_post($post_args);
			} elseif ( isset($_POST['hipoll_update']) ) {
				$post_args['ID'] = $_GET['id'];
				// Update an existing poll
				$post_id = wp_update_post($post_args);
			}

			// Update poll meta
			update_post_meta($post_id, 'hipoll_poll_meta', $poll_metadata);

			$poll_options_ids = array();
			foreach ( $fields['options'] as $rank => $option ) {
				foreach ( $option as $key => $option_name ) {
					if ( $key == 'unregistered' && !empty($option_name) ) {
						$poll_options_ids[] = hipoll_insert_poll_option($option_name, $post_id, $rank + 1);
					} elseif ( is_numeric($key) && !empty($option_name) ) {
						$poll_options_ids[] = hipoll_update_poll_option($option_name, $key, $rank + 1);
					}
				}
			}
			// Delete poll options if their IDs is not existing in $poll_options_ids
			hipoll_remove_poll_options($post_id, $poll_options_ids);

			if ( isset($_POST['hipoll_save']) ) {
				$redirect_to = hipoll_add_var_to_url(
					array(
						'action' => 'edit',
						'id' => $post_id
					),
					menu_page_url('hi_poll', false)
				);
				if ( wp_redirect($redirect_to) ) {
					exit;
				}
			}
		}

	}
}, 100);

/* Ajax Action Hooks */

// Voting ajax function
add_action('wp_ajax_hipoll_voting', 'hipoll_voting_ajax');
add_action('wp_ajax_nopriv_hipoll_voting', 'hipoll_voting_ajax');
function hipoll_voting_ajax() {
	global $wpdb, $hipoll_cookie;
	$poll_id = $_POST['pollID'];
	$option_id = $_POST['optionID'];
	$hipoll_cookie = $_POST['hipollCookie'];

	$return = array('status' => ['code' => '', 'message' => '']);
	if ( hipoll_add_vote($poll_id, $option_id) ) {
		$return['poll_options'] = hipoll_get_poll_options($poll_id);
		$return['status'] = ['code' => '200', 'message' => ''];
	}
	echo json_encode($return);

	wp_die(); // this is required to terminate immediately and return a proper response
}
