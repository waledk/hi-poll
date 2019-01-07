<?php

/*
* New poll page
*/

global $hipoll_current;

$screen = get_current_screen();

if ( $screen->id == 'hipoll_page_hi_poll_new' ) {
    $page_title = __('New Poll', 'hi-poll');
    $save_button = [
        'name' => 'hipoll_save',
        'text' => __('Save', 'hi-poll')
    ];
} else {
    $page_title = __('Edit Poll', 'hi-poll');
    $save_button = [
        'name' => 'hipoll_update',
        'text' => __('Update', 'hi-poll')
    ];
}

$get_poll = get_post($hipoll_current);
$poll_id = isset($get_poll->ID) ? $get_poll->ID : 0;
$poll_title = isset($get_poll->post_title) ? $get_poll->post_title : '';
$poll_status = isset($get_poll->post_status) ? $get_poll->post_status : '';

$poll_meta = get_post_meta($poll_id, 'hipoll_poll_meta', true);
$poll_imgs = isset($poll_meta['attachment']) ? $poll_meta['attachment'] : '';
$visitors_vote = isset($poll_meta['visitors_voting']) ? $poll_meta['visitors_voting'] : '';
$poll_imgs_ids = explode(',', $poll_imgs);
$poll_style = isset($poll_meta['style']) ? $poll_meta['style'] : '';
$get_poll_options = is_numeric($hipoll_current) ? hipoll_get_poll_options($hipoll_current) : [];

?>

<form action="" method="POST">
<div id="hipoll-container" class="hipoll-container">

    <div class="page-title">
        <h1><?php echo $page_title; ?></h1>
        <?php if ( HIPOLL_ADMIN_PAGE == 'edit-poll' ) : ?>
            <a href="<?php echo menu_page_url('hi_poll', false).'&action=delete&id='.$get_poll->ID; ?>" class="hipoll-delete"><?php _e('Delete', 'hi-poll'); ?></a>
        <?php endif; ?>
        <button class="btn update" name="<?php echo $save_button['name']; ?>"><?php echo $save_button['text']; ?></button>
    </div>

    <div class="page-content">
        <div class="column small">
            <div class="box">
                <div class="box-title"><?php _e('Details', 'hi-poll'); ?></div>
                <div class="setting">
                    <p class="setting-title"><?php _e('Status', 'hi-poll'); ?></p>
                    <select name="hipoll_fields[status]">
                        <option value="publish"><?php _e('Publish', 'hi-poll'); ?></option>
                        <option value="draft" <?php if ( $poll_status == 'draft' ) {echo 'selected="selected"';} ?>><?php _e('Draft', 'hi-poll'); ?></option>
                    </select>
                </div>

                <div class="setting">
                    <p class="setting-title"><?php _e('Style', 'hi-poll'); ?></p>
                    <select name="hipoll_fields[style]">
                        <option value="light"><?php _e('Light', 'hi-poll'); ?></option>
                        <option value="dark" <?php if ( $poll_style == 'dark' ) {echo 'selected="selected"';} ?>><?php _e('Dark', 'hi-poll'); ?></option>
                    </select>
                </div>

                <div class="setting">
                    <label><input type="checkbox" name="hipoll_fields[visitors_voting]" <?php if ( $visitors_vote == 'on' ) {echo "checked";} ?>><?php _e('Visitors can voting', 'hi-poll'); ?></label>
                </div>
            </div>

            <div class="box">
                <div class="box-title"><?php _e('Poll Options', 'hi-poll'); ?></div>
                <div class="hipoll-options-list">
                    <ul>
                        <li class="empty-field hidden"><input type="text" class="hipoll-single-option-input" placeholder="Enter your option"><span class="hipoll-delete-btn"></span></li>
                        <?php foreach ( $get_poll_options as $option ) : ?>
                            <li><input type="text" name="hipoll_fields[options][][<?php echo $option['id']; ?>]" class="hipoll-single-option-input" placeholder="Enter your option" value="<?php echo htmlspecialchars( stripslashes($option['option_name']) ); ?>"><span class="hipoll-delete-btn"></span></li>
                    <?php endforeach; ?>
                    </ul>
                    <button type="button" class="new-option"><?php _e('New Option', 'hi-poll'); ?></button>
                </div>
            </div>

            <!-- Poll Image Box -->
            <div class="box">
                <div class="box-title"><?php _e('Poll Image', 'hi-poll'); ?></div>
                <div class="hipoll-select-media">
                    <input type="text" name="hipoll_fields[attachment]" value="<?php echo htmlspecialchars($poll_imgs); ?>">
                    <button type="button" class="select"><?php _e('Select Image', 'hi-poll'); ?></button>
                    <div class="preview">
                        <?php foreach ( $poll_imgs_ids as $img_id ) : ?>
                            <?php $img_url = wp_get_attachment_image_url($img_id); ?>
                            <?php if ( $img_url !== false ) : ?>
                                <div class="single-image" data-id="<?php echo $img_id; ?>">
                                    <button type="button" class="delete-image"><?php _e('Delete', 'hi-poll'); ?></button>
                                    <img src="<?php echo $img_url; ?>" alt="">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="column large">
            <div class="box">
                <div class="box-title"><?php _e('Title', 'hi-poll'); ?></div>
                <input type="text" name="hipoll_fields[title]" placeholder="Enter your poll title" value="<?php echo $poll_title; ?>">
            </div>

            <div class="box">
                <div class="box-title"><?php _e('Preview', 'hi-poll'); ?></div>
                <?php
                    $poll_id = is_numeric($poll_id) ? $poll_id : 0;
                    echo do_shortcode('[hipoll id="'.$poll_id.'"][/hipoll]');
                ?>
            </div>

            <?php if ( HIPOLL_ADMIN_PAGE == 'edit-poll' ) : ?>
                <div class="box">
                    <div class="box-title"><?php _e('Shortcode', 'hi-poll'); ?></div>
                    <code>[hipoll id="<?php echo $poll_id; ?>"][/hipoll]</code>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</form>
