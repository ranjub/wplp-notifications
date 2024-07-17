<?php
class Wplp_notification_email {
    public function __construct() {
        add_action('transition_post_status', array($this, 'send_email_on_status_change'), 10, 3);
    }

    public function send_email_on_status_change($post_id, $old_status, $post) {
        // Check if the post type is 'ticket'
        if ($post->post_type !== 'ticket') {
            return;
        }

        // Check if the status has changed to either "Converted" or "Completed"
       $new_status = get_post_meta($post_id, 'task_status', true);

        if (($new_status === 'Converted' || $new_status === 'Completed') && $old_status !== $new_status) {
            // Get the affiliate ID
            $affiliate_id = get_post_meta($post->ID, 'affiliate_id', true);

            // Get affiliate email
            $affiliate_email = get_the_author_meta('user_email', $affiliate_id);

            // Prepare and send the email
            if ($affiliate_email) {
                $subject = 'Status Update: ' . ucfirst($new_status);
                $message = 'Hello,

The status of your ticket has been changed to ' . ucfirst($new_status) . '.

Here are the details:
- Ticket ID: ' . $post->ID . '
- Status: ' . ucfirst($new_status) . '

Best regards,
Your Company';

                wp_mail($affiliate_email, $subject, $message);
            }
        }
    }
}

// Instantiate the class after WordPress has loaded
add_action('plugins_loaded', function () {
    new Wplp_notification_email();
});