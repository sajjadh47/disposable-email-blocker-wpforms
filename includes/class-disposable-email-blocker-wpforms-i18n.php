<?php
/**
 * This file contains the definition of the Disposable_Email_Blocker_Wpforms_I18n class, which
 * is used to load the plugin's internationalization.
 *
 * @package       Disposable_Email_Blocker_Wpforms
 * @subpackage    Disposable_Email_Blocker_Wpforms/includes
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since    2.0.0
 */
class Disposable_Email_Blocker_Wpforms_I18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'disposable-email-blocker-wpforms',
			false,
			dirname( DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
