<?php
/**
 * Categories CRUD Helper
 *
 * A helper class to run CRUD operations on LearnDash Questions category table.
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
 * A helper class to run CRUD operations on category table.
 *
 * @since 1.0.0
 */
class Categories_CRUD_Helper {

	/**
	 * This class instance.
	 *
	 * @var Categories_CRUD_Helper
	 */
	protected static $instance = null;

	/**
	 * A name of the pro quiz category table.
	 *
	 * @var null|string
	 */
	private $quiz_category_tbl_name = null;

	/**
	 * Meta column name.
	 *
	 * @var string
	 */
	private $meta_column = 'ldqcm_meta';

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->quiz_category_tbl_name = \LDLMS_DB::get_table_name( 'quiz_category' );
	}

	/**
	 * Get instance of this class.
	 *
	 * @return Categories_CRUD_Helper
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add column to the category table if not exists.
	 *
	 * @return void
	 */
	public function maybe_add_column() {
		global $wpdb;

		if ( ! function_exists( 'maybe_add_column' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$ddl = 'ALTER TABLE ' . esc_sql( $this->get_category_tbl_name() ) . ' ADD ' . $this->meta_column . ' longtext NULL';
		\maybe_add_column( $this->get_category_tbl_name(), $this->meta_column, $ddl );
	}

	/**
	 * Get the pro quiz category table name.
	 *
	 * @return string A table name.
	 */
	public function get_category_tbl_name() {
		return $this->quiz_category_tbl_name;
	}

	/**
	 * Get total number of categories, limited categories data as per pagination.
	 *
	 * @param array $args An array of args. Args list is given below.
	 *  $per_page      int Rows limit.
	 *  $current_page  int Current page in pagination.
	 *  $category_name int Category name. Mostly to search this name in the table.
	 *  $cat_status    int If assigned, fetch rows that have `rec_step` meta. If unassigned, rows with no `rec_step` meta.
	 *  $quiz_id       int Quiz ID to filter categories.
	 *
	 * @return array An array of 'total_rows' with a number and 'result' with categories and respective meta.
	 */
	public function get_categories_data( $args ) {
		global $wpdb;

		$per_page      = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
		$current_page  = isset( $args['current_page'] ) ? (int) $args['current_page'] : 1;
		$category_name = isset( $args['category_name'] ) && ! empty( $args['category_name'] ) ? $args['category_name'] : null;
		$cat_status    = isset( $args['cat_status'] ) && ! empty( $args['cat_status'] ) ? $args['cat_status'] : null;
		$quiz_id       = isset( $args['quiz_id'] ) && ! empty( $args['quiz_id'] ) ? (int) $args['quiz_id'] : null;

		$where = '';

		if ( $category_name ) {
			$where .= ' `category_name` LIKE \'%' . esc_sql( $category_name ) . '%\'';
		}

		if ( $cat_status ) {
			$where .= ! empty( $where ) ? ' AND ' : '';
			if ( 'assigned' === $cat_status ) {
				$where .= ' ( `' . $this->meta_column . '` LIKE \'%rec_step%\' )';
			} elseif ( 'unassigned' === $cat_status ) {
				$where .= ' ( `' . $this->meta_column . '` NOT LIKE \'%rec_step%\' OR `' . $this->meta_column . '` IS NULL )';
			}
		}

		// Select categories of questions from the selected quiz.
		if ( $quiz_id ) {
			$quiz_questions = get_post_meta( $quiz_id, 'ld_quiz_questions', true );

			if ( ! empty( $quiz_questions ) ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
				// Get pro category IDs of these questions.
				$questions_categories = $wpdb->get_col(
					'SELECT category_id FROM ' . esc_sql( \LDLMS_DB::get_table_name( 'quiz_question' ) ) . ' WHERE id IN (' . implode( ',', array_map( 'intval', $quiz_questions ) ) . ')'
				);
				// phpcs:enable

				if ( ! empty( $questions_categories ) ) {
					$where .= ! empty( $where ) ? ' AND ' : '';
					$where .= 'category_id IN ( ' . implode( ',', array_map( 'intval', array_unique( $questions_categories ) ) ) . ' )';
				}
			}
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total_rows = $wpdb->get_var(
			'SELECT count(1) as count FROM ' . esc_sql( $this->quiz_category_tbl_name ) . ( ! empty( $where ) ? ( ' WHERE ' . $where ) : '' )
		);

		$limited_results = $wpdb->get_results(
			'SELECT * FROM ' . esc_sql( $this->quiz_category_tbl_name ) . ( ! empty( $where ) ? ( ' WHERE ' . $where ) : '' ) . ' ORDER BY category_name ASC LIMIT ' . ( ( $current_page - 1 ) * $per_page ) . ', ' . $per_page
		);
		// phpcs:enable

		$data = array(
			'total_rows' => $total_rows,
			'result'     => $limited_results,
		);

		return $data;
	}

	/**
	 * Update category meta specific to recommended steps.
	 *
	 * @param int   $cat_id        A pro category ID.
	 * @param array $rec_step_meta An array of meta.
	 *
	 * @return void
	 */
	public function update_rec_step_cat_meta( $cat_id, $rec_step_meta ) {
		if ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
			$meta = $this->get_cat_all_meta( $cat_id );

			if ( empty( $meta ) ) {
				$meta = array();
			}

			$meta['rec_step'] = $rec_step_meta;

			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$this->quiz_category_tbl_name,
				array(
					$this->meta_column => maybe_serialize( $meta ),
				),
				array(
					'category_id' => $cat_id,
				),
				array( '%s' ),
				array( '%d' )
			);
			// phpcs:enable

			// Clear cache.
			$this->clear_cat_cache( $cat_id );
		}
	}

	/**
	 * Update category meta specific to provided key.
	 *
	 * @param int    $cat_id    A pro category ID.
	 * @param mixed  $data      The data to insert.
	 * @param string $meta_key  A meta key in which data should be updated.
	 *
	 * @return void
	 */
	public function update_cat_meta_by_key( $cat_id, $data, $meta_key ) {
		if ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
			$meta = $this->get_cat_all_meta( $cat_id );

			if ( empty( $meta ) ) {
				$meta = array();
			}

			$meta[ $meta_key ] = $data;

			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$this->quiz_category_tbl_name,
				array(
					$this->meta_column => maybe_serialize( $meta ),
				),
				array(
					'category_id' => $cat_id,
				),
				array( '%s' ),
				array( '%d' )
			);
			// phpcs:enable

			// Clear cache.
			$this->clear_cat_cache( $cat_id );
		}
	}

	/**
	 * Remove rec step meta for the given category ID.
	 *
	 * @param int $cat_id A pro category ID.
	 *
	 * @return void
	 */
	public function clear_rec_step_cat_meta( $cat_id ) {
		if ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
			$meta = $this->get_cat_all_meta( $cat_id );

			if ( ! empty( $meta ) && isset( $meta['rec_step'] ) ) {

				unset( $meta['rec_step'] );

				global $wpdb;

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update(
					$this->quiz_category_tbl_name,
					array(
						$this->meta_column => maybe_serialize( $meta ),
					),
					array(
						'category_id' => $cat_id,
					),
					array( '%s' ),
					array( '%d' )
				);
				// phpcs:enable

				// Clear cache.
				$this->clear_cat_cache( $cat_id );
			}
		}
	}

	/**
	 * Get all meta data for a category.
	 *
	 * @param int $cat_id A pro category ID.
	 *
	 * @return array|null $meta An array of meta.
	 */
	public function get_cat_all_meta( $cat_id ) {
		global $wpdb;

		$cache_key = 'ldqcm_cat_meta_' . $cat_id;

		$meta = wp_cache_get( $cache_key );

		if ( false === $meta ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$meta = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ' . $this->meta_column . ' FROM ' . $this->quiz_category_tbl_name . ' WHERE category_id = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$cat_id
				)
			);
			// phpcs:enable

			$meta = maybe_unserialize( $meta );

			wp_cache_set( $cache_key, $meta );
		}

		return $meta;
	}

	/**
	 * Get recommended steps meta data for a category.
	 *
	 * @param int $cat_id A pro category ID.
	 *
	 * @return array|null $meta An array of meta.
	 */
	public function get_rec_step_meta( $cat_id ) {
		$all_meta = $this->get_cat_all_meta( $cat_id );
		$rec_step = null;

		if ( ! empty( $all_meta ) && isset( $all_meta['rec_step'] ) ) {
			$rec_step = $all_meta['rec_step'];
		}
		return $rec_step;
	}

	/**
	 * Get category meta of specific key only.
	 *
	 * @param int    $cat_id   A pro category ID.
	 * @param string $meta_key A meta key whose data should be retrieved.
	 *
	 * @return null|array $meta An array of meta data filtered by specific key.
	 */
	public function get_cat_meta_by_key( $cat_id, $meta_key ) {
		$all_meta  = $this->get_cat_all_meta( $cat_id );
		$meta_data = null;

		if ( ! empty( $all_meta ) && isset( $all_meta[ $meta_key ] ) ) {
			$meta_data = $all_meta[ $meta_key ];
		}
		return $meta_data;
	}

	/**
	 * Retrieve categories from the database by category IDs.
	 *
	 * @param array $ids Array of category IDs to retrieve.
	 *
	 * @return array|null Array of category objects or null if no categories found.
	 */
	public function get_meta_by_ids( array $ids = array() ) {
		if ( ! empty( $ids ) ) {
			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

			// Generate placeholders for the IN statement.
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			// Construct the query with placeholders.
			$query = $wpdb->prepare(
				'SELECT category_id, category_name, ' . $this->meta_column . ' FROM ' . $this->quiz_category_tbl_name . ' WHERE category_id IN (' . $placeholders . ')', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$ids
			);

			return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:enable
		}
		return null;
	}

	/**
	 * Retrieve categories from the database by category their names.
	 *
	 * @param array $names Array of category names to retrieve.
	 *
	 * @return array|null Array of category objects or null if no categories found.
	 */
	public function get_meta_by_names( array $names = array() ) {
		if ( ! empty( $names ) ) {
			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

			// Generate placeholders for the IN statement.
			$placeholders = implode( ',', array_fill( 0, count( $names ), '%s' ) );

			// Construct the query with placeholders.
			$query = $wpdb->prepare(
				'SELECT category_id, category_name, ' . $this->meta_column . ' FROM ' . $this->quiz_category_tbl_name . ' WHERE category_name IN (' . $placeholders . ')', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$names
			);

			return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:enable
		}
		return null;
	}

	/**
	 * Clear category cache.
	 *
	 * @param int $cat_id Category ID.
	 *
	 * @return void
	 */
	private function clear_cat_cache( $cat_id ) {
		$cache_key = 'ldqcm_cat_meta_' . $cat_id;
		wp_cache_delete( $cache_key );
	}
}
