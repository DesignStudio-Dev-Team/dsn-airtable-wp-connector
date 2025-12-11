<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSN_AT_Scheduler {

	private $api;
	private $database;

	public function __construct( $api, $database ) {
		$this->api      = $api;
		$this->database = $database;
	}

	public function init() {
		add_action( 'dsn_at_daily_sync', array( $this, 'run_sync' ) );
	}

	public function run_sync() {
		// Fetch data
		$result = $this->api->fetch_data();

		if ( is_wp_error( $result ) ) {
			error_log( 'DSN Airtable Connector Cron Error: ' . $result->get_error_message() );
			return;
		}

		// Save data
		if ( ! empty( $result ) ) {
			$this->database->save_products( $result );
			error_log( 'DSN Airtable Connector Cron Success: Synced ' . count( $result ) . ' records.' );
		} else {
			error_log( 'DSN Airtable Connector Cron Info: No data found to sync.' );
		}
	}
}
