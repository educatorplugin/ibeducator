<?php

class IB_Educator_Admin_Meta {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_lesson_meta_box' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'save_course_meta_box' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'save_membership_meta_box' ), 10, 3 );
	}

	/**
	 * Add meta boxes.
	 */
	public static function add_meta_boxes() {
		// Course meta box.
		add_meta_box(
			'ib_educator_course_meta',
			__( 'Course Settings', 'ibeducator' ),
			array( __CLASS__, 'course_meta_box' ),
			'ib_educator_course'
		);

		// Lesson meta box.
		add_meta_box(
			'ib_educator_lesson_meta',
			__( 'Lesson Settings', 'ibeducator' ),
			array( __CLASS__, 'lesson_meta_box' ),
			'ib_educator_lesson'
		);

		// Membership meta box.
		add_meta_box(
			'ib_educator_membership',
			__( 'Membership Settings', 'ibeducator' ),
			array( __CLASS__, 'membership_meta_box' ),
			'ib_edu_membership'
		);
	}

	/**
	 * Output course meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function course_meta_box( $post ) {
		include IBEDUCATOR_PLUGIN_DIR . 'admin/meta-boxes/course.php';
	}

	/**
	 * Output lesson meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function lesson_meta_box( $post ) {
		include IBEDUCATOR_PLUGIN_DIR . 'admin/meta-boxes/lesson.php';
	}

	/**
	 * Output membership meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function membership_meta_box( $post ) {
		include IBEDUCATOR_PLUGIN_DIR . 'admin/meta-boxes/membership.php';
	}

	/**
	 * Save tax data for a course or membership.
	 */
	protected static function save_tax_data( $post_id ) {
		if ( isset( $_POST['_ib_educator_tax_class'] ) ) {
			update_post_meta( $post_id, '_ib_educator_tax_class', sanitize_text_field( $_POST['_ib_educator_tax_class'] ) );
		}
	}

	/**
	 * Save course meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_course_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['ib_educator_course_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ib_educator_course_meta_box_nonce'], 'ib_educator_course_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ib_educator_course' != $post->post_type || ! current_user_can( 'edit_ib_educator_course', $post_id ) ) {
			return;
		}

		// Registration.
		$register = ( isset( $_POST['_ib_educator_register'] ) && 'open' != $_POST['_ib_educator_register'] ) ? 'closed' : 'open';
		update_post_meta( $post_id, '_ib_educator_register', $register );

		// Price.
		$price = ( isset( $_POST['_ibedu_price'] ) && is_numeric( $_POST['_ibedu_price'] ) ) ? $_POST['_ibedu_price'] : '';
		update_post_meta( $post_id, '_ibedu_price', $price );

		// Difficulty.
		$difficulty = ( isset( $_POST['_ib_educator_difficulty'] ) ) ? $_POST['_ib_educator_difficulty'] : '';

		if ( empty( $difficulty ) || array_key_exists( $difficulty, ib_edu_get_difficulty_levels() ) ) {
			update_post_meta( $post_id, '_ib_educator_difficulty', $difficulty );
		}

		// Prerequisites.
		if ( isset( $_POST['_ib_educator_prerequisite'] ) ) {
			$prerequisites = array();

			if ( is_numeric( $_POST['_ib_educator_prerequisite'] ) ) {
				$prerequisites[] = absint( $_POST['_ib_educator_prerequisite'] );
			}

			update_post_meta( $post_id, '_ib_educator_prerequisites', $prerequisites );
		}

		self::save_tax_data( $post_id );
	}

	/**
	 * Save lesson meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_lesson_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['ib_educator_lesson_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ib_educator_lesson_meta_box_nonce'], 'ib_educator_lesson_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ib_educator_lesson' != $post->post_type || ! current_user_can( 'edit_ib_educator_lesson', $post_id ) ) {
			return;
		}

		// Lesson access.
		$access_options = array( 'registered', 'logged_in', 'public' );
		$access = 'registered';

		if ( isset( $_POST['_ib_educator_access'] ) && in_array( $_POST['_ib_educator_access'], $access_options ) ) {
			$access = $_POST['_ib_educator_access'];
		}

		update_post_meta( $post_id, '_ib_educator_access', $access );

		// Course.
		$value = ( isset( $_POST['_ibedu_course'] ) && is_numeric( $_POST['_ibedu_course'] ) ) ? $_POST['_ibedu_course'] : '';
		update_post_meta( $post_id, '_ibedu_course', $value );
	}

	/**
	 * Save membership meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_membership_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['ib_edu_membership_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['ib_edu_membership_nonce'], 'ib_edu_membership' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ib_edu_membership' != get_post_type( $post_id ) || ! current_user_can( 'edit_ib_edu_membership', $post_id ) ) {
			return;
		}

		$ms = IB_Educator_Memberships::get_instance();
		$meta = $ms->get_membership_meta( $post_id );

		// Price.
		if ( isset( $_POST['_ib_educator_price'] ) ) {
			$meta['price'] = (float) $_POST['_ib_educator_price'];
		}

		// Duration.
		if ( isset( $_POST['_ib_educator_duration'] ) ) {
			$meta['duration'] = intval( $_POST['_ib_educator_duration'] );
		}

		// Period.
		if ( isset( $_POST['_ib_educator_period'] ) && array_key_exists( $_POST['_ib_educator_period'], $ms->get_periods() ) ) {
			$meta['period'] = $_POST['_ib_educator_period'];
		}

		// Categories.
		if ( isset( $_POST['_ib_educator_categories'] ) && is_array( $_POST['_ib_educator_categories'] ) ) {
			$meta['categories'] = array_map( 'absint', $_POST['_ib_educator_categories'] );
		}

		update_post_meta( $post_id, '_ib_educator_membership', $meta );

		self::save_tax_data( $post_id );
	}
}