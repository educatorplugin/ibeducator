<?php

// Forbid direct access.
if ( ! defined( 'ABSPATH' ) ) exit();

// Load the WP_List_Table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Entries list table.
 */
class Edr_Admin_EntriesTable extends WP_List_Table {
	/**
	 * @var array
	 */
	protected $pending_quiz_entries = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Entry', 'ibeducator' ),
			'plural'   => __( 'Entries', 'ibeducator' ),
			'ajax'     => false,
		) );

		$this->process_bulk_action();
	}

	/**
	 * Get the entry IDs that have at least one quiz pending.
	 *
	 * @return array
	 */
	protected function get_pending_quiz_entries() {
		if ( null === $this->pending_quiz_entries ) {
			if ( is_array( $this->items ) ) {
				$entry_ids = array();

				foreach ( $this->items as $item ) {
					$entry_ids[] = $item['ID'];
				}

				$this->pending_quiz_entries = Edr_Manager::get( 'edr_quizzes' )->check_for_pending_quizzes( $entry_ids );
			} else {
				$this->pending_quiz_entries = array();
			}
		}

		return $this->pending_quiz_entries;
	}

	/**
	 * Display the filters form.
	 */
	public function display_entry_filters() {
		$statuses = edr_get_entry_statuses();
		$access = '';

		if ( current_user_can( 'manage_educator' ) ) {
			$access = 'all';
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			$access = 'own';
		}

		$courses = null;

		if ( ! empty( $access ) ) {
			$course_args = array(
				'post_type'      => 'ib_educator_course',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

			if ( 'own' == $access ) {
				$course_args['include'] = IB_Educator::get_instance()->get_lecturer_courses( get_current_user_id() );
			}

			$courses = get_posts( $course_args );
		}

		$student = null;

		if ( isset( $_GET['student'] ) ) {
			$student = get_user_by( 'slug', $_GET['student'] );
		}
		?>
		<div class="ib-edu-tablenav top">
			<form class="ib-edu-admin-search" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="ib_educator_entries">
				<div class="block">
					<label for="search-entry-id"><?php echo _x( 'ID', 'ID of an item', 'ibeducator' ); ?></label>
					<input type="text" id="search-entry-id" name="id" value="<?php if ( ! empty( $_GET['id'] ) ) echo intval( $_GET['id'] ); ?>">
				</div>
				<div class="block">
					<label for="search-entry-status"><?php _e( 'Status', 'ibeducator' ); ?></label>
					<select id="search-entry-status" name="status">
						<option value=""><?php _e( 'All', 'ibeducator' ); ?></option>
						<?php
							foreach ( $statuses as $key => $value ) {
								$selected = ( isset( $_GET['status'] ) && $key == $_GET['status'] ) ? ' selected="selected"' : '';

								echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<label for="search-student"><?php _e( 'Student', 'ibeducator' ); ?></label>
					<div class="ib-edu-autocomplete">
						<input
							type="text"
							name="student"
							id="search-student"
							autocomplete="off"
							value="<?php if ( $student ) echo esc_attr( $student->user_nicename ); ?>"
							data-label="<?php if ( $student ) echo esc_attr( $student->display_name . ' (' . $student->user_login . ')' ); ?>">
					</div>
				</div>
				<?php if ( ! empty( $courses ) ) : ?>
					<div class="block">
						<label><?php _e( 'Course', 'ibeducator' ); ?></label>
						<select name="course_id">
							<option value=""><?php _e( 'All', 'ibeducator' ); ?></option>
							<?php
								foreach ( $courses as $course ) {
									$selected = ( isset( $_GET['course_id'] ) && $course->ID == $_GET['course_id'] ) ? ' selected="selected"' : '';

									echo '<option value="' . intval( $course->ID ) . '"' . $selected . '>' . esc_html( $course->post_title ) . '</option>';
								}
							?>
						</select>
					</div>
				<?php endif; ?>
				<div class="block">
					<input type="submit" class="button" value="<?php _e( 'Search', 'ibeducator' ); ?>">
				</div>
			</form>
		</div>

		<script>
			ibEducatorAutocomplete(document.getElementById('search-student'), {
				key: 'slug',
				value: 'name',
				searchBy: 'name',
				nonce: <?php echo json_encode( wp_create_nonce( 'ib_educator_autocomplete' ) ); ?>,
				url: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
				entity: 'entries_student'
			});
		</script>
		<?php
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox">',
			'ID'        => _x( 'ID', 'ID of an item', 'ibeducator' ),
			'course_id' => __( 'Course', 'ibeducator' ),
			'user_id'   => __( 'Username', 'ibeducator' ),
			'status'    => __( 'Status', 'ibeducator' ),
			'grade'     => __( 'Grade', 'ibeducator' ),
			'date'      => __( 'Date', 'ibeducator' ),
		);

		return $columns;
	}

	public function column_cb( $item ) {
		return '<input type="checkbox" name="entry[]" value="' . intval( $item['ID'] ) . '">';
	}

	/**
	 * Column: ID.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_ID( $item ) {
		return intval( $item['ID'] );
	}

	/**
	 * Column: course_id.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_course_id( $item ) {
		$title = '';
		$course = get_post( $item['course_id'] );

		if ( $course ) {
			$title = $course->post_title;
		}

		if ( in_array( $item['ID'], $this->get_pending_quiz_entries() ) ) {
			$title .= ' (' . __( 'quiz pending', 'ibeducator' ) . ')';
		}

		$base_url = admin_url( 'admin.php?page=ib_educator_entries' );
		$edit_url = admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $item['ID'] );
		$progress_url = admin_url( 'admin.php?page=ib_educator_entries&edu-action=entry-progress&entry_id=' . $item['ID'] );
		$delete_url = wp_nonce_url( add_query_arg( array( 'edu-action' => 'delete-entry', 'entry_id' => $item['ID'] ), $base_url ), 'edr_delete_entry' );

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'ibeducator' ) . '</a>';
		$actions['progress'] = '<a href="' . esc_url( $progress_url ) . '">' . __( 'Progress', 'ibeducator' ) . '</a>';

		if ( current_user_can( 'manage_educator' ) ) {
			$actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" class="delete-entry">' . __( 'Delete', 'ibeducator' ) . '</a>';
		}

		return '<strong>' . esc_html( $title ) . '</strong>' . $this->row_actions( $actions );
	}

	/**
	 * Column: user_id.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_user_id( $item ) {
		$student = get_user_by( 'id', $item['user_id'] );

		if ( $student ) {
			$student_url = add_query_arg( array( 'student' => $student->user_nicename ), admin_url( 'admin.php?page=ib_educator_entries' ) );

			return '<a href="' . esc_url( $student_url ) . '">' . esc_html( $student->user_login ) . '</a>';
		}

		return '';
	}

	/**
	 * Column: status.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_status( $item ) {
		return sanitize_title( $item['entry_status'] );
	}

	/**
	 * Column: grade.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_grade( $item ) {
		return ib_edu_format_grade( $item['grade'] );
	}

	/**
	 * Column: date.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_date( $item ) {
		return date( 'j M, Y H:i', strtotime( $item['entry_date'] ) );
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'ibeducator' ),
		);

		return $actions;
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		$ids = isset( $_POST['entry'] ) ? $_POST['entry'] : null;

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return;
		}

		$action = $this->current_action();

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$entry = edr_get_entry( $id );

				if ( $entry->ID ) {
					$entry->delete();
				}
			}
		}
	}

	/**
	 * Prepare items.
	 * Fetch and setup entries(items).
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$entries = null;
		$api = IB_Educator::get_instance();
		$statuses = edr_get_entry_statuses();
		$args = array(
			'per_page' => $this->get_items_per_page( 'entries_per_page', 10 ),
			'page'     => $this->get_pagenum(),
		);

		/**
		 * Search by status.
		 */
		if ( ! empty( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ) {
			$args['entry_status'] = $_GET['status'];
		}

		// Search by ID.
		if ( ! empty( $_GET['id'] ) ) {
			$args['entry_id'] = $_GET['id'];
		}

		// Search by course id.
		if ( ! empty( $_GET['course_id'] ) ) {
			$args['course_id'] = $_GET['course_id'];
		}

		if ( ! empty( $_GET['student'] ) ) {
			$user = get_user_by( 'slug', $_GET['student'] );

			if ( $user ) {
				$args['user_id'] = $user->ID;
			}
		}

		// Check capabilities.
		if ( current_user_can( 'manage_educator' ) ) {
			// Get all entries.
			$entries = $api->get_entries( $args, 'ARRAY_A' );
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			// Get the entries for the current lecturer's courses only.
			$course_ids = $api->get_lecturer_courses( get_current_user_id() );

			if ( ! empty( $course_ids ) ) {
				if ( empty( $args['course_id'] ) || ! in_array( $args['course_id'], $course_ids) ) {
					$args['course_id'] = $course_ids;
				}

				$entries = $api->get_entries( $args, 'ARRAY_A' );
			}
		}

		if ( ! empty( $entries ) ) {
			$this->set_pagination_args( array(
				'total_items' => $entries['num_items'],
				'per_page'    => $args['per_page'],
			) );

			$this->items = $entries['rows'];
		}
	}
}
