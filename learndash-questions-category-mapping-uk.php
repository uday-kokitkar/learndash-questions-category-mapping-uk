<?php
/**
 * Plugin Name: LearnDash Questions Category Mapping
 * Plugin URI: https://github.com/uday-kokitkar/learndash-questions-category-mapping-uk
 * Description: Map LearnDash quiz question categories to course lessons and topics for personalized learning recommendations.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Uday Kokitkar
 * Author URI: https://udayk.net/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: learndash-questions-category-mapping-uk
 * Domain Path: /languages
 *
 * @package LearnDashQuestionsCategoryMappingUK
 */

namespace LearnDashQuestionsCategoryMappingUK;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LDQCM_VERSION', '1.0.0' );
define( 'LDQCM_PLUGIN_FILE', __FILE__ );
define( 'LDQCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LDQCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LDQCM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class loader.
 */
require_once LDQCM_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin.
 *
 * @return Plugin
 */
function ldqcm_init() {
	return Plugin::get_instance();
}

// Initialize the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\ldqcm_init' );

/**
 * Activation hook.
 */
function ldqcm_activate() {
	// Ensure the database table has the required column.
	require_once LDQCM_PLUGIN_DIR . 'includes/class-categories-crud-helper.php';
	$crud_helper = Categories_CRUD_Helper::get_instance();
	$crud_helper->maybe_add_column();
	
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\ldqcm_activate' );

/**
 * Deactivation hook.
 */
function ldqcm_deactivate() {
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\ldqcm_deactivate' );
