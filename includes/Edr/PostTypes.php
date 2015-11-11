<?php

class Edr_PostTypes {
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
		add_filter( 'the_content', array( __CLASS__, 'lock_lessons' ), 15 );
		add_filter( 'the_content_feed', array( __CLASS__, 'lock_lessons_in_feed' ) );
		add_filter( 'pre_option_rss_use_excerpt', array( __CLASS__, 'hide_content_in_feed' ) );

		if ( 1 == ib_edu_get_option( 'lesson_comments', 'learning' ) ) {
			add_filter( 'comment_feed_where', array( __CLASS__, 'hide_comments_in_feed' ), 10, 2 );
			add_filter( 'comments_clauses', array( __CLASS__, 'hide_lesson_comments' ), 10, 2 );
		}
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
		$supports = array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' );

		if ( 1 == ib_edu_get_option( 'lesson_comments', 'learning' ) ) {
			$supports[] = 'comments';
		}

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
				'supports'            => $supports,
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

	public static function set_current_user_courses( $user_id ) {
		global $wpdb;

		if ( ! $user_id || ! is_null( self::$current_user_courses ) ) {
			return;
		}

		$tables = ib_edu_table_names();

		self::$current_user_courses = $wpdb->get_col( $wpdb->prepare(
			"SELECT course_id FROM {$tables['entries']} WHERE user_id = %d AND entry_status = 'inprogress'",
			$user_id
		) );
	}

	public static function clear_current_user_courses() {
		self::$current_user_courses = null;
	}

	public static function can_user_access( $object, $object_id ) {
		$access = false;
		$user_id = get_current_user_id();

		if ( 'lesson' == $object ) {
			$lesson_access = ib_edu_lesson_access( $object_id );

			if ( 'public' == $lesson_access ) {
				$access = true;
			} elseif ( $user_id ) {
				if ( 'logged_in' == $lesson_access ) {
					$access = true;
				} else {
					self::set_current_user_courses( $user_id );

					if ( in_array( ib_edu_get_course_id( $object_id ), self::$current_user_courses ) ) {
						$access = true;
					}
				}
			}
		}

		return $access;
	}

	/**
	 * Lock the lesson content.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function lock_lessons( $content ) {
		$post = get_post();

		if ( ! empty( $post ) && 'ib_educator_lesson' == $post->post_type ) {
			if ( ! self::can_user_access( 'lesson', $post->ID ) ) {
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

	/**
	 * Hide lesson comments from feeds.
	 *
	 * @param string $limits
	 * @param WP_Query $query
	 * @return string
	 */
	public static function hide_comments_in_feed( $where, $query ) {
		global $wpdb;

		if ( ! $query->is_main_query() ) {
			return $where;
		}

		if ( ! $query->is_singular ) {
			// General comments feed.
			$where .= " AND $wpdb->posts.post_type <> 'ib_educator_lesson'";
		} elseif ( 'ib_educator_lesson' == $query->get( 'post_type' ) ) {
			// Comments feed on the single lesson page.
			if ( ! self::can_user_access( 'lesson', $query->posts[0]->ID ) ) {
				$where .= " AND 1 = 0";
			}
		}

		return $where;
	}

	/**
	 * Exclude lesson comments from the comment query.
	 *
	 * @param array $pieces
	 * @param WP_Comment_Query $comment_query
	 * @return array
	 */
	public static function hide_lesson_comments( $pieces, $comment_query ) {
		global $wpdb;

		if ( false !== strpos( $pieces['join'], " $wpdb->posts ON $wpdb->posts" ) ) {
			$pieces['where'] .= " AND $wpdb->posts.post_type <> 'ib_educator_lesson'";
		}
		
		return $pieces;
	}
}
