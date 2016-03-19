<?php

class Edr_Courses {
	protected static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {}

	/**
	 * Get course id.
	 *
	 * @param int $lesson_id
	 * @return int
	 */
	public function get_course_id( $lesson_id ) {
		return intval( get_post_meta( $lesson_id, '_ibedu_course', true ) );
	}

	public function get_register_status( $course_id ) {
		return get_post_meta( $course_id, '_ib_educator_register', true );
	}

	/**
	 * Get lessons of a course.
	 *
	 * @param int $course_id
	 * @return false|WP_Query
	 */
	public function get_lessons( $course_id ) {
		if ( ! is_numeric( $course_id ) ) {
			return false;
		}

		return new WP_Query( array(
			'post_type'      => 'ib_educator_lesson',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array( 'key' => '_ibedu_course', 'value' => $course_id, 'compare' => '=' ),
			)
		) );
	}

	/**
	 * Get an adjacent lesson.
	 *
	 * @param bool $previous
	 * @return mixed If global post object is not set returns null, if post is not found, returns empty string, else returns WP_Post.
	 */
	function get_adjacent_lesson( $previous = true ) {
		global $wpdb;

		if ( ! $lesson = get_post() ) {
			return null;
		}

		$course_id = $this->get_course_id( $lesson->ID );
		$cmp = $previous ? '<' : '>';
		$order = $previous ? 'DESC' : 'ASC';
		$join = "INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID";
		$where = $wpdb->prepare( "WHERE p.post_type = 'ib_educator_lesson' AND p.post_status = 'publish' AND p.menu_order $cmp %d AND pm.meta_key = '_ibedu_course' AND pm.meta_value = %d", $lesson->menu_order, $course_id );
		$sort = "ORDER BY p.menu_order $order";
		$query = "SELECT p.ID FROM $wpdb->posts as p $join $where $sort LIMIT 1";
		$result = $wpdb->get_var( $query );

		if ( null === $result ) {
			return '';
		}

		return get_post( $result );
	}
}
