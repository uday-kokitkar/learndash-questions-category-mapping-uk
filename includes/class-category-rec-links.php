<?php
/**
 * Category Recommendation Links
 *
 * A class to fetch data to recommended LD content for each LD quiz category.
 * Works for the results displayed after attempting the quiz.
 * It also works on the quiz history page.
 *
 * @package LearnDashQuestionsCategoryMappingUK
 * @since 1.0.0
 */

namespace LearnDashQuestionsCategoryMappingUK;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A class to fetch data to recommended LD content for each LD quiz category.
 *
 * @since 1.0.0
 */
class Category_Rec_Links {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 10000 );
		add_action( 'wp_ajax_ldqcm_get_rec_steps', array( $this, 'ajax_get_rec_steps' ) );
		add_action( 'wp_ajax_nopriv_ldqcm_get_rec_steps', array( $this, 'ajax_get_rec_steps' ) );
		add_action( 'wp_ajax_ldqcm_get_rec_steps_by_name', array( $this, 'ajax_get_rec_steps_by_name' ) );
	}

	/**
	 * Enqueue assets on the single quiz page.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_singular( 'sfwd-quiz' ) && ! $this->is_test_history_page() ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			'ldqcm-front-script',
			LDQCM_PLUGIN_URL . 'assets/js/front-category-mappings' . $suffix . '.js',
			array( 'jquery' ),
			LDQCM_VERSION,
			true
		);

		wp_localize_script(
			'ldqcm-front-script',
			'ldqcm_front',
			array(
				'admin_url' => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'ldqcm_get_rec_steps' ),
			)
		);

		$custom_css = '.wpProQuiz_content .wpProQuiz_catOverview span.wpProQuiz_catName a,
		.wpProQuiz_modal_window #wpProQuiz_user_content .categoryTr a {
			text-decoration: underline;
			color: #603cff;
		}
		.wpProQuiz_content .wpProQuiz_catOverview span.wpProQuiz_catName a.below-average {
			color: #cc1818;
		}';

		wp_add_inline_style( 'learndash-front', $custom_css );
	}

	/**
	 * Check if current page is test history page.
	 *
	 * @return bool
	 */
	private function is_test_history_page() {
		// This is a placeholder. Implement based on your theme/setup.
		// Example: return is_page( 'test-history' );
		return false;
	}

	/**
	 * Ajax callback function to return recommended steps for given category IDs.
	 *
	 * Returns a JSON string with category details.
	 *
	 * @return void
	 */
	public function ajax_get_rec_steps() {
		check_ajax_referer( 'ldqcm_get_rec_steps', 'nonce' );

		$all_cat_ids = isset( $_POST['all_cat_ids'] ) ? json_decode( wp_unslash( $_POST['all_cat_ids'] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$quiz_pro_id = isset( $_POST['quiz_pro_id'] ) ? (int) $_POST['quiz_pro_id'] : null;

		if ( ! empty( $all_cat_ids ) && is_array( $all_cat_ids ) && ! empty( $quiz_pro_id ) ) {
			wp_send_json_success( $this->get_cat_rec_links( $quiz_pro_id, $all_cat_ids ) );
		}

		wp_send_json_error();
	}

	/**
	 * Ajax callback function to return recommended steps for given category names.
	 *
	 * Returns a JSON string with category details.
	 *
	 * @return void
	 */
	public function ajax_get_rec_steps_by_name() {
		check_ajax_referer( 'ldqcm_get_rec_steps', 'nonce' );

		// Currently, we are using this function to fetch category meta for user statistics.
		// This means, the user must be logged in to access this function.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$all_cat_names = isset( $_POST['all_cat_names'] ) ? json_decode( wp_unslash( $_POST['all_cat_names'] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$quiz_pro_id   = isset( $_POST['quiz_pro_id'] ) ? (int) $_POST['quiz_pro_id'] : null;

		if ( ! empty( $all_cat_names ) && is_array( $all_cat_names ) && ! empty( $quiz_pro_id ) ) {
			wp_send_json_success( $this->get_cat_rec_links( $quiz_pro_id, $all_cat_names, true ) );
		}

		wp_send_json_error();
	}

	/**
	 * Use this function to get categories data by IDs or by names.
	 *
	 * We match exact category names.
	 *
	 * @param int   $quiz_pro_id Pro quiz ID.
	 * @param array $all_cats    All categories. This array contains either IDs or names.
	 * @param bool  $by_name     If true, we assume that $all_cats array contains category names.
	 *
	 * @return array An array with category data.
	 */
	private function get_cat_rec_links( $quiz_pro_id = 0, $all_cats = array(), $by_name = false ) {
		$cat_rec_links = array();

		if ( empty( $all_cats ) || ! is_array( $all_cats ) || empty( $quiz_pro_id ) ) {
			return $cat_rec_links;
		}

		// Generate a unique cache key based on the input data.
		$cache_key = 'ldqcm_rec_steps_' . $quiz_pro_id;

		// Check if cache should be skipped (useful for QA/debugging).
		$cat_rec_links = isset( $_COOKIE['ldqcm_skip_cache'] ) ? false : wp_cache_get( $cache_key );

		// If result not found in cache, calculate it and store in cache.
		if ( false === $cat_rec_links ) {

			$result                 = null;
			$categories_crud_helper = Categories_CRUD_Helper::get_instance();

			if ( true === $by_name ) {
				$result = $categories_crud_helper->get_meta_by_names( $all_cats );
			} else {
				// Filter out numbers greater than 0.
				$all_cats = array_filter(
					$all_cats,
					function ( $value ) {
						return $value > 0;
					}
				);

				// Convert filtered array values to integers.
				$all_cats = array_map( 'intval', $all_cats );
				$result   = $categories_crud_helper->get_meta_by_ids( $all_cats );
			}

			$cat_rec_links = array();

			if ( ! empty( $result ) ) {
				foreach ( $result as $cat_data ) {
					$raw_meta = isset( $cat_data->ldqcm_meta ) ? $cat_data->ldqcm_meta : null;

					if ( ! empty( $raw_meta ) ) {
						$meta          = maybe_unserialize( $raw_meta );
						$rec_step_meta = isset( $meta['rec_step'] ) && ! empty( $meta['rec_step'] ) ? $meta['rec_step'] : null;

						if ( $rec_step_meta ) {
							$course_id     = $rec_step_meta['course_id'];
							$child_step_id = ! empty( $rec_step_meta['topic_id'] ) ? $rec_step_meta['topic_id'] : $rec_step_meta['lesson_id'];
							$step_link     = learndash_get_step_permalink( $child_step_id, $course_id );

							if ( ! empty( $step_link ) ) {
								$cat_rec_links[ $cat_data->category_id ]              = array();
								$cat_rec_links[ $cat_data->category_id ]['name']      = esc_html( $cat_data->category_name );
								$cat_rec_links[ $cat_data->category_id ]['step_link'] = esc_url( $step_link );
							}
						}
					}
				}
			}

			// Store the result in cache for future use.
			wp_cache_set( $cache_key, $cat_rec_links );
		}

		return $cat_rec_links;
	}
}

new Category_Rec_Links();
