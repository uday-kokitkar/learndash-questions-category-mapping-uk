<?php
/**
 * Main Plugin Class
 *
 * Handles plugin initialization and dependency management.
 *
 * @package LearnDashQuestionsCategoryMappingUK
 * @since 1.0.0
 */

namespace LDQCM;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->check_dependencies();
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Check if LearnDash is active.
	 *
	 * @return bool
	 */
	private function check_dependencies() {
		if ( ! defined( 'LEARNDASH_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'learndash_missing_notice' ) );
			return false;
		}
		return true;
	}

	/**
	 * Display admin notice if LearnDash is not active.
	 */
	public function learndash_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				esc_html_e(
					'LearnDash Questions Category Mapping requires LearnDash LMS to be installed and activated.',
					'learndash-questions-category-mapping-uk'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies() {
		// Load classes.
		require_once LDQCM_PLUGIN_DIR . 'includes/class-categories-crud-helper.php';
		require_once LDQCM_PLUGIN_DIR . 'includes/class-admin-bar-menu.php';
		require_once LDQCM_PLUGIN_DIR . 'includes/class-category-rec-links.php';

		// Load admin classes only in admin area.
		if ( is_admin() ) {
			require_once LDQCM_PLUGIN_DIR . 'includes/class-map-categories.php';
			require_once LDQCM_PLUGIN_DIR . 'includes/class-category-mappings-page.php';
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'learndash-questions-category-mapping-uk',
			false,
			dirname( LDQCM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return LDQCM_VERSION;
	}

	/**
	 * Get plugin directory path.
	 *
	 * @return string
	 */
	public function get_plugin_dir() {
		return LDQCM_PLUGIN_DIR;
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return LDQCM_PLUGIN_URL;
	}
}
