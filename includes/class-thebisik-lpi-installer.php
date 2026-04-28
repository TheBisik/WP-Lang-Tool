<?php
/**
 * Installer Service Class
 *
 * @package TheBisik_LPI
 */

namespace TheBisik\LPI;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the logic for downloading and installing language packs
 * via the native WordPress Language_Pack_Upgrader API.
 */
class Installer {

	/**
	 * Get the list of all available translations from WordPress.org.
	 *
	 * @return array Associative array of available translations keyed by locale.
	 */
	public function get_available_translations() {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';

		$translations = wp_get_available_translations();
		return $translations ? $translations : array();
	}

	/**
	 * Installs a single language pack.
	 *
	 * Uses the native Language_Pack_Upgrader with Automatic_Upgrader_Skin
	 * to perform a silent installation without dumping raw HTML to the screen.
	 *
	 * @param string $locale Selected locale string (e.g., 'pl_PL', 'fr_FR').
	 * @return array Result array with 'success' boolean and 'message' string.
	 */
	public function install_language( $locale ) {
		if ( empty( $locale ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid locale provided.', 'thebisik-language-pack-installer' ),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Automatic_Upgrader_Skin suppresses raw HTML output — suitable for AJAX context.
		$skin     = new \Automatic_Upgrader_Skin();
		$upgrader = new \Language_Pack_Upgrader( $skin );

		wp_clean_update_cache();

		$installed_locales = get_available_languages();
		$available         = $this->get_available_translations();

		if ( in_array( $locale, $installed_locales, true ) ) {
			return array(
				'success' => true,
				'message' => __( 'Already installed.', 'thebisik-language-pack-installer' ),
			);
		}

		if ( ! isset( $available[ $locale ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Language not available for installation.', 'thebisik-language-pack-installer' ),
			);
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
			return array(
				'success' => false,
				'message' => __( 'Installation failed.', 'thebisik-language-pack-installer' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Installed successfully.', 'thebisik-language-pack-installer' ),
		);
	}

	/**
	 * Uninstalls a single language pack and handles user/site fallback to English.
	 *
	 * If the locale being deleted is the active site language, it resets WPLANG
	 * to an empty string (which defaults WordPress to English). Also clears the
	 * locale from any user profiles that had it selected.
	 *
	 * @param string $locale The locale to uninstall (e.g., 'pl_PL').
	 * @return array Result array with 'success' boolean and 'message' string.
	 */
	public function uninstall_language( $locale ) {
		if ( empty( $locale ) || 'en_US' === $locale ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid locale or cannot delete English.', 'thebisik-language-pack-installer' ),
			);
		}

		// 1. Check site language and fall back to English if needed.
		if ( get_option( 'WPLANG' ) === $locale ) {
			update_option( 'WPLANG', '' ); // Empty string defaults to English (en_US).
		}

		// 2. Find users who are using this language and reset them to site default.
		delete_metadata( 'user', 0, 'locale', $locale, true );

		// 3. Remove the physical language files from WP_LANG_DIR.
		$lang_dir = WP_LANG_DIR;
		if ( ! is_dir( $lang_dir ) ) {
			return array(
				'success' => false,
				'message' => __( 'Language directory not found.', 'thebisik-language-pack-installer' ),
			);
		}

		$deleted_files = 0;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $lang_dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$filename = $file->getFilename();
				// Support deleting *-$locale.mo, $locale.mo, *-$locale.po, .json, .l10n.php, etc.
				if ( preg_match( '/(?:^|-)' . preg_quote( $locale, '/' ) . '\.(mo|po|l10n\.php|json)$/i', $filename ) ) {
					if ( wp_delete_file( $file->getPathname() ) ) {
						$deleted_files++;
					}
				}
			}
		}

		// Refresh the update transient cache after deletion.
		wp_clean_update_cache();

		return array(
			'success' => true,
			/* translators: %d: number of deleted language files */
			'message' => sprintf( __( 'Deleted %d language files.', 'thebisik-language-pack-installer' ), $deleted_files ),
		);
	}
}
