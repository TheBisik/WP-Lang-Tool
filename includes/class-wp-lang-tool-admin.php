<?php
/**
 * Admin Panel Class
 *
 * @package WP_Lang_Tool
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the wp-admin pages and functionality.
 */
class WP_Lang_Tool_Admin {

	/**
	 * The installer instance.
	 *
	 * @var WP_Lang_Tool_Installer
	 */
	private $installer;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param WP_Lang_Tool_Installer $installer
	 */
	public function __construct( $installer ) {
		$this->installer = $installer;
	}

	/**
	 * Register hooks for admin area.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		
		// Register AJAX endpoints
		add_action( 'wp_ajax_wplt_install_language', array( $this, 'ajax_install_language' ) );
		add_action( 'wp_ajax_wplt_uninstall_language', array( $this, 'ajax_uninstall_language' ) );
	}

	/**
	 * Register the administration menu.
	 */
	public function add_plugin_admin_menu() {
		// Capability check for security
		add_management_page(
			__( 'WP Lang Tool', 'wp-lang-tool' ),
			__( 'WP Lang Tool', 'wp-lang-tool' ),
			'install_languages',
			'wp-lang-tool',
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * AJAX logic for installing a single language pack.
	 */
	public function ajax_install_language() {
		// 1. Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wplt_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid security token.', 'wp-lang-tool' ) ) );
		}

		// 2. Capabilities check
		if ( ! current_user_can( 'install_languages' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'wp-lang-tool' ) ) );
		}

		// 3. Get Locale
		$locale = isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : '';

		// 4. Perform Installation
		$result = $this->installer->install_language( $locale );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX logic for uninstalling a single language pack.
	 */
	public function ajax_uninstall_language() {
		// 1. Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wplt_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid security token.', 'wp-lang-tool' ) ) );
		}

		// 2. Capabilities check
		if ( ! current_user_can( 'install_languages' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'wp-lang-tool' ) ) );
		}

		// 3. Get Locale
		$locale = isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : '';

		// 4. Perform Delete
		$result = $this->installer->uninstall_language( $locale );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Render the admin page HTML.
	 */
	public function display_plugin_admin_page() {
		// Get lists
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$available_translations = wp_get_available_translations();
		$installed_languages    = get_available_languages();

		?>
		<div class="wrap wplt-wrap">
			<h1><?php esc_html_e( 'WP Lang Tool', 'wp-lang-tool' ); ?></h1>
			<p><?php esc_html_e( 'Bulk install or delete language packs from the official WordPress.org repository via asynchronous, one-by-one tasks.', 'wp-lang-tool' ); ?></p>

			<!-- Progress summary message -->
			<div id="wplt-progress-summary" class="notice notice-info inline" style="display:none; padding:10px;">
				<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span>
				<strong class="wplt-progress-text"></strong>
			</div>

			<form id="wplt-bulk-form" method="post" action="">
				<table class="widefat striped wp-list-table">
					<thead>
						<tr>
							<th scope="col" class="manage-column check-column">
								<input type="checkbox" id="wplt-select-all" />
							</th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Language Name', 'wp-lang-tool' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Native Name', 'wp-lang-tool' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'ISO Code', 'wp-lang-tool' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wp-lang-tool' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'wp-lang-tool' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $available_translations ) ) {
							foreach ( $available_translations as $locale => $translation ) {
								$is_installed = in_array( $locale, $installed_languages, true );
								?>
								<tr class="wplt-row" data-locale="<?php echo esc_attr( $locale ); ?>">
									<th scope="row" class="check-column">
										<input type="checkbox" 
											   class="wplt-checkbox" 
											   name="selected_locales[]" 
											   value="<?php echo esc_attr( $locale ); ?>" 
											   <?php disabled( 'en_US' === $locale, true ); ?> />
									</th>
									<td><?php echo esc_html( $translation['english_name'] ); ?></td>
									<td><?php echo esc_html( $translation['native_name'] ); ?></td>
									<td><code><?php echo esc_html( $locale ); ?></code></td>
									<td class="wplt-status-cell">
										<?php 
										if ( $is_installed ) {
											echo '<span style="color: #46b450; font-weight: 600;" class="wplt-status-text">' . esc_html__( 'Installed', 'wp-lang-tool' ) . '</span>';
										} else {
											echo '<span class="wplt-status-text">' . esc_html__( 'Not Installed', 'wp-lang-tool' ) . '</span>';
										}
										?>
										<span class="spinner wplt-spinner"></span>
									</td>
									<td class="wplt-actions-cell">
										<?php
										if ( $is_installed && 'en_US' !== $locale ) {
											echo '<button type="button" class="button button-link-delete wplt-delete-btn" data-locale="' . esc_attr( $locale ) . '">' . esc_html__( 'Delete', 'wp-lang-tool' ) . '</button>';
										}
										?>
									</td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'We were unable to fetch the available languages. Please check your internet connection.', 'wp-lang-tool' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<p class="submit">
					<button type="button" id="wplt-submit-btn" class="button button-primary"><?php esc_html_e( 'Bulk Install Selected', 'wp-lang-tool' ); ?></button>
					<button type="button" id="wplt-bulk-delete-btn" class="button"><?php esc_html_e( 'Bulk Delete Selected', 'wp-lang-tool' ); ?></button>
					<button type="button" id="wplt-delete-all-btn" class="button" style="color:#d63638; border-color:#d63638;"><?php esc_html_e( 'Delete All Installed', 'wp-lang-tool' ); ?></button>
				</p>
			</form>
		</div>

		<script>
		// Select-all UI script only, rest is in admin-script.js
		document.addEventListener('DOMContentLoaded', function() {
			var selectAll = document.getElementById('wplt-select-all');
			if(selectAll) {
				selectAll.addEventListener('change', function() {
					var checkboxes = document.querySelectorAll('.wplt-checkbox:not(:disabled)');
					for (var i = 0; i < checkboxes.length; i++) {
						checkboxes[i].checked = selectAll.checked;
					}
				});
			}
		});
		</script>
		<?php
	}
}

