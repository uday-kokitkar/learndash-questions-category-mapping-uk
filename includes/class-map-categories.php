<?php
/**
 * Map Categories List Table
 *
 * List down all the categories with respective meta using WP_List_Table.
 *
 * @package LearnDashQuestionsCategoryMappingUK
 * @since 1.0.0
 */

namespace LearnDashQuestionsCategoryMappingUK;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Map Categories WP_List_Table class.
 *
 * @since 1.0.0
 */
class Map_Categories extends \WP_List_Table {

	/**
	 * This class instance.
	 *
	 * @var Map_Categories
	 */
	protected static $instance = null;

	/**
	 * Array of filters.
	 *
	 * @var array $filters
	 */
	public $filters = array();

	/**
	 * Items per page.
	 *
	 * @var int $per_page
	 */
	public $per_page = 20;

	/**
	 * All courses.
	 *
	 * @var null|array
	 */
	public $all_courses = null;

	/**
	 * An object of Categories_CRUD_Helper class.
	 *
	 * @var Categories_CRUD_Helper
	 */
	private $categories_crud_helper = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->categories_crud_helper = Categories_CRUD_Helper::get_instance();
		parent::__construct(
			array(
				'singular' => 'category',
				'plural'   => 'categories',
				'ajax'     => true,
			)
		);
	}

	/**
	 * Get instance of this class.
	 *
	 * @return Map_Categories
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Show extra elements above the table nav.
	 *
	 * @param string $which Position in table nav.
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which && isset( $_GET['quiz_id'] ) && ! empty( $_GET['quiz_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<h2>' . sprintf(
				/* translators: %s: Quiz title */
				esc_html__( 'Categories for %s', 'learndash-questions-category-mapping-uk' ),
				'<i>' . esc_html( get_the_title( (int) $_GET['quiz_id'] ) ) . '</i>' // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) . '</h2>';
		}
	}

	/**
	 * Render categories and respective meta using the WP_List_Table utility class.
	 *
	 * @return void
	 */
	public function render_mappings() {
		// Add column to question category table.
		$this->categories_crud_helper->maybe_add_column();

		// Fetch all courses.
		if ( null === $this->all_courses ) {
			$this->all_courses = get_posts(
				array(
					'posts_per_page' => 100,
					'post_type'      => 'sfwd-courses',
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
		}

		$this->views();
		$this->prepare_items();
		$this->search_box( __( 'Search Category', 'learndash-questions-category-mapping-uk' ), 'ldqcm-category-search' );
		
		// Add hidden field for quiz_id if present.
		if ( isset( $_REQUEST['quiz_id'] ) && ! empty( $_REQUEST['quiz_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<input type="hidden" name="quiz_id" value="' . esc_attr( (int) $_REQUEST['quiz_id'] ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		
		$this->display();
	}

	/**
	 * Renders columns headings in the table.
	 *
	 * @return array A list of columns.
	 */
	public function get_columns() {
		$columns = array(
			'category' => \LearnDash_Custom_Label::get_label( 'question' ) . ' ' . __( 'Category', 'learndash-questions-category-mapping-uk' ),
			'course'   => \LearnDash_Custom_Label::get_label( 'course' ),
			'lesson'   => \LearnDash_Custom_Label::get_label( 'lesson' ),
			'topic'    => \LearnDash_Custom_Label::get_label( 'topic' ) . ' ' . __( '(optional)', 'learndash-questions-category-mapping-uk' ),
			'action'   => __( 'Action', 'learndash-questions-category-mapping-uk' ),
		);
		return $columns;
	}

	/**
	 * Responsible to display the data.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$current_page = $this->get_pagenum();
		$per_page     = $this->per_page;

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = array();

		$args = array(
			'per_page'      => $per_page,
			'current_page'  => $current_page,
			'category_name' => ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'cat_status'    => ( isset( $_REQUEST['cat_status'] ) && ! empty( $_REQUEST['cat_status'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['cat_status'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'quiz_id'       => ( isset( $_REQUEST['quiz_id'] ) && ! empty( $_REQUEST['quiz_id'] ) ) ? (int) $_REQUEST['quiz_id'] : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$categories_data = $this->categories_crud_helper->get_categories_data( $args );

		$arr = array();
		if ( ! empty( $categories_data['result'] ) ) {
			$index = 0;
			foreach ( $categories_data['result'] as $cat_data ) {
				$is_change_in_meta = false;
				$meta              = maybe_unserialize( $cat_data->ldqcm_meta );
				$step_mapping      = isset( $meta['rec_step'] ) ? $meta['rec_step'] : array();

				$arr[ $index ]['cat_id']    = $cat_data->category_id;
				$arr[ $index ]['category']  = $cat_data->category_name;
				$arr[ $index ]['course_id'] = isset( $step_mapping['course_id'] ) ? $step_mapping['course_id'] : null;
				$arr[ $index ]['lesson_id'] = isset( $step_mapping['lesson_id'] ) ? $step_mapping['lesson_id'] : null;
				$arr[ $index ]['topic_id']  = isset( $step_mapping['topic_id'] ) ? $step_mapping['topic_id'] : null;

				// Check if lesson/topic still exists.
				if ( null !== $arr[ $index ]['lesson_id'] && ! get_post_status( $arr[ $index ]['lesson_id'] ) ) {
					$arr[ $index ]['lesson_id'] = null;
					$is_change_in_meta          = true;
				}
				if ( null !== $arr[ $index ]['topic_id'] && ! get_post_status( $arr[ $index ]['topic_id'] ) ) {
					$arr[ $index ]['topic_id'] = null;
					$is_change_in_meta         = true;
				}

				// Update meta if lesson/topic was deleted.
				if ( $is_change_in_meta ) {
					$rec_step_meta = array(
						'course_id' => $arr[ $index ]['course_id'],
						'lesson_id' => $arr[ $index ]['lesson_id'],
						'topic_id'  => $arr[ $index ]['topic_id'],
					);
					$this->categories_crud_helper->update_rec_step_cat_meta( $arr[ $index ]['cat_id'], $rec_step_meta );
				}

				// Step link as per the mapping.
				if ( null !== $arr[ $index ]['course_id'] && null !== $arr[ $index ]['lesson_id'] ) {
					$child_step_id              = ! empty( $arr[ $index ]['topic_id'] ) ? $arr[ $index ]['topic_id'] : $arr[ $index ]['lesson_id'];
					$arr[ $index ]['step_link'] = learndash_get_step_permalink( $child_step_id, $arr[ $index ]['course_id'] );
				}

				$index++;
			}
		}
		$this->items = $arr;

		$this->set_pagination_args(
			array(
				'total_items' => intval( $categories_data['total_rows'] ),
				'per_page'    => intval( $per_page ),
				'total_pages' => ceil( intval( $categories_data['total_rows'] ) / intval( $per_page ) ),
			)
		);
	}

	/**
	 * Check table filters.
	 *
	 * @return void
	 */
	public function check_table_filters() {
		$this->filters = array();

		if ( ( isset( $_GET['s'] ) ) && ( ! empty( $_GET['s'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->filters['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	/**
	 * Get views callback function for WP_List_Table class.
	 *
	 * We are adding filter links to the table.
	 *
	 * @return array Links to filter the data.
	 */
	protected function get_views() {
		// Get the current URL.
		$current_url = add_query_arg( null, null );
		$current_url = remove_query_arg( 'paged', $current_url );

		// Get the value of cat_status from the current URL.
		$cat_status = isset( $_GET['cat_status'] ) ? sanitize_text_field( wp_unslash( $_GET['cat_status'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Add search query to URL if present.
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_url = add_query_arg( 's', trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$status_links = array(
			'all'        => '<a href="' . esc_url( remove_query_arg( 'cat_status', $current_url ) ) . '" class="' . ( empty( $cat_status ) ? 'current' : '' ) . '">' . __( 'All', 'learndash-questions-category-mapping-uk' ) . '</a>',
			'unassigned' => '<a href="' . esc_url( add_query_arg( 'cat_status', 'unassigned', $current_url ) ) . '" class="' . ( 'unassigned' === $cat_status ? 'current' : '' ) . '">' . __( 'Unassigned', 'learndash-questions-category-mapping-uk' ) . '</a>',
			'assigned'   => '<a href="' . esc_url( add_query_arg( 'cat_status', 'assigned', $current_url ) ) . '" class="' . ( 'assigned' === $cat_status ? 'current' : '' ) . '">' . __( 'Assigned', 'learndash-questions-category-mapping-uk' ) . '</a>',
		);
		return $status_links;
	}

	/**
	 * Table search box.
	 *
	 * @param string $text     Search text.
	 * @param string $input_id Search field HTML ID.
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>">
		<?php submit_button( $text, '', false, false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Show row item category column.
	 *
	 * @param array $item Custom array. Refer to 'prepare_items' function.
	 *
	 * @return string HTML for 'category' column.
	 */
	public function get_column_category( $item ) {
		ob_start();
		echo '<span class="category-name">' . esc_html( $item['category'] ) . '</span>';

		echo '<span class="step-link">';
		if ( isset( $item['step_link'] ) && ! empty( $item['step_link'] ) ) {
			echo '<a href="' . esc_url( $item['step_link'] ) . '" target="_blank"><span class="dashicons dashicons-external"></span></a>';
		}
		echo '</span>';
		return ob_get_clean();
	}

	/**
	 * Show row item category course.
	 *
	 * @param array $item Custom array. Refer to 'prepare_items' function.
	 *
	 * @return string HTML for 'course' column.
	 */
	public function get_column_course( $item ) {
		ob_start();
		echo '<div id="course-title-' . esc_attr( $item['cat_id'] ) . '">' . esc_html( $item['course_id'] ? get_the_title( $item['course_id'] ) : '-' ) . '</div>';
		if ( ! empty( $this->all_courses ) ) {
			echo '<select class="edit-mode course-selector" id="course-' . esc_attr( $item['cat_id'] ) . '">';
			echo '<option value="0">' . esc_html__( '--Select--', 'learndash-questions-category-mapping-uk' ) . '</option>';
			foreach ( $this->all_courses as $course ) {
				echo '<option value="' . esc_attr( $course->ID ) . '">' . esc_html( $course->post_title ) . '</option>';
			}
			echo '</select>';
		}
		return ob_get_clean();
	}

	/**
	 * Show row item category lesson.
	 *
	 * @param array $item Custom array. Refer to 'prepare_items' function.
	 *
	 * @return string HTML for 'lesson' column.
	 */
	public function get_column_lesson( $item ) {
		ob_start();
		echo '<div id="lesson-title-' . esc_attr( $item['cat_id'] ) . '">' . esc_html( $item['lesson_id'] ? get_the_title( $item['lesson_id'] ) : '-' ) . '</div>';
		echo '<select class="edit-mode lesson-selector" id="lesson-' . esc_attr( $item['cat_id'] ) . '">';
		echo '<option value="0">' . esc_html__( '--Select--', 'learndash-questions-category-mapping-uk' ) . '</option>';
		echo '</select>';
		return ob_get_clean();
	}

	/**
	 * Show row item category topic.
	 *
	 * @param array $item Custom array. Refer to 'prepare_items' function.
	 *
	 * @return string HTML for 'topic' column.
	 */
	public function get_column_topic( $item ) {
		ob_start();
		echo '<div id="topic-title-' . esc_attr( $item['cat_id'] ) . '">' . esc_html( $item['topic_id'] ? get_the_title( $item['topic_id'] ) : '-' ) . '</div>';
		echo '<select class="edit-mode topic-selector" id="topic-' . esc_attr( $item['cat_id'] ) . '">';
		echo '<option value="0">' . esc_html__( '--Select--', 'learndash-questions-category-mapping-uk' ) . '</option>';
		echo '</select>';
		return ob_get_clean();
	}

	/**
	 * Show row item actions.
	 *
	 * @param array $item Custom array. Refer to 'prepare_items' function.
	 *
	 * @return string HTML for 'action' column.
	 */
	public function get_column_action( $item ) {
		ob_start();
		?>
		<div class="action-wrapper actions">
			<a href="#" class="edit show-mode" title="<?php esc_attr_e( 'Edit this row', 'learndash-questions-category-mapping-uk' ); ?>"><?php esc_html_e( 'Edit', 'learndash-questions-category-mapping-uk' ); ?></a>
			<a href="#" class="save edit-mode button" title="<?php esc_attr_e( 'Save updated values', 'learndash-questions-category-mapping-uk' ); ?>"><?php esc_html_e( 'Save', 'learndash-questions-category-mapping-uk' ); ?></a>
			<a href="#" class="cancel edit-mode" title="<?php esc_attr_e( 'Cancel editing without saving', 'learndash-questions-category-mapping-uk' ); ?>"><?php esc_html_e( 'Cancel', 'learndash-questions-category-mapping-uk' ); ?></a>
			<a href="#" class="unlink edit-mode" title="<?php esc_attr_e( 'Remove mapping', 'learndash-questions-category-mapping-uk' ); ?>"><?php esc_html_e( 'Unlink', 'learndash-questions-category-mapping-uk' ); ?></a>
			<?php
			/**
			 * Allows adding custom content to category mapping action column.
			 *
			 * @since 1.0.0
			 *
			 * @param array $item Category item data.
			 */
			do_action( 'ldqcm_cat_mapping_column_action_content', $item );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays rows.
	 *
	 * @return void
	 */
	public function display_rows() {
		foreach ( $this->items as $item ) {
			$row_class = '';
			$row_data  = 'data-cat_id="' . esc_attr( $item['cat_id'] ) . '"';

			// Define the columns for each row.
			$columns = array(
				'category' => $this->get_column_category( $item ),
				'course'   => $this->get_column_course( $item ),
				'lesson'   => $this->get_column_lesson( $item ),
				'topic'    => $this->get_column_topic( $item ),
				'action'   => $this->get_column_action( $item ),
			);

			// If course or lesson ID is missing, that means we don't have mapping for this category.
			if ( empty( $item['course_id'] ) || empty( $item['lesson_id'] ) ) {
				$row_class .= ' not-mapped';
			}

			// Add class and data attribute to table row.
			if ( ! empty( $row_class ) ) {
				$row_class = ' class="' . trim( $row_class ) . '"';
			}
			if ( ! empty( $row_data ) ) {
				$row_data = ' ' . $row_data;
			}

			echo '<tr' . $row_class . $row_data . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			foreach ( $columns as $column_slug => $column_value ) {
				echo '<td class="row-' . esc_attr( $column_slug ) . '">' . $column_value . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</tr>';
		}
	}
}
