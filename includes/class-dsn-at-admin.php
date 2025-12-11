<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSN_AT_Admin {

	private $api;
	private $database;

	public function __construct( $api, $database ) {
		$this->api      = $api;
		$this->database = $database;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_dsn_at_sync', array( $this, 'handle_sync' ) );
        add_action( 'wp_ajax_dsn_at_get_products', array( $this, 'handle_get_products' ) );
        add_action( 'wp_ajax_dsn_at_fetch_tables', array( $this, 'handle_fetch_tables' ) );
	}
    
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_dsn-airtable-connector' !== $hook ) {
            return;
        }
        wp_enqueue_script( 'dsn-at-admin-js', DSN_AT_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), DSN_AT_VERSION, true );
    }

	public function add_admin_menu() {
		add_menu_page(
			'DSN Airtable Connector',
			'Airtable Connector',
			'manage_options',
			'dsn-airtable-connector',
			array( $this, 'render_settings_page' ),
			'dashicons-cloud',
			100
		);
	}

	public function register_settings() {
		register_setting( 'dsn_at_settings_group', 'dsn_at_api_key' );
		register_setting( 'dsn_at_settings_group', 'dsn_at_base_id' );
		register_setting( 'dsn_at_settings_group', 'dsn_at_table_name' );

		add_settings_section(
			'dsn_at_settings_section',
			'API Configuration',
			null,
			'dsn-airtable-connector'
		);

		add_settings_field(
			'dsn_at_api_key',
			'API Key',
			array( $this, 'render_api_key_field' ),
			'dsn-airtable-connector',
			'dsn_at_settings_section'
		);

		add_settings_field(
			'dsn_at_base_id',
			'Base ID',
			array( $this, 'render_base_id_field' ),
			'dsn-airtable-connector',
			'dsn_at_settings_section'
		);

		add_settings_field(
			'dsn_at_table_name',
			'Table Name',
			array( $this, 'render_table_name_field' ),
			'dsn-airtable-connector',
			'dsn_at_settings_section'
		);
	}

	public function render_api_key_field() {
		$value = get_option( 'dsn_at_api_key' );
		echo '<input type="password" name="dsn_at_api_key" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	public function render_base_id_field() {
		$value = get_option( 'dsn_at_base_id' );
		echo '<input type="text" name="dsn_at_base_id" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	public function render_table_name_field() {
		$value = get_option( 'dsn_at_table_name' );
        ?>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="dsn_at_table_name" name="dsn_at_table_name" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="Products">
            <button type="button" id="dsn-at-load-tables-btn" class="button button-secondary">Load Tables</button>
            <span id="dsn-at-load-tables-spinner" class="spinner"></span>
        </div>
        <div id="dsn-at-tables-dropdown-container" style="margin-top: 5px; display: none;">
            <select id="dsn-at-tables-dropdown" style="max-width: 100%;">
                <option value="">Select a table...</option>
            </select>
        </div>
        <p class="description">Enter the table name or click "Load Tables" to pick from a list (requires `schema.bases:read` scope).</p>
        <?php
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>DSN Airtable Connector Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'dsn_at_settings_group' );
				do_settings_sections( 'dsn-airtable-connector' );
				submit_button();
				?>
			</form>
            <hr>
            <h2>Sync Data</h2>
            <button id="dsn-at-sync-btn" class="button button-primary">Sync Now</button>
            <div id="dsn-at-sync-status" style="margin-top: 10px;"></div>
            
            <hr>
            <h2>Synced Products</h2>
            <div id="dsn-at-products-table">
                <!-- Table will be loaded here -->
            </div>
		</div>
		<?php
	}

    public function handle_sync() {
        // Fetch data
        $result = $this->api->fetch_data();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // Save data
        if ( ! empty( $result ) ) {
            $this->database->save_products( $result );
            wp_send_json_success( 'Synced ' . count( $result ) . ' records.' );
        } else {
            wp_send_json_error( 'No data found.' );
        }
    }

    public function handle_get_products() {
        $products = $this->database->get_products();
        
        if ( empty( $products ) ) {
            wp_send_json_success( '<p>No products found.</p>' );
        }

        ob_start();
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Airtable ID</th>
                    <th>Data</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $products as $product ) : ?>
                    <tr>
                        <td><?php echo esc_html( $product->id ); ?></td>
                        <td><?php echo esc_html( $product->airtable_id ); ?></td>
                        <td><?php echo esc_html( mb_strimwidth( $product->data, 0, 100, '...' ) ); ?></td>
                        <td><?php echo esc_html( $product->updated_at ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }

    public function handle_fetch_tables() {
        $tables = $this->api->fetch_tables();
        
        if ( is_wp_error( $tables ) ) {
            wp_send_json_error( $tables->get_error_message() );
        }
        
        wp_send_json_success( $tables );
    }
}
