<?php
class Wplp_notification_email
{
    public function __construct()
    {
        add_action('save_post', array($this, 'send_email_to_affiliate_on_status_change'), 10, 3);
    }

    public function send_email_to_affiliate_on_status_change($post_id, $post, $update)
    {
        // Check if the post is a ticket and not an autosave or revision
        if ($post->post_type !== 'ticket' || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Get the new status of the post
        $new_status = get_post_meta($post_id, 'task_status', true);

        // Check if the new status is "Converted" or "Completed"
        if ($new_status === 'Converted' || $new_status === 'Completed') {
            // Get the affiliate ID
            $affiliate_id = get_post_meta($post_id, 'affiliate_id', true);

            // Get affiliate email
            $affiliate_email = get_the_author_meta('user_email', $affiliate_id);

            // Prepare and send the email
            if ($affiliate_email) {
                $subject = 'Status Update: ' . ucfirst($new_status);
                $message = 'Hello,

The status of your ticket has been changed to ' . ucfirst($new_status) . '.

Here are the details:
- Ticket ID: ' . $post_id . '
- Status: ' . ucfirst($new_status) . '

Best regards,
Your Company';

                wp_mail($affiliate_email, $subject, $message);
            }
        }
    }
}

new Wplp_notification_email();
