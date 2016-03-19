<?php

/**
 * @deprecated 1.8.0
 */
function ib_edu_lesson_access( $lesson_id ) {
	edr_deprecated_function( 'ib_edu_lesson_access', '1.8.0', 'Edr_Courses::get_instance()->get_lesson_access_status' );

	Edr_Courses::get_instance()->get_lesson_access_status( $lesson_id );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_user_can_edit_lesson( $lesson_id ) {
	edr_deprecated_function( 'ib_edu_user_can_edit_lesson', '1.8.0', 'Edr_Access::get_instance()->can_edit_lesson' );

	return Edr_Access::get_instance()->can_edit_lesson( $lesson_id );
}

/**
 * @deprecated 1.8.0
 */
function _ib_edu_deprecated_function( $function, $version, $replacement = null ) {
	edr_deprecated_function( $function, $version, $replacement );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_purchase_link( $atts ) {
	edr_deprecated_function( 'ib_edu_purchase_link', '1.8.0', 'edr_purchase_link' );

	return edr_purchase_link( $atts );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_location( $part = null ) {
	edr_deprecated_function( 'ib_edu_get_location', '1.8.0', 'edr_get_location' );

	return edr_get_location( $part );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_collect_billing_data( $object ) {
	edr_deprecated_function( 'ib_edu_collect_billing_data', '1.8.0', 'edr_collect_billing_data' );

	return edr_collect_billing_data( $object );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_is_payment() {
	edr_deprecated_function( 'ib_edu_is_payment', '1.8.0', 'edr_is_payment' );

	return edr_is_payment();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_page_title() {
	edr_deprecated_function( 'ib_edu_page_title', '1.8.0', 'edr_page_title' );

	edr_page_title();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_send_notification( $to, $template, $subject_vars, $template_vars ) {
	edr_deprecated_function( 'ib_edu_send_notification', '1.8.0', 'edr_send_notification' );

	return edr_send_notification( $to, $template, $subject_vars, $template_vars );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_message( $key, $value = null ) {
	edr_deprecated_function( 'ib_edu_message', '1.8.0', 'edr_internal_message' );

	return edr_internal_message( $key, $value );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_format_grade( $grade ) {
	edr_deprecated_function( 'ib_edu_format_grade', '1.8.0', 'edr_format_grade' );

	return edr_format_grade( $grade );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_strip_zeroes( $number, $decimal_point ) {
	edr_deprecated_function( 'ib_edu_strip_zeroes', '1.8.0', 'edr_strip_zeroes' );

	return edr_strip_zeroes( $number, $decimal_point );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_currency_symbol( $currency ) {
	edr_deprecated_function( 'ib_edu_get_currency_symbol', '1.8.0', 'edr_get_currency_symbol' );

	return edr_get_currency_symbol( $currency );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_currency() {
	edr_deprecated_function( 'ib_edu_get_currency', '1.8.0', 'edr_get_currency' );

	return edr_get_currency();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_currencies() {
	edr_deprecated_function( 'ib_edu_get_currencies', '1.8.0', 'edr_get_currencies' );

	return edr_get_currencies();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_option( $key, $section ) {
	edr_deprecated_function( 'ib_edu_get_option', '1.8.0', 'edr_get_option' );

	return edr_get_option( $key, $section );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_settings() {
	edr_deprecated_function( 'ib_edu_get_settings', '1.8.0', 'edr_get_settings' );

	return edr_get_settings();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_request_url( $request ) {
	edr_deprecated_function( 'ib_edu_request_url', '1.8.0', 'Edr_RequestDispatcher::get_url' );

	return Edr_RequestDispatcher::get_url( $request );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_page_id( $page_name ) {
	edr_deprecated_function( 'ib_edu_page_id', '1.8.0', 'edr_get_page_id' );

	return edr_get_page_id( $page_name );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_format_price( $price, $apply_filters = true, $symbol = true ) {
	edr_deprecated_function( 'ib_edu_format_price', '1.8.0', 'edr_format_price' );

	return edr_format_price( $price, $apply_filters, $symbol );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_format_course_price( $price ) {
	edr_deprecated_function( 'ib_edu_format_course_price', '1.8.0', 'edr_format_price' );

	return edr_format_price( $price );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_table_names() {
	edr_deprecated_function( 'ib_edu_table_names', '1.8.0', 'edr_db_tables' );

	return edr_db_tables();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_student_can_study( $lesson_id ) {
	edr_deprecated_function( 'ib_edu_student_can_study', '1.8.0', 'Edr_Access::get_instance()->can_study_lesson( $lesson_id )' );

	return Edr_Access::get_instance()->can_study_lesson( $lesson_id );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_course_id( $lesson_id = null ) {
	edr_deprecated_function( 'ib_edu_get_course_id', '1.8.0', 'Edr_Courses::get_instance()->get_course_id( $lesson_id )' );

	// Is this function called inside the loop?
	if ( ! $lesson_id ) {
		$lesson_id = get_the_ID();
	}

	$course_id = get_post_meta( $lesson_id, '_ibedu_course', true );

	return is_numeric( $course_id ) ? $course_id : 0;
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_course_price( $course_id ) {
	edr_deprecated_function( 'ib_edu_get_course_price', '1.8.0', 'Edr_Courses::get_instance()->get_course_price' );

	return Edr_Courses::get_instance()->get_course_price( $course_id );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_difficulty_levels() {
	edr_deprecated_function( 'ib_edu_get_difficulty_levels', '1.8.0', 'edr_get_difficulty_levels' );

	return edr_get_difficulty_levels();
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_difficulty( $course_id ) {
	edr_deprecated_function( 'ib_edu_get_difficulty', '1.8.0', 'edr_get_difficulty' );

	return edr_get_difficulty( $course_id );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_price_widget( $course_id, $user_id, $before = '<div class="ib-edu-course-price">', $after = '</div>' ) {
	edr_deprecated_function( 'ib_edu_get_price_widget', '1.8.0', 'edr_get_price_widget' );

	return edr_get_price_widget( $course_id, $user_id, $before, $after );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_registration( $course_id ) {
	edr_deprecated_function( 'ib_edu_registration', '1.8.0', 'Edr_Courses::get_instance()->get_register_status( $course_id )' );

	return Edr_Courses::get_instance()->get_register_status( $course_id );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_adjacent_lesson( $previous = true ) {
	edr_deprecated_function( 'ib_edu_get_adjacent_lesson', '1.8.0', 'Edr_Courses::get_instance()->get_adjacent_lesson()' );

	return Edr_Courses::get_instance()->get_adjacent_lesson( $previous );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_adjacent_lesson_link( $dir = 'previous', $format, $title ) {
	edr_deprecated_function( 'ib_edu_get_adjacent_lesson_link', '1.8.0', 'edr_get_adjacent_lesson_link' );

	return edr_get_adjacent_lesson_link( $dir, $format, $title );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_has_quiz( $lesson_id ) {
	edr_deprecated_function( 'ib_edu_has_quiz', '1.8.0', 'edr_post_has_quiz' );

	return edr_post_has_quiz( $lesson_id );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_endpoint_url( $endpoint, $value, $url ) {
	edr_deprecated_function( 'ib_edu_get_endpoint_url', '1.8.0', 'edr_get_endpoint_url' );

	return edr_get_endpoint_url( $endpoint, $value, $url );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_breadcrumbs( $sep = ' &raquo; ' ) {
	edr_deprecated_function( 'ib_edu_breadcrumbs', '1.8.0', 'edr_breadcrumbs' );

	$breadcrumbs = array();
	$is_lesson = is_singular( 'ib_educator_lesson' );
	$is_course = is_singular( 'ib_educator_course' );

	if ( $is_course || $is_lesson ) {
		$student_courses_page_id = edr_get_page_id( 'student_courses' );

		if ( $student_courses_page_id ) {
			$page = get_post( $student_courses_page_id );

			if ( $page ) {
				$breadcrumbs[] = '<a href="' . esc_url( get_permalink( $page->ID ) ) . '">' . esc_html( $page->post_title ) . '</a>';
			}
		}
	}

	if ( $is_lesson ) {
		$course_id = Edr_Courses::get_instance()->get_course_id( get_the_ID() );

		if ( $course_id ) {
			$course = get_post( $course_id );

			if ( $course ) {
				$breadcrumbs[] = '<a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a>';
			}
		}
	}

	$breadcrumbs[] = '<span>' . get_the_title() . '</span>';

	echo implode( $sep, $breadcrumbs );
}

/**
 * @deprecated 1.8.0
 */
function ib_edu_get_access_status_message( $access_status ) {
	edr_deprecated_function( 'ib_edu_get_access_status_message', '1.8.0' );

	$message = '';

	switch ( $access_status ) {
		case 'pending_entry':
			$message = '<p>' . __( 'Your registration is pending.', 'ibeducator' ) . '</p>';
			break;

		case 'pending_payment':
			$message = '<p>' . __( 'The payment for this course is pending.', 'ibeducator' ) . '</p>';
			break;

		case 'inprogress':
			$message = '<p>' . __( 'You are registered for this course.', 'ibeducator' ) . '</p>';
			break;
	}

	return $message;
}
