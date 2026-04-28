<?php
/**
 * Plugin Name:       TheBisik Language Pack Installer
 * Plugin URI:        https://thebisik-language-pack-installer.bartonsky.pl/
 * Description:       A tool for bulk installation of language packs without changing the site's global language.
 * Version:           1.0.0
 * Author:            Fabian Baranski
 * Author URI:        https://github.com/TheBisik
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       thebisik-language-pack-installer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'THEBISIK_LPI_VERSION', '1.0.0' );
define( 'THEBISIK_LPI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'THEBISIK_LPI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the core plugin classes (Installer and Admin).
 *
 * The Installer handles downloading and removing language packs.
 * The Admin class registers menus, AJAX endpoints, and renders the UI.
 */
require_once THEBISIK_LPI_PLUGIN_DIR . 'includes/class-thebisik-lpi-installer.php';
require_once THEBISIK_LPI_PLUGIN_DIR . 'includes/class-thebisik-lpi-admin.php';

/**
 * Begins execution of the plugin.
 *
 * Instantiates the Installer and Admin objects, then calls Admin::init()
 * to register all WordPress hooks.
 *
 * @return void
 */
function thebisik_lpi_run() {
	$installer = new TheBisik\LPI\Installer();
	$admin     = new TheBisik\LPI\Admin( $installer );
	$admin->init();
}

add_action( 'plugins_loaded', 'thebisik_lpi_run' );

/**
 * Enqueue scripts and styles for the plugin admin page.
 *
 * Only loads assets on the plugin's own Tools sub-page to avoid
 * polluting other admin screens.
 *
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function thebisik_lpi_enqueue_admin_scripts( $hook ) {
	// Only load on our plugin page: Tools -> TheBisik Language Pack Installer.
	if ( 'tools_page_thebisik-language-pack-installer' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'thebisik-lpi-admin-script',
		THEBISIK_LPI_PLUGIN_URL . 'assets/js/admin-script.js',
		array( 'jquery' ),
		THEBISIK_LPI_VERSION,
		true
	);

	wp_localize_script(
		'thebisik-lpi-admin-script',
		'thebisikLpiData',
		array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'thebisik_lpi_ajax_nonce' ),
			'textInstalling'      => esc_html__( 'Installing...', 'thebisik-language-pack-installer' ),
			'textSuccess'         => esc_html__( 'Success', 'thebisik-language-pack-installer' ),
			'textError'           => esc_html__( 'Error', 'thebisik-language-pack-installer' ),
			'textNotInstalled'    => esc_html__( 'Not Installed', 'thebisik-language-pack-installer' ),
			'textInstalled'       => esc_html__( 'Installed', 'thebisik-language-pack-installer' ),
			'textDeleteConfirm'   => esc_html__( 'Are you sure you want to delete this language?', 'thebisik-language-pack-installer' ),
			'textDeleting'        => esc_html__( 'Deleting...', 'thebisik-language-pack-installer' ),
			'textDeleteAllConfirm' => esc_html__( 'Are you sure you want to delete ALL installed languages (except English)?', 'thebisik-language-pack-installer' ),
			'textBulkDeleteConfirm' => esc_html__( 'Are you sure you want to delete the selected languages?', 'thebisik-language-pack-installer' ),
		)
	);
}

add_action( 'admin_enqueue_scripts', 'thebisik_lpi_enqueue_admin_scripts' );
