# DSN Airtable WP Connector

Connects to Airtable to fetch product information and store it in a custom local database table.

## Features

-   **Manual Sync**: Triggered via admin interface.
-   **Automated Sync**: Automatically syncs data daily via WordPress Cron.
-   **Data Storage**: Stores raw Airtable fields as JSON in `airtable_products` table.
-   **Flexible Configuration**: Configure API Key, Base ID, and Table Name.
-   **Auto-Updates**: Supports automatic updates from GitHub Releases.

## Installation

1.  Download the latest `.zip` from the [Releases](https://github.com/DesignStudio-Dev-Team/dsn-airtable-wp-connector/releases) page.
2.  Upload to your WordPress Plugins (`/wp-content/plugins/`).
3.  Activate the plugin.

## Configuration

1.  Navigate to **Airtable Connector** in the admin menu.
2.  Enter your **Airtable Personal Access Token (API Key)**.
    -   *Required Scopes*: `data.records:read`, `schema.bases:read`
3.  Enter your **Base ID** (e.g., `app123...`).
4.  Click **Load Tables** to select your table, or type the name manually.
5.  Save Changes.

## Development

-   **Author**: DesignStudio Network, Inc.
-   **License**: GPL2

## Changelog

### 1.0.0
-   Initial release with Manual/Auto sync and Admin UI.
