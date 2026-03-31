<?php
/**
 * Installer Service Class
 *
 * @package WP_Lang_Tool
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the logic for downloading and installing language packs.
 */
class WP_Lang_Tool_Installer {

	/**
	 * Get the list of all available translations from WordPress.org.
	 *
	 * @return array
	 */
	public function get_available_translations() {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		
		$translations = wp_get_available_translations();
		return $translations ? $translations : array();
	}

	/**
	 * Installs a single language pack.
	 *
	 * @param string $locale Selected locale string (e.g., 'pl_PL', 'fr_FR').
	 * @return array Result array with success boolean and message.
	 */
	public function install_language( $locale ) {
		if ( empty( $locale ) ) {
			return array( 'success' => false, 'message' => __( 'Invalid locale provided.', 'wp-lang-tool' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// We use the Automatic Upgrader Skin to prevent the upgrader from dumping raw HTML output to the screen.
		// Actually, WP_Ajax_Upgrader_Skin is better for AJAX, but Automatic_Upgrader_Skin works silently.
		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new Language_Pack_Upgrader( $skin );

		wp_clean_update_cache();
		
		$installed_locales = get_available_languages();
		$available = $this->get_available_translations();

		if ( in_array( $locale, $installed_locales, true ) ) {
			return array( 'success' => true, 'message' => __( 'Already installed.', 'wp-lang-tool' ) );
		}

		if ( ! isset( $available[ $locale ] ) ) {
			return array( 'success' => false, 'message' => __( 'Language not available for installation.', 'wp-lang-tool' ) );
		}

		$language_update = (object) array(
			'type'       => 'core',
			'slug'       => 'default',
			'language'   => $locale,
			'version'    => $available[ $locale ]['version'],
			'updated'    => $available[ $locale ]['updated'],
			'package'    => $available[ $locale ]['package'],
			'autoupdate' => true,
		);

		$result = $upgrader->upgrade( $language_update );

		if ( is_wp_error( $result ) || ! $result ) {
			return array( 'success' => false, 'message' => __( 'Installation failed.', 'wp-lang-tool' ) );
		}

		return array( 'success' => true, 'message' => __( 'Installed successfully.', 'wp-lang-tool' ) );
	}

	/**
	 * Uninstalls a single language pack and handles user/site fallback to English.
	 *
	 * @param string $locale The locale to uninstall.
	 * @return array Result array with success boolean and message.
	 */
	public function uninstall_language( $locale ) {
		if ( empty( $locale ) || 'en_US' === $locale ) {
			return array( 'success' => false, 'message' => __( 'Invalid locale or cannot delete English.', 'wp-lang-tool' ) );
		}

		// 1. Check Site Language and fallback to English
		if ( get_option( 'WPLANG' ) === $locale ) {
			update_option( 'WPLANG', '' ); // Empty string defaults to English (en_US)
		}

		// 2. Find users who are using this language and reset them to default (English or site default)
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'locale' AND meta_value = %s", $locale ) );

		// 3. Remove the language files
		$lang_dir = WP_LANG_DIR;
		if ( ! is_dir( $lang_dir ) ) {
			return array( 'success' => false, 'message' => __( 'Language directory not found.', 'wp-lang-tool' ) );
		}

		$deleted_files = 0;

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $lang_dir, \FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$filename = $file->getFilename();
				// Support deleting *-$locale.mo, $locale.mo, *-$locale.po, etc.
				if ( preg_match( '/(?:^|-)' . preg_quote( $locale, '/' ) . '\.(mo|po|l10n\.php|json)$/i', $filename ) ) {
					if ( @unlink( $file->getPathname() ) ) {
						$deleted_files++;
					}
				}
			}
		}

		// Refresh transient cache
		wp_clean_update_cache();

		return array( 'success' => true, 'message' => sprintf( __( 'Deleted %d language files.', 'wp-lang-tool' ), $deleted_files ) );
	}
}
