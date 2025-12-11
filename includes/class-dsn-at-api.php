<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSN_AT_API {

	public function __construct() {
		// Constructor
	}

    public function fetch_tables() {
        $api_key = get_option( 'dsn_at_api_key' );
        $base_id = get_option( 'dsn_at_base_id' );

        if ( empty( $api_key ) || empty( $base_id ) ) {
            return new WP_Error( 'missing_settings', 'Please configure API Key and Base ID.' );
        }

        $url = "https://api.airtable.com/v0/meta/bases/{$base_id}/tables";
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $body = wp_remote_retrieve_body( $response );
            return new WP_Error( 'api_error', 'MetaData API Error (' . $code . '): ' . $body );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( empty( $data->tables ) ) {
            return array();
        }

        return $data->tables;
    }

	public function fetch_data() {
		$api_key    = get_option( 'dsn_at_api_key' );
		$base_id    = get_option( 'dsn_at_base_id' );
		$table_name = get_option( 'dsn_at_table_name' );

        if ( empty( $api_key ) || empty( $base_id ) || empty( $table_name ) ) {
            return new WP_Error( 'missing_settings', 'Please configure API Key, Base ID, and Table Name in settings.' );
        }

        $url = "https://api.airtable.com/v0/{$base_id}/" . rawurlencode( $table_name );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $body = wp_remote_retrieve_body( $response );
            $error_msg = 'Airtable API Error (' . $code . '): ' . $body;
            
            if ( $code === 404 ) {
                $error_msg .= ' - This usually means the Base ID is incorrect or the Table Name "' . esc_html( $table_name ) . '" could not be found in the base.';
            }
            
            return new WP_Error( 'api_error', $error_msg );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( empty( $data->records ) ) {
            return array();
        }

        // Handle pagination if needed (fetching all records)
        // For V1, let's just fetch the first page or until specific limit. User said "get all information".
        // Airtable limits to 100 per request. I should loop if there is an offset.
        
        $all_records = $data->records;
        $offset = isset( $data->offset ) ? $data->offset : null;

        while ( $offset ) {
            $paged_url = add_query_arg( 'offset', $offset, $url );
            $paged_response = wp_remote_get( $paged_url, $args );
            
            if ( is_wp_error( $paged_response ) || wp_remote_retrieve_response_code( $paged_response ) !== 200 ) {
                break; // Stop on error
            }
            
            $paged_body = wp_remote_retrieve_body( $paged_response );
            $paged_data = json_decode( $paged_body );
            
            if ( ! empty( $paged_data->records ) ) {
                $all_records = array_merge( $all_records, $paged_data->records );
            }
            
            $offset = isset( $paged_data->offset ) ? $paged_data->offset : null;
        }

		return $all_records;
	}
}
