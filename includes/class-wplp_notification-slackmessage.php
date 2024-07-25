<?php
class Wplp_notification_message
{
    public function __construct()
    {
        add_action('save_post', array($this, 'send_slack_message'), 10, 3);
    }
    public function send_slack_message($post_id, $post)
    {
        // to check if the posttype 'ticket' or not
        if (get_post_type($post_id) != 'ticket') {
            return;
        } else if ($post->post_status == 'publish') {
            // Get the affiliate ID
            $affiliate_id = get_post_meta($post_id, 'affiliate_id', true);

            // Get affiliate email
            $affiliate_email = get_the_author_meta('user_email', $affiliate_id);

            // Get the client_id from post meta
            $client_id = get_post_meta($post_id, 'client_id', true);

            // Assuming client_id corresponds to a user ID, get the user data
            $user_info = get_userdata($affiliate_id);

            $client_email = $user_info->user_email;

            // Prepare the message
            $message = "A new ticket has been created. " . get_permalink($post->ID);


            // Send the message to Slack
            $slack_webhook_url = 'https://hooks.slack.com/services/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';

            $payload = json_encode([
                'text' => $message,
                'channel' => '#projectnotification-plugin' //  Slack channel
            ]);

            $args = [
                'body'        => $payload,
                'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                'timeout'     => 60
            ];

            wp_remote_post($slack_webhook_url, $args);
        }
        
    }
    
}
new Wplp_notification_message();

