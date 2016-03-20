<?php

class Edr_Payments {
	protected static $instance = null;

	protected function __construct() {
		$tables = edr_db_tables();
		$this->payments = $tables['payments'];
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get payments.
	 *
	 * @param array $args
	 * @return array
	 */
	public function get_payments( $args, $output_type = null ) {
		global $wpdb;

		if ( is_null( $output_type ) ) {
			$output_type = OBJECT;
		}

		$sql = 'SELECT * FROM ' . $this->payments . ' WHERE 1';

		// Filter by payment_id.
		if ( isset( $args['payment_id'] ) ) {
			if ( is_array( $args['payment_id'] ) ) {
				$sql .= ' AND ID IN (' . implode( ',', array_map( 'absint', $args['payment_id'] ) ) . ')';
			} else {
				$sql .= $wpdb->prepare( ' AND ID = %d', $args['payment_id'] );
			}
		}

		// Filter by user_id.
		if ( isset( $args['user_id'] ) ) {
			$sql .= $wpdb->prepare( ' AND user_id = %d', $args['user_id'] );
		}

		// Filter by course_id.
		if ( isset( $args['course_id'] ) ) {
			$sql .= $wpdb->prepare( ' AND course_id = %d', $args['course_id'] );
		}

		// Filter by payment_type.
		if ( isset( $args['payment_type'] ) ) {
			$sql .= $wpdb->prepare( ' AND payment_type = %s', $args['payment_type'] );
		}

		// Filter by object_id.
		if ( isset( $args['object_id'] ) ) {
			$sql .= $wpdb->prepare( ' AND object_id = %d', $args['object_id'] );
		}

		// Filter by payment status.
		if ( isset( $args['payment_status'] ) && is_array( $args['payment_status'] ) ) {
			$sql .= $wpdb->prepare(
				' AND payment_status IN (' . implode( ',', array_fill( 0, count( $args['payment_status'] ), '%s' ) ) . ')',
				$args['payment_status']
			);
		}

		// With or without pagination
		$has_pagination = ( isset( $args['page'] ) && isset( $args['per_page'] )
			&& is_numeric( $args['page'] ) && is_numeric( $args['per_page'] ) );
		$pagination_sql = '';

		if ( $has_pagination ) {
			$num_rows = $wpdb->get_var( str_replace( 'SELECT *', 'SELECT count(1)', $sql ) );
			$pagination_sql .= ' LIMIT ' . ( ( $args['page'] - 1 ) * $args['per_page'] ) . ', ' . $args['per_page'];
		}

		$payments = $wpdb->get_results( $sql . ' ORDER BY payment_date DESC' . $pagination_sql, $output_type );

		if ( ! empty( $payments ) ) {
			$payments = array_map( 'edr_get_payment', $payments );
		}

		if ( $has_pagination ) {
			return array(
				'num_pages' => ceil( $num_rows / $args['per_page'] ),
				'num_items' => $num_rows,
				'rows'      => $payments,
			);
		}

		return $payments;
	}

	/**
	 * Setup payment item (e.g. course, membership).
	 *
	 * @param IB_Educator_Payment $payment
	 */
	public function setup_payment_item( $payment ) {
		if ( 'course' == $payment->payment_type ) {
			// Setup course entry.
			$edr_entries = Edr_Entries::get_instance();
			$entry = $edr_entries->get_entry( array( 'payment_id' => $payment->ID ) );

			if ( ! $entry ) {
				$entry = edr_get_entry();
				$entry->course_id = $payment->course_id;
				$entry->user_id = $payment->user_id;
				$entry->payment_id = $payment->ID;
				$entry->entry_status = 'inprogress';
				$entry->entry_date = date( 'Y-m-d H:i:s' );
				$entry->save();

				// Send notification email to the student.
				$student = get_user_by( 'id', $payment->user_id );
				$course = get_post( $payment->course_id, OBJECT, 'display' );

				if ( $student && $course ) {
					edr_send_notification(
						$student->user_email,
						'student_registered',
						array(
							'course_title' => $course->post_title,
						),
						array(
							'student_name'   => $student->display_name,
							'course_title'   => $course->post_title,
							'course_excerpt' => $course->post_excerpt,
						)
					);
				}
			}
		} elseif ( 'membership' == $payment->payment_type ) {
			// Setup membership.
			$ms = Edr_Memberships::get_instance();
			$ms->setup_membership( $payment->user_id, $payment->object_id );

			$student = get_user_by( 'id', $payment->user_id );
			$membership = $ms->get_membership( $payment->object_id );

			if ( $student && $membership ) {
				$user_membership = $ms->get_user_membership( $student->ID );
				$membership_meta = $ms->get_membership_meta( $membership->ID );
				$expiration = ( $user_membership ) ? $user_membership['expiration'] : 0;

				edr_send_notification(
					$student->user_email,
					'membership_register',
					array(),
					array(
						'student_name' => $student->display_name,
						'membership'   => $membership->post_title,
						'expiration'   => ( $expiration ) ? date_i18n( get_option( 'date_format' ), $expiration ) : __( 'None', 'ibeducator' ),
						'price'        => $ms->format_price( $membership_meta['price'], $membership_meta['duration'], $membership_meta['period'], false ),
					)
				);
			}
		}
	}

	/**
	 * Get user's billing data.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_billing_data( $user_id ) {
		$billing = get_user_meta( $user_id, '_ib_educator_billing', true );

		if ( ! is_array( $billing ) ) {
			$billing = array(
				'address'   => '',
				'address_2' => '',
				'city'      => '',
				'state'     => '',
				'postcode'  => '',
				'country'   => '',
			);
		}

		return $billing;
	}
}
