<?php

class IB_Educator_Actions {
	/**
	 * Cancel student's payment for a course.
	 */
	public static function cancel_payment() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_cancel_payment' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) return;

		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;

		if ( ! $payment_id ) return;

		$payment = IB_Educator_Payment::get_instance( $payment_id );

		// User may cancel his/her pending payments only.
		if ( 'pending' == $payment->payment_status && $payment->user_id == get_current_user_id() ) {
			if ( $payment->update_status( 'cancelled' ) ) {
				wp_redirect( ib_edu_get_endpoint_url( 'edu-message', 'payment-cancelled', get_permalink() ) );
				exit;
			}
		}
	}

	/**
	 * Submit quiz.
	 */
	public static function submit_quiz() {
		if ( empty( $_POST ) ) return;

		$lesson_id = get_the_ID();
		$user_id = get_current_user_id();
		$api = IB_Educator::get_instance();

		// Verify nonce.
		check_admin_referer( 'ibedu_submit_quiz_' . $lesson_id );

		$questions = $api->get_questions( array( 'lesson_id' => $lesson_id ) );
		$num_questions = count( $questions );
		$num_answers = 0;

		// The student has to submit the answers to all questions.
		if ( ! isset( $_POST['answers'] ) ) {
			ib_edu_message( 'quiz', 'empty-answers' );
			return;
		} elseif ( is_array( $_POST['answers'] ) ) {
			foreach ( $_POST['answers'] as $answer ) {
				if ( ! empty( $answer ) ) ++$num_answers;
			}

			if ( $num_answers != $num_questions ) {
				ib_edu_message( 'quiz', 'empty-answers' );
				return;
			}
		}

		if ( ! $questions ) return;

		$user_answer = '';
		$answer = null;
		$question_meta = null;
		$entry = $api->get_entry( array(
			'user_id'      => $user_id,
			'course_id'    => ib_edu_get_course_id( $lesson_id ),
			'entry_status' => 'inprogress'
		) );
		$automatic_grade = true;

		if ( ! $entry ) return;

		// Process answers only if the quiz wasn't yet submitted.
		if ( $api->is_quiz_submitted( $lesson_id, $entry->ID ) ) return;

		$answered = 0;
		$correct = 0;
		$choices = $api->get_choices( $lesson_id, true );

		// Check answers to the quiz questions.
		foreach ( $questions as $question ) {
			if ( ! isset( $_POST['answers'][ $question->ID ] ) ) {
				// Student has to submit an answer.
				continue;
			}

			// Every question type needs a specific way to check for the valid answer.
			switch ( $question->question_type ) {
				// Multiple Choice Question.
				case 'multiplechoice':
					$user_answer = absint( $_POST['answers'][ $question->ID ] );

					if ( isset( $choices[ $question->ID ] ) && isset( $choices[ $question->ID ][ $user_answer ] ) ) {
						$choice = $choices[ $question->ID ][ $user_answer ];

						// Add answer to database.
						$added = $api->add_student_answer( array(
							'question_id' => $question->ID,
							'entry_id'    => $entry->ID,
							'correct'     => $choice->correct,
							'choice_id'   => $choice->ID
						) );

						if ( 1 == $added ) ++$answered;
						if ( 1 == $choice->correct ) ++$correct;
					}

					break;

				// Written Answer Question.
				case 'writtenanswer':
					// We cannot check written answers automatically.
					if ( $automatic_grade ) $automatic_grade = false;

					$user_answer = stripslashes( $_POST['answers'][ $question->ID ] );

					if ( empty( $user_answer ) ) {
						continue;
					}

					// Add answer to database.
					$added = $api->add_student_answer( array(
						'question_id' => $question->ID,
						'entry_id'    => $entry->ID,
						'correct'     => -1,
						'answer_text' => $user_answer
					) );

					if ( 1 == $added ) ++$answered;
					
					break;
			}
		}

		if ( $answered == $num_questions ) {
			$grade_data = array(
				'lesson_id' => $lesson_id,
				'entry_id'  => $entry->ID,
			);

			if ( $automatic_grade ) {
				$grade_data['grade'] = round( $correct / $answered * 100 );
				$grade_data['status'] = 'approved';
			} else {
				$grade_data['grade'] = 0;
				$grade_data['status'] = 'pending';
			}

			$api->add_quiz_grade( $grade_data );

			wp_redirect( ib_edu_get_endpoint_url( 'edu-message', 'quiz-submitted', get_permalink() ) );
			exit;
		}
	}

	/**
	 * Pay for a course.
	 */
	public static function payment() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_submit_payment' ) ) {
			return;
		}

		do_action( 'ib_educator_before_payment' );

		// Get post id and payment type (course or membership).
		$post_id = 0; // either course id or membership id
		$payment_type = 'course';

		if ( isset( $_POST['course_id'] ) ) {
			$post_id = absint( $_POST['course_id'] );
		} elseif ( isset( $_POST['membership_id'] ) ) {
			$post_id = absint( $_POST['membership_id'] );
			$payment_type = 'membership';
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$user_id = get_current_user_id();
		$errors = new WP_Error();

		// Check the course prerequisites.
		if ( 'course' == $payment_type ) {
			$api = IB_Educator::get_instance();

			if ( ! $api->check_prerequisites( $post_id, $user_id ) ) {
				$prerequisites_html = '';
				$prerequisites = $api->get_prerequisites( $post_id );
				$courses = get_posts( array(
					'post_type'   => 'ib_educator_course',
					'post_status' => 'publish',
					'include'     => $prerequisites,
				) );

				if ( ! empty( $courses ) ) {
					foreach ( $courses as $course ) {
						$prerequisites_html .= '<br><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a>';
					}
				}

				$errors->add( 'prerequisites', sprintf( __( 'You have to complete the prerequisites for this course: %s', 'ibeducator' ), $prerequisites_html ) );
				ib_edu_message( 'payment_errors', $errors );
				return;
			}
		}
		
		// Get the payment method.
		$payment_method = '';
		$gateways = IB_Educator_Main::get_gateways();
		
		if ( ! isset( $_POST['payment_method'] ) || ! array_key_exists( $_POST['payment_method'], $gateways ) ) {
			$errors->add( 'empty_payment_method', __( 'Please select a payment method.', 'ibeducator' ) );
		} else {
			$payment_method = $_POST['payment_method'];
		}

		/**
		 * Filter the validation of the payment form.
		 *
		 * @param WP_Error $errors
		 */
		$errors = apply_filters( 'ib_educator_register_form_validate', $errors, $post );

		// Attempt to register the user.
		if ( $errors->get_error_code() ) {
			ib_edu_message( 'payment_errors', $errors );
			return;
		} elseif ( ! $user_id ) {
			$user_data = apply_filters( 'ib_educator_register_user_data', array( 'role' => 'student' ), $post );
			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				ib_edu_message( 'payment_errors', $user_id );
				return;
			} else {
				// Setup the password change nag.
				update_user_option( $user_id, 'default_password_nag', true, true );

				// Send the new user notifications.
				wp_new_user_notification( $user_id, $user_data['user_pass'] );

				do_action( 'ib_educator_new_student', $user_id, $post );

				// Log the user in.
				wp_set_auth_cookie( $user_id );
			}
		} else {
			do_action( 'ib_educator_update_student', $user_id, $post );
		}

		$can_pay = true;

		if ( 'course' == $payment_type ) {
			$access_status = IB_Educator::get_instance()->get_access_status( $post_id, $user_id );

			// Student can pay for a course only if he/she completed this course or didn't register for it yet.
			$can_pay = in_array( $access_status, array( 'course_complete', 'forbidden' ) );
		}

		if ( $can_pay ) {
			// Process payment.
			$atts = array();

			if ( ib_edu_get_option( 'payment_ip', 'settings' ) ) {
				$atts['ip'] = $_SERVER['REMOTE_ADDR'];
			}
			
			$result = $gateways[ $payment_method ]->process_payment( $post_id, $user_id, $payment_type, $atts );
			
			/**
			 * Fires when the payment record has been created.
			 *
			 * The payment may not be confirmed yet.
			 *
			 * @param null|IB_Educator_Payment
			 */
			do_action( 'ib_educator_payment_processed', ( isset( $result['payment'] ) ? $result['payment'] : null ) );

			// Go to the next step(e.g. thank you page).
			wp_safe_redirect( $result['redirect'] );
			
			exit;
		}
	}

	/**
	 * Join the course if membership allows.
	 */
	public static function join() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_join' ) ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		// Get course id.
		$course_id = get_the_ID();

		if ( ! $course_id ) {
			return;
		}

		// Get course.
		$course = get_post( $course_id );

		if ( ! $course || 'ib_educator_course' != $course->post_type ) {
			return;
		}

		$api = IB_Educator::get_instance();
		$errors = new WP_Error();

		// Check the course prerequisites.
		if ( ! $api->check_prerequisites( $course_id, $user_id ) ) {
			$prerequisites_html = '';
			$prerequisites = $api->get_prerequisites( $course_id );
			$courses = get_posts( array(
				'post_type'   => 'ib_educator_course',
				'post_status' => 'publish',
				'include'     => $prerequisites,
			) );

			if ( ! empty( $courses ) ) {
				foreach ( $courses as $course ) {
					$prerequisites_html .= '<br><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a>';
				}
			}

			$errors->add( 'prerequisites', sprintf( __( 'You have to complete the prerequisites for this course: %s', 'ibeducator' ), $prerequisites_html ) );
			ib_edu_message( 'course_join_errors', $errors );
			return;
		}

		// Make sure the user can join this course.
		$ms = IB_Educator_Memberships::get_instance();

		if ( ! $ms->membership_can_access( $course_id, $user_id ) ) {
			return;
		}

		// Check if the user already has an inprogress entry for this course.
		$entries = $api->get_entries( array(
			'course_id'    => $course_id,
			'user_id'      => $user_id,
			'entry_status' => 'inprogress',
		) );

		if ( ! empty( $entries ) ) {
			return;
		}

		$user_membership = $ms->get_user_membership( $user_id );

		$entry = IB_Educator_Entry::get_instance();
		$entry->course_id    = $course_id;
		$entry->object_id    = $user_membership['membership_id'];
		$entry->user_id      = $user_id;
		$entry->entry_origin = 'membership';
		$entry->entry_status = 'inprogress';
		$entry->entry_date   = date( 'Y-m-d H:i:s' );
		$entry->save();
	}

	/**
	 * Resume entry.
	 */
	public static function resume_entry() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_resume_entry' ) ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();
		if ( ! $user_id ) return;

		// Get entry id.
		if ( ! isset( $_POST['entry_id'] ) ) return;
		$entry_id = $_POST['entry_id'];

		// Get entry.
		$entry = IB_Educator_Entry::get_instance( $entry_id );
		if ( ! $entry ) return;

		$ms = IB_Educator_Memberships::get_instance();

		// Check if there is an "inprogress" entry for this course.
		$api = IB_Educator::get_instance();
		$inprogress_entry = $api->get_entry( array(
			'entry_status' => 'inprogress',
			'course_id'    => $entry->course_id,
			'user_id'      => $user_id,
		) );

		// Make sure that this entry belongs to the current user.
		// Make sure that the current membership gives access to this entry's course.
		if ( $inprogress_entry || $entry->user_id != $user_id || ! $ms->membership_can_access( $entry->course_id, $user_id ) ) {
			return;
		}

		$entry->entry_status = 'inprogress';
		$entry->save();

		wp_safe_redirect( get_permalink() );
	}

	/**
	 * Pause the user's membership.
	 */
	public static function pause_membership() {
		if ( 1 != ib_edu_get_option( 'pause_memberships', 'memberships' ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_educator_pause_membership' ) ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return;
		}

		$ms = IB_Educator_Memberships::get_instance();
		$user_membership = $ms->get_user_membership( $user_id );

		if ( $user_membership && 'active' == $user_membership['status'] ) {
			$ms->pause_membership( $user_id );
		}
	}

	/**
	 * Resume the user's membership.
	 */
	public static function resume_membership() {
		if ( 1 != ib_edu_get_option( 'pause_memberships', 'memberships' ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_educator_resume_membership' ) ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$ms = IB_Educator_Memberships::get_instance();
		$user_membership = $ms->get_user_membership( $user_id );

		if ( $user_membership && 'paused' == $user_membership['status'] ) {
			$ms->resume_membership( $user_id );
		}
	}
}