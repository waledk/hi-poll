<?php

/*
* All polls admin page
*/

global $wpdb;
$paged = (isset($_GET['paged']) && is_numeric($_GET['paged'])) ? $_GET['paged'] : 1;

$posts_per_page = 10;
$posts_offset = $posts_per_page * ($paged - 1);

$get_polls = new WP_Query( array(
    'posts_per_page'   => $posts_per_page,
    'offset'           => $posts_offset,
    'orderby'          => 'date',
    'order'            => 'DESC',
    'post_type'        => 'poll',
    'author'           => '',
    'post_status'      => 'publish',
) );

?>

<div id="hipoll-container" class="hipoll-container">

    <div class="page-title">
        <h1><?php _e('All Polls', 'hi-poll'); ?></h1>
        <a href="<?php menu_page_url('hi_poll_new'); ?>" class="btn new-poll"><?php _e('Add New', 'hi-poll'); ?></a>
    </div>

    <div class="page-content">
        <div class="hipoll-table">
            <div class="thead">
                <div class="tr">
                    <div class="th"><?php _e('ID', 'hi-poll'); ?></div>
                    <div class="th"><?php _e('Title', 'hi-poll'); ?></div>
                    <div class="th"><?php _e('Votes', 'hi-poll'); ?></div>
                    <div class="th"><?php _e('Date', 'hi-poll'); ?></div>
                </div>
            </div>
            <div class="tbody">
                <?php foreach ( $get_polls->posts as $poll ) : ?>
                    <div class="tr">
                        <div class="th"><?php echo $poll->ID; ?></div>
                        <div class="th"><strong><a href="<?php echo menu_page_url('hi_poll', false).'&action=edit&id=' . $poll->ID; ?>"><?php echo $poll->post_title; ?></a></strong></div>
                        <?php
                        $table_name = $wpdb->prefix . 'hipoll_options';
                        $get_votes_sum = $wpdb->get_results("SELECT SUM(count) AS votes_sum FROM {$table_name} WHERE poll_id = '{$poll->ID}'", ARRAY_A);
                        ?>
                        <div class="th"><?php echo $get_votes_sum[0]['votes_sum']; ?></div>
                        <div class="th"><?php echo $poll->post_date; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pagination">
            <?php
            $page_url = menu_page_url('hi_poll', false);
            echo paginate_links( array(
                'base' => esc_url( $page_url . '%_%' ),
                'format' => strpos($page_url, '?') ? '&paged=%#%' : '?paged=%#%',
                'current' => $paged,
                'total' => $get_polls->max_num_pages,
                'prev_text' => '<',
                'next_text' => '>'
            ) );
            ?>
        </div>
    </div>

</div>
