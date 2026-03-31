<?php
/**
 * Plugin Name:       WP Lang Tool
 * Plugin URI:        https://wp-lang-tool.bartonsky.pl
 * Description:       A tool for bulk installation of language packs without changing the site's global language.
 * Version:           1.0.0
 * Author:            Fabian Baranski
 * Author URI:        https://github.com/TheBisik
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-lang-tool
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WP_LANG_TOOL_VERSION', '1.0.0' );
define( 'WP_LANG_TOOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_LANG_TOOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once WP_LANG_TOOL_PLUGIN_DIR . 'includes/class-wp-lang-tool-installer.php';
require_once WP_LANG_TOOL_PLUGIN_DIR . 'includes/class-wp-lang-tool-admin.php';

/**
 * Begins execution of the plugin.
 */
function run_wp_lang_tool() {
	$installer = new WP_Lang_Tool_Installer();
	$admin     = new WP_Lang_Tool_Admin( $installer );
	$admin->init();
}

add_action( 'plugins_loaded', 'run_wp_lang_tool' );

/**
 * Enqueue scripts for the admin page.
 *
 * @param string $hook The current admin page.
 */
function wplt_enqueue_admin_scripts( $hook ) {
	// Only load on our plugin page.
	if ( 'tools_page_wp-lang-tool' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'wplt-admin-script',
		WP_LANG_TOOL_PLUGIN_URL . 'assets/js/admin-script.js',
		array(),
		WP_LANG_TOOL_VERSION,
		true
	);

	wp_localize_script(
		'wplt-admin-script',
		'wpltData',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'wplt_ajax_nonce' ),
			'textInstalling'    => esc_html__( 'Installing...', 'wp-lang-tool' ),
			'textSuccess'       => esc_html__( 'Success', 'wp-lang-tool' ),
			'textError'         => esc_html__( 'Error', 'wp-lang-tool' ),
			'textNotInstalled'  => esc_html__( 'Not Installed', 'wp-lang-tool' ),
			'textInstalled'     => esc_html__( 'Installed', 'wp-lang-tool' ),
			'textDeleteConfirm' => esc_html__( 'Are you sure you want to delete this language?', 'wp-lang-tool' ),
			'textDeleting'      => esc_html__( 'Deleting...', 'wp-lang-tool' ),
			'textDeleteAllConfirm' => esc_html__( 'Are you sure you want to delete ALL installed languages (except English)?', 'wp-lang-tool' ),
			'textBulkDeleteConfirm' => esc_html__( 'Are you sure you want to delete the selected languages?', 'wp-lang-tool' ),
		)
	);
}

add_action( 'admin_enqueue_scripts', 'wplt_enqueue_admin_scripts' );