<?php

/**
 * Fired during plugin activation
 *
 * @link       https://whitelabelwp.io
 * @since      1.0.0
 *
 * @package    Wplp_notification
 * @subpackage Wplp_notification/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wplp_notification
 * @subpackage Wplp_notification/includes
 * @author     Ranju , Prashna <ranjubhusal57@gmail.com>
 */
class Wplp_notification_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		// to register the post type called "Tickets"
		self::register_tickets_post_type();
		flush_rewrite_rules();

		// Create custom database table on plugin activation
		self::create_custom_table();
	}


	public static function register_tickets_post_type()
	{
		$labels = array(
			'name'               => _x('Tickets', 'post type general name', 'wplp_notification'),
			'singular_name'      => _x('Ticket', 'post type singular name', 'wplp_notification'),
			'menu_name'          => _x('Tickets', 'admin menu', 'wplp_notification'),
			'name_admin_bar'     => _x('Ticket', 'add new on admin bar', 'wplp_notification'),
			'add_new'            => _x('Add New', 'ticket', 'wplp_notification'),
			'add_new_item'       => __('Add New Ticket', 'wplp_notification'),
			'new_item'           => __('New Ticket', 'wplp_notification'),
			'edit_item'          => __('Edit Ticket', 'wplp_notification'),
			'view_item'          => __('View Ticket', 'wplp_notification'),
			'all_items'          => __('All Tickets', 'wplp_notification'),
			'search_items'       => __('Search Tickets', 'wplp_notification'),
			'parent_item_colon'  => __('Parent Tickets:', 'wplp_notification'),
			'not_found'          => __('No tickets found.', 'wplp_notification'),
			'not_found_in_trash' => __('No tickets found in Trash.', 'wplp_notification')
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array('slug' => 'ticket'),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array('title', 'editor', 'comments')
		);

		register_post_type('ticket', $args);
	}


	public static function create_custom_table()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'wplp_notifications';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            notify_date timestamp DEFAULT CURRENT_TIMESTAMP,
            user_id bigint(20) NOT NULL,
            notify_to bigint(20) NOT NULL,
            notification_for varchar(15) NOT NULL,
            url varchar(255) DEFAULT '' NOT NULL,
            notification_read tinyint(1) DEFAULT 0,
            notification_read_on timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}