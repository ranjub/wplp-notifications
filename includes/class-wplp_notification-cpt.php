<?php
class Wplp_notification_CPT
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_tickets_meta_boxes'));
        add_action('save_post', array($this, 'save_tickets_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'tickets_admin_scripts'));
        add_action('do_meta_boxes', array($this, 'remove_default_custom_fields'));

        // Add custom column to the All Users page
        add_filter('manage_users_columns', array($this, 'add_affiliate_percentage_column'));
        add_action('manage_users_custom_column', array($this, 'show_affiliate_percentage_column'), 10, 3);

        // Add custom user meta field
        add_action('show_user_profile', array($this, 'show_affiliate_percentage_field'));
        add_action('edit_user_profile', array($this, 'show_affiliate_percentage_field'));
        // add_action('personal_options_update', array($this, 'save_affiliate_percentage_field'));
        add_action('edit_user_profile_update', array($this, 'save_affiliate_percentage_field'));

        // Save affiliate cut based on user affiliate percentage when user is updated
        add_action('profile_update', array($this, 'update_affiliate_cut_from_user'));
    }

    public function add_tickets_meta_boxes()
    {
        add_meta_box(
            'tickets_meta_box_status', // Unique ID
            'Ticket Status', // Box title
            array($this, 'display_status_meta_box'), // Content callback, must be of type callable
            'ticket', // Post type
            'side' // Context: Display on the side
        );

        add_meta_box(
            'tickets_meta_box_details', // Unique ID
            'Ticket Details', // Box title
            array($this, 'display_details_meta_box'), // Content callback, must be of type callable
            'ticket', // Post type
            'side' // Context: Display on the side
        );
    }

    public function display_status_meta_box($post)
    {
        wp_nonce_field('save_ticket_details', 'ticket_details_nonce');

        $task_status = get_post_meta($post->ID, 'task_status', true);
        $affiliate_id = get_post_meta($post->ID, 'affiliate_id', true);
        $client_id = get_post_meta($post->ID, 'client_id', true);
        $users = get_users();
?>
<label for="task_status">Task Status:</label>
<select name="task_status" id="task_status">
    <option value="Early" <?php selected($task_status, 'Early'); ?>>Early</option>
    <option value="Contacted" <?php selected($task_status, 'Contacted'); ?>>Contacted</option>
    <option value="Awaiting Response" <?php selected($task_status, 'Awaiting Response'); ?>>Awaiting Response</option>
    <option value="Converted" <?php selected($task_status, 'Converted'); ?>>Converted</option>
    <option value="Completed" <?php selected($task_status, 'Completed'); ?>>Completed</option>
</select>
<br>
<label for="affiliate_id">Affiliate ID:</label>
<select name="affiliate_id" id="affiliate_id">
    <option value=""><?php _e('Select Affiliate', 'wplp_notification'); ?></option>
    <?php foreach ($users as $user) { ?>
    <option value="<?php echo $user->ID; ?>" <?php selected($affiliate_id, $user->ID); ?>>
        <?php echo $user->display_name; ?></option>
    <?php } ?>
</select>
<br>
<label for="client_id">Client ID:</label>
<select name="client_id" id="client_id">
    <option value=""><?php _e('Select Client', 'wplp_notification'); ?></option>
    <?php foreach ($users as $user) { ?>
    <option value="<?php echo $user->ID; ?>" <?php selected($client_id, $user->ID); ?>>
        <?php echo $user->display_name; ?></option>
    <?php } ?>
</select>
<?php
    }

    public function display_details_meta_box($post)
    {
        wp_nonce_field('save_ticket_details', 'ticket_details_nonce');

        $task_amt_converted = get_post_meta($post->ID, 'task_amt_converted', true);
        $task_amt_total = get_post_meta($post->ID, 'task_amt_total', true);
        $affiliate_cut = get_post_meta($post->ID, 'affiliate_cut', true);
    ?>
<label for="task_amt_converted">Task Amount Converted:</label>
<input type="number" name="task_amt_converted" id="task_amt_converted" min="0"
    value="<?php echo esc_attr($task_amt_converted); ?>">
<br>
<label for="task_amt_total">Task Amount Total:</label>
<input type="number" name="task_amt_total" id="task_amt_total" min="0" value="<?php echo esc_attr($task_amt_total); ?>">
<br>
<label for="affiliate_cut">Affiliate Cut:</label>
<input type="number" name="affiliate_cut" id="affiliate_cut" min="0" value="<?php echo esc_attr($affiliate_cut); ?>">
<?php
    }

    public function save_tickets_meta_box($post_id)
    {
        // Verify nonce
        if (!isset($_POST['ticket_details_nonce']) || !wp_verify_nonce($_POST['ticket_details_nonce'], 'save_ticket_details')) {
            return $post_id;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check user permissions
        if (isset($_POST['post_type']) && 'ticket' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }

        // Save the meta fields
        if (isset($_POST['task_status'])) {
            update_post_meta($post_id, 'task_status', sanitize_text_field($_POST['task_status']));
        }
        if (isset($_POST['task_amt_converted'])) {
            update_post_meta($post_id, 'task_amt_converted', sanitize_text_field($_POST['task_amt_converted']));
        }
        if (isset($_POST['task_amt_total'])) {
            update_post_meta($post_id, 'task_amt_total', sanitize_text_field($_POST['task_amt_total']));
        }
        if (isset($_POST['affiliate_id'])) {
            update_post_meta($post_id, 'affiliate_id', intval($_POST['affiliate_id']));
        }
        if (isset($_POST['client_id'])) {
            update_post_meta($post_id, 'client_id', intval($_POST['client_id']));
        }
        if (isset($_POST['affiliate_cut'])) {
            update_post_meta($post_id, 'affiliate_cut', sanitize_text_field($_POST['affiliate_cut']));
        }

        // Always calculate affiliate cut
        $this->calculate_and_update_affiliate_cut($post_id);

        // Add a notification when task_status is changed
        $previous_status = get_post_meta($post_id, 'task_status', true);
        if (isset($_POST['task_status']) && $_POST['task_status'] !== $previous_status) {
            $this->add_notification($post_id, 'task_status', sanitize_text_field($_POST['task_status']));
        }
    }

    public function calculate_and_update_affiliate_cut($post_id)
    {
        $task_amt_total = floatval(get_post_meta($post_id, 'task_amt_total', true));
        $affiliate_id = intval(get_post_meta($post_id, 'affiliate_id', true));
        $affiliate_percentage = floatval(get_user_meta($affiliate_id, 'affiliate_percentage', true));
        $affiliate_cut = ($task_amt_total * $affiliate_percentage) / 100;
        update_post_meta($post_id, 'affiliate_cut', $affiliate_cut);
    }

    public function tickets_admin_scripts($hook)
    {
        if ('post.php' != $hook && 'post-new.php' != $hook) {
            return;
        }

        global $post;
        if ('ticket' != $post->post_type) {
            return;
        }

        wp_enqueue_script('wplp-admin-script', plugins_url('admin/js/wplp_notification-admin.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_style('wplp-admin-style', plugins_url('admin/css/wplp_notification-admin.css', __FILE__));
    }

    public function remove_default_custom_fields()
    {
        remove_meta_box('postcustom', 'ticket', 'normal');
    }

    public function add_affiliate_percentage_column($columns)
    {
        $columns['affiliate_percentage'] = __('Affiliate %', 'wplp_notification');
        return $columns;
    }

    public function show_affiliate_percentage_column($value, $column_name, $user_id)
    {
        if ('affiliate_percentage' == $column_name) {
            $affiliate_percentage = get_user_meta($user_id, 'affiliate_percentage', true);
            return esc_html($affiliate_percentage . '%');
        }
        return $value;
    }

    public function show_affiliate_percentage_field($user)
    {
        // if (!current_user_can('edit_user', $user->ID)) {
        //     return;
        // }
    ?>
<h3><?php _e('Affiliate Information', 'wplp_notification'); ?></h3>
<table class="form-table">
    <tr>
        <th><label for="affiliate_percentage"><?php _e('Affiliate Percentage', 'wplp_notification'); ?></label></th>
        <td>
            <input type="number" name="affiliate_percentage" id="affiliate_percentage" min="0" max="100"
                value="<?php echo esc_attr(get_user_meta($user->ID, 'affiliate_percentage', true)); ?>">
        </td>
    </tr>
</table>
<?php
    }

    public function save_affiliate_percentage_field($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (isset($_POST['affiliate_percentage'])) {
            $affiliate_percentage = floatval($_POST['affiliate_percentage']);
            update_user_meta($user_id, 'affiliate_percentage', $affiliate_percentage);

            // Update the affiliate cut amount in tickets
            $this->update_affiliate_cut_from_user($user_id);
        }
    }

    public function update_affiliate_cut_from_user($user_id)
    {
        $affiliate_percentage = floatval(get_user_meta($user_id, 'affiliate_percentage', true));

        $tickets = get_posts(array(
            'post_type' => 'ticket',
            'meta_query' => array(
                array(
                    'key' => 'affiliate_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            )
        ));

        foreach ($tickets as $ticket) {
            $task_amt_total = floatval(get_post_meta($ticket->ID, 'task_amt_total', true));
            $affiliate_cut = ($task_amt_total * $affiliate_percentage) / 100;
            update_post_meta($ticket->ID, 'affiliate_cut', $affiliate_cut);
        }
    }

    public function add_notification($post_id, $meta_key, $meta_value)
    {
        global $wpdb;

        $user_id = get_current_user_id();
        $permalink = get_permalink($post_id);

        $wpdb->insert(
            $wpdb->prefix . 'wplp_notifications',
            array(
                'notify_date' => current_time('mysql'),
                'user_id' => $user_id,
                'notify_to' => 0,
                'notification_for' => 'tic_' . $post_id,
                'url' => $permalink,
                'notification_read' => 0,
                'notification_read_on' => '0000-00-00 00:00:00'
            ),
            array(
                '%s', '%d', '%d', '%s', '%s', '%d', '%s'
            )
        );
    }
}

// new Wplp_notification_CPT();