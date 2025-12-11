<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSN_AT_Updater {

	private $plugin_slug;
	private $version;
	private $cache_key;
	private $cache_allowed;

	const GITHUB_USER = 'DesignStudio-Dev-Team';
	const GITHUB_REPO = 'dsn-airtable-wp-connector'; 

	public function __construct() {
		$this->plugin_slug   = plugin_basename( DSN_AT_PLUGIN_DIR . 'dsn-airtable-wp-connector.php' );
		$this->version       = DSN_AT_VERSION;
		$this->cache_key     = 'dsn_at_updater_cache';
		$this->cache_allowed = false; // Set to true to enable caching

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
	}

	public function request() {
		$remote = get_transient( $this->cache_key );

		if ( false === $remote || ! $this->cache_allowed ) {
			$remote = wp_remote_get(
				'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest',
				array(
					'headers' => array(
						'Accept' => 'application/vnd.github.v3+json',
					),
				)
			);

			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}

			$payload = json_decode( wp_remote_retrieve_body( $remote ) );

			if ( ! empty( $payload ) && isset( $payload->tag_name ) ) {
				$res = new stdClass();
				$res->tag_name     = $payload->tag_name;
				$res->version      = $res->tag_name; // Should be 'v1.0.0' or '1.0.0'
				$res->download_url = $payload->assets[0]->browser_download_url; // Assuming first asset is zip
				$res->author       = 'DesignStudio Network, Inc.'; 
				$res->requires     = '6.0';
				$res->tested       = '6.7';
				$res->last_updated = $payload->published_at;
				
				// Sections for the details popup
				$res->sections = array(
					'description' => $payload->body, // Use release notes as description
				);

				set_transient( $this->cache_key, $res, HOUR_IN_SECONDS );
				$remote = $res;
			} else {
				return false;
			}
		}

		return $remote;
	}

	public function info( $res, $action, $args ) {
		// do nothing if this is not about getting plugin information
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		// do nothing if it is not our plugin
		if ( $this->plugin_slug !== $args->slug ) {
			return $res;
		}

		$remote = $this->request();

		if ( ! $remote ) {
			return $res;
		}

		$res = new stdClass();
		$res->name           = 'DSN Airtable WP Connector';
		$res->slug           = $this->plugin_slug;
		$res->version        = $remote->version;
		$res->tested         = $remote->tested;
		$res->requires       = $remote->requires;
		$res->author         = $remote->author;
		$res->download_link  = $remote->download_url;
		$res->trunk          = $remote->download_url;
		$res->last_updated   = $remote->last_updated;
		$res->sections       = array(
			'description'  => $remote->sections['description'],
			'installation' => 'Install via zip upload or Git.',
			'changelog'    => $remote->sections['description'],
		);

		return $res;
	}

	public function update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if ( $remote && version_compare( $this->version, $remote->version, '<' ) ) {
			$res = new stdClass();
			$res->slug = $this->plugin_slug;
			$res->plugin = $this->plugin_slug;
			$res->new_version = $remote->version;
			$res->url = 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
			$res->package = $remote->download_url;

			$transient->response[ $this->plugin_slug ] = $res;
		}

		return $transient;
	}

	public function purge( $upgrader, $options ) {
		if (
			$this->cache_allowed &&
			'update' === $options['action'] &&
			'plugin' === $options[ 'type' ]
		) {
			// clean the cache when new plugin version is installed
			delete_transient( $this->cache_key );
		}
	}
}
