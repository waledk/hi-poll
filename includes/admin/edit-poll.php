<?php

/*
* Edit poll page
*/

global $hipoll_current;

$poll_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0;
$hipoll = get_post($poll_id);

if ( $hipoll->post_type == 'poll' ) {
    $hipoll_current = get_post($poll_id)->ID;
    include_once hipoll_get_path('includes/admin/new-poll.php');
}
