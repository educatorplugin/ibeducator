<?php

/**
 * Admin for the course syllabus feature.
 */
class Edr_Admin_Syllabus {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'edr_autocomplete_ajax', array( $this, 'ajax_get_lessons' ) );
		add_action( 'save_post', array( $this, 'update_syllabus' ), 10, 3 );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( $screen && 'post' == $screen->base && 'ib_educator_course' == $screen->id ) {
			wp_enqueue_style( 'edr-syllabus', IBEDUCATOR_PLUGIN_URL . 'admin/css/syllabus.css', array(), '1.0' );
			wp_enqueue_script( 'edr-syllabus', IBEDUCATOR_PLUGIN_URL . 'admin/js/syllabus.js', array( 'jquery', 'underscore', 'backbone', 'jquery-ui-sortable' ), '1.0', true );
			wp_localize_script( 'edr-syllabus', 'edrSyllabusText', array(
				'autoCompleteNonce' => wp_create_nonce( 'edr_syllabus_autocomplete' ),
			) );
		}
	}

	/**
	 * Add the syllabus meta box to a course edit page.
	 */
	public function add_meta_box() {
		add_meta_box(
			'edr_syllabus',
			__( 'Syllabus', 'ibeducator' ),
			array( $this, 'output_meta_box' ),
			'ib_educator_course'
		);
	}

	/**
	 * Display the syllabus meta box.
	 *
	 * @param WP_Post $post
	 */
	public function output_meta_box( $post ) {
		wp_nonce_field( 'edr_save_syllabus', 'edr_save_syllabus_nonce' );
		?>
			<div id="edr-syllabus">
				<input type="hidden" name="edr_syllabus_status" value="loading">
				<div class="edr-loading"><?php _e( 'Loading', 'ib-educator' ); ?></div>
				<ul class="groups"></ul>
				<p>
					<button class="add-group button"><?php _e( 'Add Group', 'ibeducator' ); ?></button>
				</p>
			</div>
			<script type="text/html" id="edr-syllabus-group-view">
				<div class="group-header">
					<div class="handle dashicons dashicons-sort"></div>
					<input type="text" class="group-title" name="edr_syllabus_groups[<%= group_id %>]" value="<%= title %>">
					<button class="remove-group remove">&times;</button>
				</div>
				<div class="group-body">
					<ul class="lessons"></ul>
					<div class="add-lesson-container">
						<div class="ib-edu-autocomplete">
							<input
								type="text"
								class="select-lessons"
								autocomplete="off"
								value=""
								data-label="<?php _e( 'Select Lesson', 'ibeducator' ); ?>">
						</div>
						<button class="add-lesson button"><?php _e( 'Add', 'ibeducator' ); ?></button>
					</div>
				</div>
			</script>
			<script type="text/html" id="edr-syllabus-lesson-view">
				<div class="handle dashicons dashicons-sort"></div>
				<h4><%= title %></h4>
				<input type="hidden" name="edr_syllabus_lessons[<%= group_id %>][]" value="<%= post_id %>">
				<button class="remove-lesson remove">&times;</button>
			</script>
			<?php
				// Setup initial data for the syllabus manager.
				$js_obj = array();
				$syllabus = get_post_meta( $post->ID, '_edr_syllabus', true );

				if ( is_array( $syllabus ) ) {
					$lesson_ids = array();
					$lessons = array();

					foreach ( $syllabus as $group ) {
						if ( ! empty( $group['lessons'] ) ) {
							foreach ( $group['lessons'] as $lesson_id ) {
								$lesson_ids[] = $lesson_id;
							}
						}
					}

					if ( ! empty( $lesson_ids ) ) {
						// Get all required lessons, so we won't need
						// to execute a database query for each group.
						$tmp = get_posts( array(
							'post_type'      => 'ib_educator_lesson',
							'post_status'    => 'publish',
							'include'        => $lesson_ids,
							'posts_per_page' => -1,
						) );

						foreach ( $tmp as $lesson ) {
							$lessons[ $lesson->ID ] = $lesson;
						}

						unset( $tmp );
					}

					foreach ( $syllabus as $group ) {
						$group_lessons = array();

						if ( ! empty( $group['lessons'] ) ) {
							foreach ( $group['lessons'] as $lesson_id ) {
								$edit_url = get_edit_post_link( $lesson_id );
								$group_lessons[] = array(
									'post_id' => (int) $lesson_id,
									'title'   => isset( $lessons[ $lesson_id ] ) ? '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $lessons[ $lesson_id ]->post_title ) . '</a>' : '',
								);
							}
						}

						$js_obj[] = array(
							'title'   => esc_html( $group['title'] ),
							'lessons' => $group_lessons,
						);
					}
				}
			?>
			<script>
				var edrSyllabus = <?php echo json_encode( $js_obj ); ?>;
			</script>
		<?php
	}

	/**
	 * Update syllabus for a given course(post).
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $update
	 */
	public function update_syllabus( $post_id, $post, $update ) {
		global $wpdb;

		if ( ! isset( $_POST['edr_save_syllabus_nonce'] ) || ! wp_verify_nonce( $_POST['edr_save_syllabus_nonce'], 'edr_save_syllabus' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ib_educator_course' != $post->post_type || ! current_user_can( 'edit_ib_educator_course', $post_id ) ) {
			return;
		}

		// Make sure that the user saved the post when the syllabus manager finished loading.
		if ( ! isset( $_POST['edr_syllabus_status'] ) || 'ready' != $_POST['edr_syllabus_status'] ) {
			return;
		}

		$syllabus = array();
		$groups = isset( $_POST['edr_syllabus_groups'] ) ? $_POST['edr_syllabus_groups'] : null;

		if ( is_array( $groups ) ) {
			$lessons = array();
			$menu_order_case = '';
			$menu_order_in = array();
			$menu_order = 1;

			// Validate submitted lessons.
			if ( ! empty( $_POST['edr_syllabus_lessons'] ) ) {
				foreach ( $_POST['edr_syllabus_lessons'] as $group_id => $tmp_lessons ) {
					// If no lessons, continue.
					if ( ! is_array( $tmp_lessons ) ) {
						continue;
					}

					// Get submitted lessons for them to be verified.
					$tmp = get_posts( array(
						'post_type'      => 'ib_educator_lesson',
						'post_status'    => 'publish',
						'include'        => array_map( 'intval', $tmp_lessons ),
						'orderby'        => 'post__in',
						'posts_per_page' => -1,
 					) );

 					if ( ! empty( $tmp ) ) {
 						$lessons[ $group_id ] = array();

 						foreach ( $tmp as $tmp_lesson ) {
 							// Can the current user edit this lesson?
 							if ( ! current_user_can( 'edit_ib_educator_lesson', $tmp_lesson->ID ) ) {
 								continue;
 							}

 							// Add this lesson to the group.
 							$lessons[ $group_id ][] = $tmp_lesson->ID;

							// Update the menu order of this lesson.
							$menu_order_case .= ' WHEN ' . intval( $tmp_lesson->ID ) . ' THEN ' . $menu_order++;
							$menu_order_in[] = (int) $tmp_lesson->ID;
						}

 						unset( $tmp );
 					}
				}
			}

			foreach ( $groups as $group_id => $group_title ) {
				$group_data = array();
				$group_data['title'] = sanitize_text_field( $group_title );

				if ( isset( $lessons[ $group_id ] ) ) {
					$group_data['lessons'] = $lessons[ $group_id ];

					// Assign lessons to the current course.
					foreach ( $group_data['lessons'] as $lesson_id ) {
						update_post_meta( $lesson_id, '_ibedu_course', $post->ID );
					}
				}

				// Add group to syllabus.
				$syllabus[] = $group_data;
			}
		}

		// Update syllabus.
		update_post_meta( $post_id, '_edr_syllabus', $syllabus );

		// Update the menu order of the lessons.
		if ( '' != $menu_order_case ) {
			$wpdb->query( 'UPDATE ' . $wpdb->posts . ' SET menu_order = CASE ID' . $menu_order_case .
				' END WHERE ID IN (' . implode( ',', $menu_order_in ) . ')' );
		}
	}

	/**
	 * Get lessons for the lesson autocomplete field.
	 *
	 * @param string $entity
	 */
	public function ajax_get_lessons( $entity ) {
		if ( 'admin_syllabus_lessons' != $entity ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_syllabus_autocomplete' ) ) {
			exit();
		}

		if ( ! isset( $_GET['current_course_id'] ) ) {
			exit();
		}

		$current_course_id = intval( $_GET['current_course_id'] );

		$args = array(
			'post_type'      => 'ib_educator_lesson',
			'post_status'    => 'publish',
			'posts_per_page' => 15,
			'meta_query'     => array(
				array(
					'key'     => '_ibedu_course',
					'value'   => $current_course_id,
					'compare' => '='
				)
			),
		);

		if ( ! empty( $_GET['input'] ) ) {
			$args['s'] = $_GET['input'];
		}

		$response = array();
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$response[] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
				);
			}

			wp_reset_postdata();
		}

		echo json_encode( $response );

		exit();
	}
}
