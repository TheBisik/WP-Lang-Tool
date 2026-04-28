<?php
/**
 * Admin Panel Class
 *
 * @package TheBisik_LPI
 */

namespace TheBisik\LPI;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handles the wp-admin pages, menus, and AJAX endpoints
 * for the TheBisik Language Pack Installer plugin.
 */
class Admin
{

	/**
	 * The installer service instance.
	 *
	 * @var Installer
	 */
	private $installer;

	/**
	 * Initialize the class and inject the Installer dependency.
	 *
	 * @param Installer $installer Instance of the Installer service class.
	 */
	public function __construct(Installer $installer)
	{
		$this->installer = $installer;
	}

	/**
	 * Register all WordPress hooks for the admin area.
	 *
	 * @return void
	 */
	public function init()
	{
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

		// Register AJAX endpoints (logged-in users only).
		add_action('wp_ajax_thebisik_lpi_install_language', array($this, 'ajax_install_language'));
		add_action('wp_ajax_thebisik_lpi_uninstall_language', array($this, 'ajax_uninstall_language'));
	}

	/**
	 * Register the plugin administration menu under Tools.
	 *
	 * @return void
	 */
	public function add_plugin_admin_menu()
	{
		add_management_page(
			__('TheBisik Language Pack Installer', 'thebisik-language-pack-installer'),
			__('TheBisik Language Pack Installer', 'thebisik-language-pack-installer'),
			'install_languages',
			'thebisik-language-pack-installer',
			array($this, 'display_plugin_admin_page')
		);
	}

	/**
	 * AJAX handler for installing a single language pack.
	 *
	 * Verifies the nonce and user capability before delegating
	 * to the Installer service.
	 *
	 * @return void Outputs JSON and exits.
	 */
	public function ajax_install_language()
	{
		// 1. Verify nonce.
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'thebisik_lpi_ajax_nonce')) {
			wp_send_json_error(array('message' => esc_html__('Invalid security token.', 'thebisik-language-pack-installer')));
		}

		// 2. Capability check.
		if (!current_user_can('install_languages')) {
			wp_send_json_error(array('message' => esc_html__('Insufficient permissions.', 'thebisik-language-pack-installer')));
		}

		// 3. Sanitize and retrieve locale.
		$locale = isset($_POST['locale']) ? sanitize_text_field(wp_unslash($_POST['locale'])) : '';

		// 4. Perform installation.
		$result = $this->installer->install_language($locale);

		if ($result['success']) {
			wp_send_json_success(array('message' => $result['message']));
		} else {
			wp_send_json_error(array('message' => $result['message']));
		}
	}

	/**
	 * AJAX handler for uninstalling a single language pack.
	 *
	 * Verifies the nonce and user capability before delegating
	 * to the Installer service.
	 *
	 * @return void Outputs JSON and exits.
	 */
	public function ajax_uninstall_language()
	{
		// 1. Verify nonce.
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'thebisik_lpi_ajax_nonce')) {
			wp_send_json_error(array('message' => esc_html__('Invalid security token.', 'thebisik-language-pack-installer')));
		}

		// 2. Capability check.
		if (!current_user_can('install_languages')) {
			wp_send_json_error(array('message' => esc_html__('Insufficient permissions.', 'thebisik-language-pack-installer')));
		}

		// 3. Sanitize and retrieve locale.
		$locale = isset($_POST['locale']) ? sanitize_text_field(wp_unslash($_POST['locale'])) : '';

		// 4. Perform deletion.
		$result = $this->installer->uninstall_language($locale);

		if ($result['success']) {
			wp_send_json_success(array('message' => $result['message']));
		} else {
			wp_send_json_error(array('message' => $result['message']));
		}
	}

	/**
	 * Render the admin page HTML.
	 *
	 * Fetches available and installed translations, then outputs the
	 * management table. No inline <script> tags are used here — all
	 * JavaScript is loaded via wp_enqueue_script() in the main plugin file.
	 *
	 * @return void
	 */
	public function display_plugin_admin_page()
	{
		// Load translation API helpers.
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$available_translations = wp_get_available_translations();
		$installed_languages = get_available_languages();

		?>
		<div class="wrap thebisik-lpi-wrap">
			<h1><?php esc_html_e('TheBisik Language Pack Installer', 'thebisik-language-pack-installer'); ?></h1>
			<p><?php esc_html_e('Bulk install or delete language packs from the official WordPress.org repository via asynchronous, one-by-one tasks.', 'thebisik-language-pack-installer'); ?>
			</p>

			<!-- Progress summary message -->
			<div id="thebisik-lpi-progress-summary" class="notice notice-info inline" style="display:none; padding:10px;">
				<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span>
				<strong class="thebisik-lpi-progress-text"></strong>
			</div>

			<form id="thebisik-lpi-bulk-form" method="post" action="">
				<table class="widefat striped wp-list-table">
					<thead>
						<tr>
							<th scope="col" class="manage-column check-column">
								<input type="checkbox" id="thebisik-lpi-select-all" />
							</th>
							<th scope="col" class="manage-column">
								<?php esc_html_e('Language Name', 'thebisik-language-pack-installer'); ?></th>
							<th scope="col" class="manage-column">
								<?php esc_html_e('Native Name', 'thebisik-language-pack-installer'); ?></th>
							<th scope="col" class="manage-column">
								<?php esc_html_e('ISO Code', 'thebisik-language-pack-installer'); ?></th>
							<th scope="col" class="manage-column">
								<?php esc_html_e('Status', 'thebisik-language-pack-installer'); ?></th>
							<th scope="col" class="manage-column">
								<?php esc_html_e('Actions', 'thebisik-language-pack-installer'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if (!empty($available_translations)) {
							foreach ($available_translations as $locale => $translation) {
								$is_installed = in_array($locale, $installed_languages, true);
								?>
								<tr class="thebisik-lpi-row" data-locale="<?php echo esc_attr($locale); ?>">
									<th scope="row" class="check-column">
										<input type="checkbox" class="thebisik-lpi-checkbox" name="selected_locales[]"
											value="<?php echo esc_attr($locale); ?>" <?php disabled('en_US' === $locale, true); ?> />
									</th>
									<td><?php echo esc_html($translation['english_name']); ?></td>
									<td><?php echo esc_html($translation['native_name']); ?></td>
									<td><code><?php echo esc_html($locale); ?></code></td>
									<td class="thebisik-lpi-status-cell">
										<?php
										if ($is_installed) {
											echo '<span style="color: #46b450; font-weight: 600;" class="thebisik-lpi-status-text">' . esc_html__('Installed', 'thebisik-language-pack-installer') . '</span>';
										} else {
											echo '<span class="thebisik-lpi-status-text">' . esc_html__('Not Installed', 'thebisik-language-pack-installer') . '</span>';
										}
										?>
										<span class="spinner thebisik-lpi-spinner"></span>
									</td>
									<td class="thebisik-lpi-actions-cell">
										<?php
										if ($is_installed && 'en_US' !== $locale) {
											echo '<button type="button" class="button button-link-delete thebisik-lpi-delete-btn" data-locale="' . esc_attr($locale) . '">' . esc_html__('Delete', 'thebisik-language-pack-installer') . '</button>';
										}
										?>
									</td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="6">
									<?php esc_html_e('We were unable to fetch the available languages. Please check your internet connection.', 'thebisik-language-pack-installer'); ?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<p class="submit">
					<button type="button" id="thebisik-lpi-submit-btn"
						class="button button-primary"><?php esc_html_e('Bulk Install Selected', 'thebisik-language-pack-installer'); ?></button>
					<button type="button" id="thebisik-lpi-bulk-delete-btn"
						class="button"><?php esc_html_e('Bulk Delete Selected', 'thebisik-language-pack-installer'); ?></button>
					<button type="button" id="thebisik-lpi-delete-all-btn" class="button"
						style="color:#d63638; border-color:#d63638;"><?php esc_html_e('Delete All Installed', 'thebisik-language-pack-installer'); ?></button>
				</p>
			</form>
		</div>
		<?php
	}
}
