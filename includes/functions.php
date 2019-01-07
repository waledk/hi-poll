<?php

/*
* HiPoll functions
*/

function hipoll_get_url($url = null) {
    return plugin_dir_url(__DIR__) . $url;
}

function hipoll_get_path($url = null) {
    return plugin_dir_path(__DIR__) . $url;
}

function hipoll_option_exists($option_id, $poll_id = null) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hipoll_options';
    $where = "id = $option_id";
    if ( $poll_id !== null ) {
        $where .= " AND poll_id = $poll_id";
    }
    $get_option = $wpdb->get_results("SELECT * FROM $table_name WHERE $where ORDER BY rank ASC", ARRAY_A);
    return (count($get_option) > 0) ? true : false;
}

function hipoll_get_poll_options($poll_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hipoll_options';
    $where = "poll_id = $poll_id";

    $poll_options = $wpdb->get_results("SELECT * FROM $table_name WHERE $where ORDER BY rank ASC", ARRAY_A);

    if ( !is_array($poll_options) ) {
        return false;
    }

    $poll_options_by_counts = $poll_options;

    usort($poll_options_by_counts, function ($a, $b) { return $a['count'] < $b['count']; });
    $votes_sum = 0;
    foreach ( $poll_options as $key => $option ) {
        $votes_sum += is_numeric($option['count']) ? $option['count'] : 0;
    }
    $votes_sum_percents = 0;
    foreach ( $poll_options as $key => $option ) {
        $option_percent = floor( ($option['count'] / $votes_sum) * 100 );
        $poll_options[$key]['percent'] = $option_percent;
        $votes_sum_percents += $option_percent;
    }
    foreach ( $poll_options as $key => $option ) {
        $poll_options[$key]['percent'] = ($poll_options_by_counts[0]['id'] == $option['id']) ? $poll_options[$key]['percent'] + (100 - $votes_sum_percents) : $poll_options[$key]['percent'];
    }

    return $poll_options;
}

function hipoll_update_poll_option($option_name = null, $option_id = 0, $rank = 1) {
    global $wpdb;
    $update_option = $wpdb->update(
        $wpdb->prefix . 'hipoll_options', // Table name
        array(
            'option_name' => $option_name,
            'rank' => $rank
        ),
        array('id' => $option_id)
    );
    if ( $update_option !== false ) {
        return $option_id;
    }
}

function hipoll_remove_poll_options($poll_id = 0, $options_exceptions_ids = array()) {

    global $wpdb;
    $options_exceptions_ids = is_array($options_exceptions_ids) ? $options_exceptions_ids : [];

    $table_name = $wpdb->prefix . 'hipoll_options';
    $where = "poll_id = '$poll_id'";
    if ( count($options_exceptions_ids) > 0 ) {
        $options_exceptions_ids = implode(',', $options_exceptions_ids);
        $where .= " AND id NOT IN($options_exceptions_ids)";
    }

    $update_options = $wpdb->query("DELETE FROM $table_name WHERE $where");

}

function hipoll_insert_poll_option($option_name = null, $poll_id = 0, $rank = 1) {
    global $wpdb;
    $insert_option = $wpdb->insert(
        $wpdb->prefix . 'hipoll_options', // Table name
        array(
            'poll_id' => $poll_id,
            'option_name' => $option_name,
            'rank' => $rank
        )
    );
    if ( $insert_option !== false ) {
        return $wpdb->insert_id;
    }
}

function hipoll_user_voted_before($poll_id) {
    global $wpdb, $hipoll_cookie;

    $hipoll_cookie = $_COOKIE['hipoll_voted_polls'];
    $current_user = wp_get_current_user();

    if ( $current_user->ID != 0 ) {
        $table_name = $wpdb->prefix . 'hipoll_votes';
        $where = "user_id = {$current_user->ID} AND poll_id = {$poll_id}";
        $votes = $wpdb->get_results("SELECT * FROM $table_name WHERE $where", ARRAY_A);

        // check if current user voted on this poll before
        if ( count($votes) > 0 ) {
            return true;
        }
    } else {
        $hipoll_cookie = explode(',', $hipoll_cookie);
        if ( in_array($poll_id, $hipoll_cookie) ) {
            return true;
        }
    }
    return false;
}

// This function (hipoll_user_can_vote) checks if current user can vote on a poll
function hipoll_user_can_vote($poll_id) {
    $poll_meta = get_post_meta($poll_id, 'hipoll_poll_meta', true);
    $post_meta = array_merge(
        ['visitors_voting' => ''],
        $poll_meta
    );

    // check if this poll disallows visitors voting
    if ( $poll_meta['visitors_voting'] != 'on' && !is_user_logged_in() ) {
        return false;
    }

    if ( hipoll_user_voted_before($poll_id) ) {
        return false;
    }

    return true;
}

// This function add vote to database
function hipoll_add_vote($poll_id, $option_id) {
    global $wpdb, $hipoll_cookie;

    include_once ABSPATH . '/wp-includes/pluggable.php';
    if ( !is_numeric($poll_id) || !is_numeric($option_id) || !hipoll_option_exists($option_id, $poll_id) || !hipoll_user_can_vote($poll_id) ) {
        return false;
    }

    $hipoll_cookie = explode(',', $hipoll_cookie);

    $current_user = wp_get_current_user();

    $insert_vote = $wpdb->insert(
        $wpdb->prefix . 'hipoll_votes', // Table name
        array(
            'user_id' => $current_user->ID,
            'poll_id' => $poll_id,
            'option_id' => $option_id,
            'date' => gmdate('Y-m-d H:i:s')
        )
    );

    if ( $current_user->ID == 0 ) {
        $hipoll_cookie[] = $poll_id;
        $hipoll_cookie = implode(',', $hipoll_cookie);
        setcookie('hipoll_voted_polls', $hipoll_cookie, time() + 60*60*24*30*12*10, '/');
    }

    if ( !$insert_vote ) {
        return false;
    }

    $table_name = $wpdb->prefix . 'hipoll_options';
    $update_option = $wpdb->query("UPDATE {$table_name} SET count = count + 1 WHERE id = '{$option_id}' AND poll_id = '{$poll_id}'");

    return true;
}

// Delete a poll (will delete its options and votes)
function hipoll_delete_poll($poll_id) {
    global $wpdb;

    if ( !is_numeric($poll_id) || $poll_id == 0 ) {
        return false;
    }

    $get_poll = get_post($poll_id);
    if ( $get_poll->post_type != 'poll' ) {
        return false;
    }

    // Delete poll
    wp_delete_post($poll_id, true);
    // Delete poll metadata
    delete_post_meta($poll_id, 'hipoll_poll_meta');
    // Delete poll's options
    hipoll_remove_poll_options($poll_id);
    // Delete poll votes
    $wpdb->delete(
        $wpdb->prefix . 'hipoll_votes',
        array(
            'poll_id' => $poll_id,
        )
    );

    return true;
}

// add varibles to URL
function hipoll_add_var_to_url($variables, $url_string) {
	foreach ( $variables as $variable_name => $variable_value ) {
		// first we will remove the var (if it exists)
		// test if url has variables (contains "?")
		if ( strpos($url_string,'?') !== false ) {
			$start_pos = strpos($url_string, '?');
			$url_vars_strings = substr($url_string, $start_pos + 1);
			$names_and_values = explode('&', $url_vars_strings);
			$url_string = substr($url_string, 0, $start_pos);
			foreach ( $names_and_values as $value ) {
				list($var_name, $var_value) = explode('=', $value);
				if ( $var_name != $variable_name ) {
					if ( strpos($url_string, '?') === false ) {
						$url_string .= '?';
					} else {
						$url_string .= '&';
					}
					$url_string .= $var_name.'='.$var_value;
				}
			}
		}
		// add variable name and variable value
		if ( strpos($url_string, '?') === false ) {
			$url_string .= '?'.$variable_name.'='.$variable_value;
		} else {
			$url_string .= '&'.$variable_name.'='.$variable_value;
		}
	}
	return $url_string;
}
