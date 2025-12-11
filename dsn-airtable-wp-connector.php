<?php
/**
 * Plugin Name: DSN Airtable WP Connector
 * Plugin URI: https://designstudio.com
 * Description: Connects to Airtable to fetch product information and store it in a custom local database table.
 * Version: 1.0.0
 * Author: DesignStudio Network, Inc.
 * Author URI: https://designstudio.com
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DSN_AT_VERSION', '1.0.0' );
define( 'DSN_AT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSN_AT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once DSN_AT_PLUGIN_DIR . 'includes/class-dsn-at-database.php';
require_once DSN_AT_PLUGIN_DIR . 'includes/class-dsn-at-api.php';
require_once DSN_AT_PLUGIN_DIR . 'includes/class-dsn-at-api.php';
require_once DSN_AT_PLUGIN_DIR . 'includes/class-dsn-at-admin.php';
require_once DSN_AT_PLUGIN_DIR . 'includes/class-dsn-at-scheduler.php';
require_once DSN_AT_PLUGIN_DIR . 'includes/class-dsn-at-updater.php';

// Initialize the plugin
function dsn_at_init() {
	$database  = new DSN_AT_Database();
	$api       = new DSN_AT_API();
	$admin     = new DSN_AT_Admin( $api, $database );
	$scheduler = new DSN_AT_Scheduler( $api, $database );
    
    // Initialize Updater
    if ( is_admin() ) {
        new DSN_AT_Updater();
    }

	$admin->init();
	$scheduler->init();
}
add_action( 'plugins_loaded', 'dsn_at_init' );

// Activation hook
function dsn_at_activate() {
	DSN_AT_Database::create_table();
	if ( ! wp_next_scheduled( 'dsn_at_daily_sync' ) ) {
		wp_schedule_event( time(), 'daily', 'dsn_at_daily_sync' );
	}
}
register_activation_hook( __FILE__, 'dsn_at_activate' );

// Deactivation hook
function dsn_at_deactivate() {
	wp_clear_scheduled_hook( 'dsn_at_daily_sync' );
}
register_deactivation_hook( __FILE__, 'dsn_at_deactivate' );
