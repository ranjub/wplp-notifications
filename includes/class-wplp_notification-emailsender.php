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
        if ($new_status === 'Completed') {
            // Get the affiliate ID
            $affiliate_id = get_post_meta($post_id, 'affiliate_id', true);

            // Get affiliate email
            $affiliate_email = get_the_author_meta('user_email', $affiliate_id);

            // Prepare and send the email
            if ($affiliate_id) {
                // Get the user info associated with the affiliate ID
                $user_info = get_userdata($affiliate_id);
                $affiliate_name = $user_info->display_name;
                $affiliate_email = $user_info->user_email;

                // Example: Get client name, total task amount, and affiliate cut (replace with your actual meta keys)
                // $client_name = get_post_meta($post->ID, 'client_id', true);

                // Get the client ID and client name
                $client_id = get_post_meta($post_id, 'client_id', true);
                if ($client_id) {
                    $client_info = get_userdata($client_id);
                    $client_name = $client_info->display_name;
                } else {
                    $client_name = 'Unknown Client';
                }
                $task_amount_total = get_post_meta($post->ID, 'task_amt_total', true);
                $affiliate_cut = get_post_meta($post->ID, 'affiliate_cut', true);

                // Prepare your email content
                $subject = 'Status Changed';
                $message = "Dear $affiliate_name,\n\n";
                $message .= "Great news!\n";
                $message .= "We just completed a task for $client_name.\n";
                $message .= "The total for the task was $task_amount_total.\n";
                $message .= "Since they were your referral, you get a share of this. 🙂\n";
                $message .= "Your earnings from the deal: $affiliate_cut.\n\n";
                $message .= "Thank you.";

                // Send the email
                wp_mail($affiliate_email, $subject, $message);
            }
        } else if ($new_status === 'Converted') {
            // Get the affiliate ID
            $affiliate_id = get_post_meta($post_id, 'affiliate_id', true);

            // Get affiliate email
            $affiliate_email = get_the_author_meta('user_email', $affiliate_id);

            // Prepare and send the email
            if ($affiliate_id) {
                // Get the user info associated with the affiliate ID
                $user_info = get_userdata($affiliate_id);
                $affiliate_name = $user_info->display_name;
                $affiliate_email = $user_info->user_email;

                // Example: Get client name and task amount converted (replace with your actual meta keys)
                $client_name = get_post_meta($post->ID, 'client_name', true);
                $task_amount = get_post_meta($post->ID, 'task_amt_converted', true);

                // Prepare your email content
                $subject = 'Status Changed';
                $message = "Dear $affiliate_name,\n\n";
                $message .= "Good news!\n";
                $message .= "We just converted one of your referrals $client_name, for one of their tasks. ";
                $message .= "The deal was closed for $task_amount.\n";
                $message .= "We will keep you posted on how it goes.\n\n";
                $message .= "Thank you.";

                // Send the email
                wp_mail($affiliate_email, $subject, $message);
            }
        }
    }
}
new Wplp_notification_email();
