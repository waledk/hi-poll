<?php

/*
* HiPoll main class
*
* Contains plugin admin pages & poll shortcode
*/

global $hipoll;

if ( ! class_exists('HiPoll') ) {
    class HiPoll {

        // Class Constractor
        public function __construct() {
            add_shortcode('hipoll', [$this, 'hipoll_shortcode']);
        }

        public function polls_page() {
            if ( isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] == 'edit' && is_numeric($_GET['id']) ) {
                include_once hipoll_get_path('includes/admin/edit-poll.php');
            } else {
                include_once hipoll_get_path('includes/admin/polls.php');
            }
        }

        public function new_poll_page() {
            include_once hipoll_get_path('includes/admin/new-poll.php');
        }

        public function hipoll_shortcode($atts, $content = null) {

            if ( ! isset($atts['id']) || ! is_numeric($atts['id']) ) {
                return;
            }

            $get_poll = get_post($atts['id']);
            $default_meta = array(
                'attachment' => '',
                'style' => '',
                'visitors_voting' => ''
            );
            $poll_meta = get_post_meta($atts['id'], 'hipoll_poll_meta', true);
            $poll_meta = array_merge($default_meta, $poll_meta);
            $poll_options = hipoll_get_poll_options($atts['id']);

            if ( ($get_poll->post_type != 'poll' && $atts['id'] != 0) || ($get_poll->post_status != 'publish' && !is_admin()) ) {
                return;
            }

            $user_voted = ($atts['id'] != 0 && !is_admin()) ? hipoll_user_voted_before($get_poll->ID) : false;

            $classes = 'hipoll-frontend-poll';
            $classes .= ($poll_meta['style'] == 'dark') ? ' dark-mode' : '';
            $classes .= is_admin() ? ' admin' : '';
            $classes .= (HIPOLL_ADMIN_PAGE == 'new-poll') ? ' new-poll' : '';
            $classes .= ($user_voted) ? ' voted' : '';

            $html = "<div class='{$classes}' data-poll-id='{$get_poll->ID}'>";
                $html .= "<div class='poll-container'>";

                    // Poll image
                    $img_url = wp_get_attachment_image_url($poll_meta['attachment'], 'medium');

                    $img_url = ($img_url) ? $img_url : '';
                    $html .= "<div class='poll-image'>";
                        $html .= "<img src='{$img_url}'>";
                    $html .= "</div>";

                    // Poll title
                    $html .= "<div class='poll-title'>";
                        $html .= "<p>{$get_poll->post_title}</p>";
                    $html .= "</div>";

                    // Poll options
                    $html .= "<div class='poll-options'>";
                        $radio_inputs_name = uniqid();
                        foreach ( $poll_options as $key => $option ) {
                            $percent = ($user_voted) ? $option['percent'].'%' : '';
                            $percent_attr = ($user_voted) ? " data-percent='" . $percent . "'" : '';
                            $html .= "<div class='poll-single-option'>";
                                $html .= "<label{$percent_attr}><input name='{$radio_inputs_name}' type='radio' data-option-id='" . $option['id'] . "'>".$option['option_name']."</label>";
                                $html .= "<span class='progress' style='width:{$percent};'></span>";
                            $html .= "</div>";
                        }
                    $html .= "</div>";

                $html .= "</div>";
            $html .= "</div>";
            return $html;

        }

    }
}

$hipoll = new HiPoll();
