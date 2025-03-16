<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @package    Disposable_Email_Blocker_Wpforms
 * @subpackage Disposable_Email_Blocker_Wpforms/public
 * @author     Sajjad Hossain Sagor <sagorh672@gmail.com>
 */
class Disposable_Email_Blocker_Wpforms_Public
{
	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_name     The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $version    		The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @param    string    $plugin_name   	The name of the plugin.
	 * @param    string    $version   		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{
		$this->plugin_name 	= $plugin_name;
		
		$this->version 		= $version;
	}

	/**
	 * Blocks disposable emails submitted via WPForms.
	 *
	 * This function checks if the submitted email address belongs to a disposable
	 * email domain and adds an error to the WPForms processing if it does.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @param    int    $field_id     The ID of the field being validated.
	 * @param    string $field_submit The submitted value of the field.
	 * @param    array  $form_data    The current WPForms form data array.
	 * @return   void
	 */
	function wpforms_process_validate_email( $field_id, $field_submit, $form_data )
	{
		global $wpdb;
		
		// if not blocking is enabled return early	
		if ( empty( $form_data['settings']['block_disposable_emails'] ) || $form_data['settings']['block_disposable_emails'] !== '1' ) return;
		
		if( filter_var( $field_submit, FILTER_VALIDATE_EMAIL ) )
		{
			// split on @ and return last value of array (the domain)
			$domain 						= explode( '@', sanitize_email( $field_submit ) );
			
			$domain 						= array_pop( $domain );

			$found 							= false;
			
			$table_name 					= $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_TABLE_NAME;

			$txt_file 						= DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_PATH . '/admin/data/domains.txt';

			// Check if the table exists
			$table_exists 					= $wpdb->get_var(
				$wpdb->prepare(
					"SHOW TABLES LIKE %s",
					$table_name
				)
			);

			if ( $table_exists )
			{
				// Look for the domain in the database
				$found 						= (bool) $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE domain = %s",
					$domain
				) );
			}
			else
			{
				// If not found the table and file exists, fall back to txt
				if ( file_exists( $txt_file ) )
				{
					// Get domains list from the txt file
					$disposable_domains 	= explode( "\n", file_get_contents( $txt_file ) );

					if ( is_array( $disposable_domains ) && in_array( $domain, $disposable_domains ) )
					{
						$found = true;
					}
				}
			}

			// If found in DB or txt, invalidate the result
			if ( $found )
			{
				wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = $form_data['settings']['disposable_emails_found_msg'];	
			}
		}
	}
}
