<?php
/**
 * Admin Bar Menu
 *
 * Adds admin bar menu to get listing of current quiz's categories easily.
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
 * Adds admin bar menu to get listing of current quiz's categories easily.
 *
 * @since 1.0.0
 */
class Admin_Bar_Menu {

	/**
	 * Public constructor for class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'add_cat_mapping_link' ), 999 );
	}

	/**
	 * Adds a Category Mapping link to the WordPress admin bar for the current post, if available.
	 *
	 * This function checks if a post is currently being viewed and adds a link to the WordPress admin bar
	 * that leads to the Category Mapping page for that post's quiz.
	 *
	 * @since 1.0.0
	 * @global WP_Admin_Bar $wp_admin_bar The WordPress admin bar instance.
	 * @global WP_Post|null $post The currently viewed post, or null if no post is being viewed.
	 *
	 * @return void
	 */
	public function add_cat_mapping_link() {
		global $wp_admin_bar;
		global $post;

		if ( $post && 'sfwd-quiz' === $post->post_type ) {
			$wp_admin_bar->add_node(
				array(
					'id'    => 'ldqcm-category-mapping',
					'title' => __( 'Category Mapping', 'learndash-questions-category-mapping-uk' ),
					'href'  => admin_url( 'admin.php?page=questions-category-mapping&quiz_id=' . $post->ID ),
				)
			);
		}
	}
}

new Admin_Bar_Menu();
