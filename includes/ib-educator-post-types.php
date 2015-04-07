<?php

class IB_Educator_Post_Types {
	/**
	 * @var null|array
	 */
	protected static $current_user_courses = null;

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 8 ); // Run before the plugin update.
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 8 ); // Run before the plugin update.
		add_filter( 'the_content', array( __CLASS__, 'lock_lessons' ) );
		add_filter( 'the_content_feed', array( __CLASS__, 'lock_lessons_in_feed' ) );
		add_filter( 'pre_option_rss_use_excerpt', array( __CLASS__, 'hide_content_in_feed' ) );
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		$permalink_settings = get_option( 'ib_educator_permalinks' );

		// Courses.
		$course_slug = ( $permalink_settings && ! empty( $permalink_settings['course_base'] ) ) ? $permalink_settings['course_base'] : _x( 'courses', 'course slug', 'ibeducator' );
		$courses_archive_slug = ( $permalink_settings && ! empty( $permalink_settings['courses_archive_base'] ) ) ? $permalink_settings['courses_archive_base'] : _x( 'courses', 'courses archive slug', 'ibeducator' );

		register_post_type(
			'ib_educator_course',
			apply_filters( 'ib_educator_cpt_course', array(
				'labels'              => array(
					'name'          => __( 'Courses', 'ibeducator' ),
					'singular_name' => __( 'Course', 'ibeducator' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => 'ib_educator_course',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => $courses_archive_slug,
				'rewrite'             => array( 'slug' => $course_slug ),
				'query_var'           => 'course',
				'can_export'          => true,
			) )
		);

		// Lessons.
		register_post_type(
			'ib_educator_lesson',
			apply_filters( 'ib_educator_cpt_lesson', array(
				'labels'              => array(
					'name'          => __( 'Lessons', 'ibeducator' ),
					'singular_name' => __( 'Lesson', 'ibeducator' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => 'ib_educator_lesson',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => ( ! empty( $permalink_settings['lessons_archive_base'] ) ) ? $permalink_settings['lessons_archive_base'] : _x( 'lessons', 'lesson slug', 'ibeducator' ),
				'rewrite'             => array(
					'slug' => ( ! empty( $permalink_settings['lesson_base'] ) ) ? $permalink_settings['lesson_base'] : _x( 'lessons', 'lessons archive slug', 'ibeducator' ),
				),
				'query_var'           => 'lesson',
				'can_export'          => true,
			) )
		);

		// Memberships.
		register_post_type(
			'ib_edu_membership',
			apply_filters( 'ib_educator_cpt_membership', array(
				'label'               => __( 'Membership Levels', 'ibeducator' ),
				'labels'              => array(
					'name'               => __( 'Membership Levels', 'ibeducator' ),
					'singular_name'      => __( 'Membership Level', 'ibeducator' ),
					'add_new_item'       => __( 'Add New Membership Level', 'ibeducator' ),
					'edit_item'          => __( 'Edit Membership Level', 'ibeducator' ),
					'new_item'           => __( 'New Membership Level', 'ibeducator' ),
					'view_item'          => __( 'View Membership Level', 'ibeducator' ),
					'search_items'       => __( 'Search Membership Levels', 'ibeducator' ),
					'not_found'          => __( 'No membership levels found', 'ibeducator' ),
					'not_found_in_trash' => __( 'No membership levels found in Trash', 'ibeducator' ),
				),
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => 'ib_educator_admin',
				'exclude_from_search' => true,
				'capability_type'     => 'ib_edu_membership',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'membership' ),
				'query_var'           => 'membership',
				'can_export'          => true,
			) )
		);
	}

	/**
	 * Register taxonomies.
	 */
	public static function register_taxonomies() {
		$permalink_settings = get_option( 'ib_educator_permalinks' );
		
		// Course categories.
		register_taxonomy(
			'ib_educator_category',
			'ib_educator_course',
			apply_filters( 'ib_educator_ct_category', array(
				'label'             => __( 'Course Categories', 'ibeducator' ),
				'public'            => true,
				'show_ui'           => true,
				'show_in_nav_menus' => true,
				'hierarchical'      => true,
				'rewrite'           => array(
					'slug' => ( ! empty( $permalink_settings['category_base'] ) ) ? $permalink_settings['category_base'] : _x( 'course-category', 'slug', 'ibeducator' ),
				),
				'capabilities'      => array(
					'assign_terms' => 'edit_ib_educator_courses',
				),
			) )
		);
	}

	/**
	 * Lock the lesson content.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function lock_lessons( $content ) {
		global $wpdb;

		if ( 'ib_educator_lesson' == get_post_type() ) {
			$post = get_post();
			$lesson_id = ! empty( $post ) ? $post->ID : 0;
			$user_id = get_current_user_id();

			// Get lesson's access option.
			$access = get_post_meta( $lesson_id, '_ib_educator_access', true );

			if ( 'public' == $access || ( 'logged_in' == $access && $user_id ) ) {
				return $content;
			}

			if ( $user_id && null === self::$current_user_courses ) {
				$tables = ib_edu_table_names();
				self::$current_user_courses = $wpdb->get_col( $wpdb->prepare(
					"SELECT course_id FROM {$tables['entries']} WHERE user_id = %d AND entry_status = 'inprogress'",
					$user_id
				) );
			}

			if ( ! self::$current_user_courses || ! in_array( ib_edu_get_course_id( $lesson_id ), self::$current_user_courses ) ) {
				$more_index = strpos( $content, '<span id="more-' );

				if ( false !== $more_index ) {
					$content = force_balance_tags( substr( $content, 0, $more_index ) );
				} elseif ( false === strpos( $content, 'class="more-link">' ) ) {
					$content = esc_html( $post->post_excerpt );
				}
			}
		}

		return $content;
	}

	/**
	 * Completely remove the lesson content from the feeds.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function lock_lessons_in_feed( $content ) {
		if ( 'ib_educator_lesson' == get_post_type() ) {
			return '';
		}

		return $content;
	}

	/**
	 * Don't output the lesson content in the feeds.
	 *
	 * @param bool $option_value
	 * @return bool|int
	 */
	public static function hide_content_in_feed( $option_value ) {
		if ( 'ib_educator_lesson' == get_post_type() ) {
			return 1;
		}

		return $option_value;
	}
}