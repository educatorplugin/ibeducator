<?php

/**
 * Get database table names.
 *
 * @deprecated 1.8.0
 */
function ib_edu_table_names() {
	edr_deprecated_function( 'ib_edu_table_names', '1.8.0', 'edr_db_tables' );

	return edr_db_tables();
}

/**
 * Check if the current user can view the lesson.
 *
 * @deprecated 1.8.0
 */
function ib_edu_student_can_study( $lesson_id ) {
	edr_deprecated_function( 'ib_edu_student_can_study', '1.8.0', 'Edr_Access::get_instance()->can_study_lesson( $lesson_id )' );

	return Edr_Access::get_instance()->can_study_lesson( $lesson_id );
}

/**
 * Get the course ID for a lesson.
 *
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
 * Get HTML for the course price widget.
 *
 * @deprecated 1.8.0
 */
function ib_edu_get_price_widget( $course_id, $user_id, $before = '<div class="ib-edu-course-price">', $after = '</div>' ) {
	edr_deprecated_function( 'ib_edu_get_price_widget', '1.8.0', 'edr_get_price_widget' );

	return edr_get_price_widget( $course_id, $user_id, $before, $after );
}

/**
 * Get registration status for a given course.
 *
 * @deprecated 1.8.0
 */
function ib_edu_registration( $course_id ) {
	edr_deprecated_function( 'ib_edu_registration', '1.8.0', 'Edr_Courses::get_instance()->get_register_status( $course_id )' );

	return Edr_Courses::get_instance()->get_register_status( $course_id );
}

/**
 * Get the adjacent lesson.
 *
 * @deprecated 1.8.0
 */
function ib_edu_get_adjacent_lesson( $previous = true ) {
	edr_deprecated_function( 'ib_edu_get_adjacent_lesson', '1.8.0', 'Edr_Courses::get_instance()->get_adjacent_lesson()' );

	return Edr_Courses::get_instance()->get_adjacent_lesson( $previous );
}

/**
 * Get the adjacent lesson's link.
 *
 * @deprecated 1.8.0
 */
function ib_edu_get_adjacent_lesson_link( $dir = 'previous', $format, $title ) {
	edr_deprecated_function( 'ib_edu_get_adjacent_lesson_link', '1.8.0', 'edr_get_adjacent_lesson_link' );

	return edr_get_adjacent_lesson_link( $dir, $format, $title );
}
