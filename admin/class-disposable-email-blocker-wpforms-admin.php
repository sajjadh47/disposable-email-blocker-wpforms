<?php
/**
 * This file contains the definition of the Disposable_Email_Blocker_Wpforms_Admin class, which
 * is used to load the plugin's admin-specific functionality.
 *
 * @package       Disposable_Email_Blocker_Wpforms
 * @subpackage    Disposable_Email_Blocker_Wpforms/admin
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @since    2.0.0
 */
class Disposable_Email_Blocker_Wpforms_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     string $plugin_name The name of this plugin.
	 * @param     string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Adds a settings link to the plugin's action links on the plugin list table.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $links The existing array of plugin action links.
	 * @return    array $links The updated array of plugin action links, including the settings link.
	 */
	public function add_plugin_action_links( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=wpforms-overview' ) ), __( 'Settings', 'disposable-email-blocker-wpforms' ) );

		return $links;
	}

	/**
	 * Displays admin notices in the admin area.
	 *
	 * This function checks if the required plugin is active.
	 * If not, it displays a warning notice and deactivates the current plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function admin_notices() {
		// Check if required plugin is active.
		if ( ! class_exists( 'WPForms', false ) ) {
			sprintf(
				'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a> %s</p></div>',
				__( 'Disposable Email Blocker - WPForms requires', 'disposable-email-blocker-wpforms' ),
				esc_url( 'https://wordpress.org/plugins/wpforms-lite/' ),
				__( 'WPForms', 'disposable-email-blocker-wpforms' ),
				__( 'plugin to be active!', 'disposable-email-blocker-wpforms' ),
			);

			// Deactivate the plugin.
			deactivate_plugins( DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_BASENAME );
		}

		// Get current db version and if needed update domains list.
		$current_db_version = get_option( 'debwpforms_db_version', false );

		if ( DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_DB_VERSION !== $current_db_version ) {
			if ( ! wp_next_scheduled( 'wpforms_create_disposable_email_domains_table' ) ) {
				wp_schedule_single_event( time() + 10, 'wpforms_create_disposable_email_domains_table' );
			}

			update_option( 'debwpforms_db_version', DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_DB_VERSION );
		}
	}

	/**
	 * Adds custom settings to the WPForms form settings general tab for disposable email blocking.
	 *
	 * This function adds a checkbox to enable/disable disposable email blocking and a text
	 * field to customize the error message displayed when a disposable email is detected
	 * within the general settings of a WPForms form.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     object $form The current WPForms form object.
	 * @return    void
	 */
	public function wpforms_form_settings_general( $form ) {
		wpforms_panel_field(
			'toggle',
			'settings',
			'block_disposable_emails',
			$form->form_data,
			esc_html__( 'Block Disposable/Temporary Emails', 'disposable-email-blocker-wpforms' ),
			array(
				'tooltip' => esc_html__( 'Enables blocking disposable and temporary emails from submitting.', 'disposable-email-blocker-wpforms' ),
			)
		);

		wpforms_panel_field(
			'text',
			'settings',
			'disposable_emails_found_msg',
			$form->form_data,
			esc_html__( 'Disposable Email Found Error Text', 'disposable-email-blocker-wpforms' ),
			array(
				'default' => esc_html__( 'Disposable/Temporary emails are not allowed! Please use a non temporary email', 'disposable-email-blocker-wpforms' ),
			)
		);
	}

	/**
	 * Handles plugin table creation task.
	 *
	 * This function is called when the plugin is activated using cron. It creates the
	 * necessary database table to store disposable email domains and populates it
	 * with data from a txt file.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    void
	 */
	public function create_disposable_email_domains_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_TABLE_NAME;
		$txt_file   = DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_PATH . '/public/data/domains.txt';

		if ( ! file_exists( $txt_file ) ) {
			return;
		}

		// Create table if it doesn't exist.
		$charset_collate = $wpdb->get_charset_collate();

		$sql =
		"CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			domain VARCHAR(255) NOT NULL UNIQUE
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Get domains list from txt file.
		$txt_file_content   = $wp_filesystem->get_contents( $txt_file );
		$disposable_domains = explode( "\n", $txt_file_content );

		if ( ! empty( $disposable_domains ) && is_array( $disposable_domains ) ) {
			foreach ( $disposable_domains as $domain ) {
				// Insert or update domains.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->replace(
					$table_name,
					array( 'domain' => sanitize_text_field( $domain ) ),
					array( '%s' )
				);
			}
		}
	}
}
