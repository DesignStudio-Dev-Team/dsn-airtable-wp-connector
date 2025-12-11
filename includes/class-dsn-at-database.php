<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSN_AT_Database {

	public function __construct() {
		// Constructor
	}

	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'airtable_products';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			airtable_id varchar(50) NOT NULL UNIQUE,
			data longtext NOT NULL,
			updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function save_products( $products ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'airtable_products';

        foreach ( $products as $record ) {
            $airtable_id = $record->id;
            $fields      = wp_json_encode( $record->fields ); // Store fields as JSON
            $now         = current_time( 'mysql' );

            // Check if exists
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE airtable_id = %s", $airtable_id ) );

            if ( $exists ) {
                $wpdb->update(
                    $table_name,
                    array(
                        'data'       => $fields,
                        'updated_at' => $now
                    ),
                    array( 'airtable_id' => $airtable_id ),
                    array( '%s', '%s' ),
                    array( '%s' )
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array(
                        'airtable_id' => $airtable_id,
                        'data'        => $fields,
                        'updated_at'  => $now
                    ),
                    array( '%s', '%s', '%s' )
                );
            }
        }
	}
    
    public function get_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'airtable_products';
        return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY updated_at DESC" );
    }
}
