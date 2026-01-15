<?php
/**
 * Category Mappings Settings Page
 *
 * LearnDash Settings Page for Questions Category Mapping.
 *
 * @package LearnDashQuestionsCategoryMappingUK
 * @since 1.0.0
 */

namespace LDQCM;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

if ( class_exists( '\LearnDash_Settings_Page' ) ) {

	/**
	 * Class LearnDash Settings Page Questions Options.
	 *
	 * @since 1.0.0
	 */
	class Category_Mappings_Page extends \LearnDash_Settings_Page {

		/**
		 * Public constructor for class.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->parent_menu_page_url  = 'edit.php?post_type=sfwd-question';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'questions-category-mapping';
			$this->settings_tab_priority = 10;
			$this->settings_page_title   = esc_html__( 'Category Mapping', 'learndash-questions-category-mapping-uk' );
			$this->show_submit_meta      = false;
			$this->show_quick_links_meta = false;

			parent::__construct();

			add_action(
				'learndash_settings_page_before_metaboxes',
				function () {
					if ( $this->is_category_mapping_page() ) {
						$map_categories_obj = Map_Categories::get_instance();
						$map_categories_obj->render_mappings();
					}
				}
			);

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'wp_ajax_ldqcm_lesson_rec_course_steps', array( $this, 'ajax_lesson_rec_course_steps' ) );
			add_action( 'wp_ajax_ldqcm_lesson_rec_cat_meta', array( $this, 'ajax_update_cat_meta' ) );
			add_action( 'wp_ajax_ldqcm_lesson_clear_rec_cat_meta', array( $this, 'ajax_clear_cat_meta' ) );
			add_filter( 'learndash_admin_page_form', array( $this, 'set_learndash_admin_page_form' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'add_category_mapping_link' ), 10, 2 );
		}

		/**
		 * A utility function to check whether this is a category mapping page.
		 *
		 * @return bool True if the page is mapping page.
		 */
		public function is_category_mapping_page() {
			$screen = get_current_screen();
			if ( 'sfwd-question_page_' . $this->settings_page_id === $screen->id ) {
				return true;
			}
			return false;
		}

		/**
		 * Ajax callback function for 'ldqcm_lesson_rec_course_steps' action.
		 *
		 * This is to get all the steps of the given course ID.
		 *
		 * @return void
		 */
		public function ajax_lesson_rec_course_steps() {
			if ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
				$course_id    = isset( $_POST['course_id'] ) ? (int) $_POST['course_id'] : null;
				$course_steps = array();

				if ( $course_id ) {
					$lessons = learndash_course_get_children_of_step( $course_id, 0, 'sfwd-lessons', 'objects', false );

					if ( ! empty( $lessons ) ) {
						foreach ( $lessons as $lesson ) {
							$course_steps[ $lesson->ID ]           = array(
								'title' => str_replace( '&#039;', "'", esc_html( $lesson->post_title ) ),
							);
							$course_steps[ $lesson->ID ]['topics'] = array();
							$topics                                = learndash_course_get_children_of_step( $course_id, $lesson->ID, 'sfwd-topic', 'objects', false );

							if ( ! empty( $topics ) ) {
								foreach ( $topics as $topic ) {
									$course_steps[ $lesson->ID ]['topics'][ $topic->ID ] = array(
										'title' => str_replace( '&#039;', "'", esc_html( $topic->post_title ) ),
									);
								}
							}
						}
					}
					wp_send_json( $course_steps );
				}
			}
			wp_send_json_error();
		}

		/**
		 * Ajax callback function for 'ldqcm_lesson_rec_cat_meta' action.
		 *
		 * @return void
		 */
		public function ajax_update_cat_meta() {
			if ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
				$cat_id    = isset( $_POST['cat_id'] ) ? (int) $_POST['cat_id'] : null;
				$course_id = isset( $_POST['course_id'] ) ? (int) $_POST['course_id'] : null;
				$lesson_id = isset( $_POST['lesson_id'] ) ? (int) $_POST['lesson_id'] : null;
				$topic_id  = isset( $_POST['topic_id'] ) ? (int) $_POST['topic_id'] : null;

				if ( ! empty( $cat_id ) && ! empty( $course_id ) && ! empty( $lesson_id ) ) {
					$rec_step = array(
						'course_id' => $course_id,
						'lesson_id' => $lesson_id,
						'topic_id'  => $topic_id,
					);

					$categories_crud_helper = Categories_CRUD_Helper::get_instance();
					$categories_crud_helper->update_rec_step_cat_meta( $cat_id, $rec_step );

					$child_step_id = ! empty( $topic_id ) ? $topic_id : $lesson_id;
					$step_link     = learndash_get_step_permalink( $child_step_id, $course_id );
					wp_send_json_success( array( 'step_link' => $step_link ) );
				}
			}
			wp_send_json_error();
		}

		/**
		 * Ajax callback to unlink or clear the recommended step meta data.
		 *
		 * @return void
		 */
		public function ajax_clear_cat_meta() {
			if ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
				$cat_id = isset( $_POST['cat_id'] ) ? (int) $_POST['cat_id'] : null;

				if ( ! empty( $cat_id ) ) {
					$categories_crud_helper = Categories_CRUD_Helper::get_instance();
					$categories_crud_helper->clear_rec_step_cat_meta( $cat_id );
					wp_send_json_success();
				}
			}
			wp_send_json_error();
		}

		/**
		 * Enqueue scripts and styles to the category mapping page. Only for the backend.
		 *
		 * @param string $hook A hook name.
		 *
		 * @return void
		 */
		public function enqueue_admin_assets( $hook ) {
			if ( $this->is_category_mapping_page() ) {
				wp_enqueue_style(
					'ldqcm-admin-style',
					LDQCM_PLUGIN_URL . 'assets/css/admin-category-mappings.css',
					array(),
					LDQCM_VERSION
				);

				wp_enqueue_script(
					'ldqcm-admin-script',
					LDQCM_PLUGIN_URL . 'assets/js/admin-category-mappings.js',
					array( 'jquery' ),
					LDQCM_VERSION,
					true
				);
			}
		}

		/**
		 * Set form action value to the form.
		 *
		 * @param string $form  Form HTML.
		 * @param bool   $start true if the form open tag.
		 *
		 * @return string Modified form element.
		 */
		public function set_learndash_admin_page_form( $form, $start ) {
			if ( true === $start && $this->is_category_mapping_page() ) {
				$form = '<form id="learndash-settings-page-form" method="post" action="admin.php?page=questions-category-mapping">';
			}
			return $form;
		}

		/**
		 * Add a direct link to category mappings page to filter categories only related to questions from each quiz.
		 *
		 * @param array    $actions Actions for a row.
		 * @param \WP_Post $post    WP_Post object of the current post.
		 *
		 * @return array Modified actions for a row.
		 */
		public function add_category_mapping_link( $actions, $post ) {
			if ( 'sfwd-quiz' === $post->post_type ) {
				$url                          = admin_url( 'admin.php?page=questions-category-mapping&quiz_id=' . $post->ID );
				$actions['ldqcm_cat_mapping'] = sprintf(
					'<a href="%s">%s</a>',
					$url,
					__( 'Category Mapping', 'learndash-questions-category-mapping-uk' )
				);
			}
			return $actions;
		}
	}
}

add_action(
	'learndash_settings_pages_init',
	function () {
		Category_Mappings_Page::add_page_instance();
	}
);
