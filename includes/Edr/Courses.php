<?php

class Edr_Courses {
	protected static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
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

	public function get_course_price( $course_id ) {
		return (float) get_post_meta( $course_id, '_ibedu_price', true );
	}

	public function get_register_status( $course_id ) {
		return get_post_meta( $course_id, '_ib_educator_register', true );
	}

	/**
	 * Get course prerequisites.
	 *
	 * @param int $course_id
	 * @return array
	 */
	public function get_course_prerequisites( $course_id ) {
		$prerequisites = get_post_meta( $course_id, '_ib_educator_prerequisites', true );

		return is_array( $prerequisites ) ? $prerequisites : array();
	}

	/**
	 * Check if user has completed course prerequisites.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return bool
	 */
	public function check_course_prerequisites( $course_id, $user_id ) {
		$prerequisites = $this->get_course_prerequisites( $course_id );

		if ( empty( $prerequisites ) ) {
			return true;
		}

		$edr_entries = Edr_Entries::get_instance();
		$completed_courses = $edr_entries->get_entries( array(
			'user_id'      => $user_id,
			'entry_status' => 'complete',
		) );

		if ( empty( $completed_courses ) ) {
			return false;
		}

		$prerequisites_satisfied = 0;

		foreach ( $completed_courses as $entry ) {
			if ( in_array( $entry->course_id, $prerequisites ) ) {
				$prerequisites_satisfied += 1;
			}
		}

		return ( $prerequisites_satisfied == count( $prerequisites ) );
	}

	/**
	 * Get number of lessons in a course.
	 *
	 * @param int $course_id
	 * @return int
	 */
	public function get_num_lessons( $course_id ) {
		global $wpdb;

		$num_lessons = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type='ib_educator_lesson' AND pm.meta_key='_ibedu_course' AND pm.meta_value = %d",
				$course_id
			)
		);

		return $num_lessons;
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
	 * Get lesson access status.
	 *
	 * @param int $lesson_id
	 * @return string
	 */
	public function get_lesson_access_status( $lesson_id ) {
		return get_post_meta( $lesson_id, '_ib_educator_access', true );
	}

	/**
	 * Get an adjacent lesson.
	 *
	 * @param bool $previous
	 * @return mixed If global post object is not set returns null, if post is not found, returns empty string, else returns WP_Post.
	 */
	public function get_adjacent_lesson( $previous = true ) {
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

	/**
	 * Get courses where the user is the author.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_lecturer_courses( $user_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_author = %d AND post_type = %s',
			$user_id, EDR_PT_COURSE );

		return $wpdb->get_col( $sql );
	}

	/**
	 * Get student's courses.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_student_courses( $user_id ) {
		global $wpdb;

		if ( absint( $user_id ) != $user_id ) {
			return false;
		}

		$ids = array();
		$edr_entries = Edr_Entries::get_instance();
		$entries = $edr_entries->get_entries( array( 'user_id' => $user_id ) );

		if ( ! empty( $entries ) ) {
			$statuses = array();

			foreach ( $entries as $row ) {
				$ids[] = $row->course_id;

				if ( isset( $statuses[ $row->entry_status ] ) ) {
					++$statuses[ $row->entry_status ];
				} else {
					$statuses[ $row->entry_status ] = 0;
				}
			}

			$query = new WP_Query( array(
				'post_type'      => 'ib_educator_course',
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'posts_per_page' => -1,
				'orderby'        => 'post__in',
				'order'          => 'ASC',
			) );

			if ( $query->have_posts() ) {
				$posts = array();

				foreach ( $query->posts as $post ) {
					$posts[ $post->ID ] = $post;
				}

				return array(
					'entries'  => $entries,
					'courses'  => $posts,
					'statuses' => $statuses
				);
			}
		}

		return false;
	}

	/**
	 * Get courses pending payment.
	 *
	 * @param int $user_id
	 * @return false|array of WP_Post objects
	 */
	public function get_pending_courses( $user_id ) {
		global $wpdb;
		$ids = array();
		$edr_payments = Edr_Payments::get_instance();
		$payments = $edr_payments->get_payments( array(
			'user_id'        => $user_id,
			'payment_status' => array( 'pending' ),
		), OBJECT_K );

		if ( ! empty( $payments ) ) {
			$payment_ids = array();

			foreach ( $payments as $payment ) {
				$ids[] = $payment->course_id;
				$payment_ids[ $payment->course_id ] = $payment->ID;
			}

			$query = new WP_Query( array(
				'post_type'      => EDR_PT_COURSE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'order'          => 'ASC',
			) );

			if ( $query->have_posts() ) {
				$posts = array();

				foreach ( $query->posts as $post ) {
					$post->edu_payment_id = $payment_ids[ $post->ID ];
					$post->edu_payment = $payments[ $post->edu_payment_id ];
					$posts[ $post->ID ] = $post;
				}

				return $posts;
			}
		}

		return false;
	}
}
