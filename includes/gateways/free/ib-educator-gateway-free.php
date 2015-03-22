<?php

class IB_Educator_Gateway_Free extends IB_Educator_Payment_Gateway {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'free';
		$this->title = __( 'Free', 'ibeducator' );
		$this->editable = false;
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $object_id, $user_id = 0, $payment_type = 'course', $atts = array() ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array( 'status' => '', 'redirect' => home_url( '/' ) );
		}

		// Add payment.
		$payment = IB_Educator_Payment::get_instance();
		$payment->user_id = $user_id;
		$payment->payment_type = $payment_type;
		$payment->payment_status = 'complete';
		$payment->payment_gateway = $this->get_id();
		$payment->amount = 0.0;
		$payment->currency = ib_edu_get_currency();

		if ( 'course' == $payment_type ) {
			$payment->course_id = $object_id;
			$payment->amount = ib_edu_get_course_price( $object_id );
		} elseif ( 'membership' == $payment_type ) {
			$payment->object_id = $object_id;
			$ms = IB_Educator_Memberships::get_instance();
			$payment->amount = $ms->get_price( $object_id );
		}

		if ( ! empty( $atts['ip'] ) ) {
			$payment->ip = $atts['ip'];
		}

		if ( 0.0 == $payment->amount ) {
			$payment->save();

			if ( $payment->ID ) {
				if ( 'course' == $payment->payment_type ) {
					// Setup course entry.
					$entry = IB_Educator_Entry::get_instance();
					$entry->course_id = $object_id;
					$entry->user_id = $user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry->save();
				} elseif ( 'membership' == $payment->payment_type ) {
					// Setup membership.
					$ms->setup_membership( $user_id, $object_id );
				}
			}
		}

		return array(
			'status'   => 'complete',
			'redirect' => get_permalink( $object_id ),
			'payment'  => $payment,
		);
	}
}