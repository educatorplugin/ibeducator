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
 * @param int $lesson_id
 * @return bool
 */
function ib_edu_student_can_study( $lesson_id ) {
	edr_deprecated_function( 'ib_edu_student_can_study', '1.8.0', 'Edr_Access::get_instance()->can_study_lesson( $lesson_id )' );

	return Edr_Access::get_instance()->can_study_lesson( $lesson_id );
}

/**
 * Get the course ID for a lesson.
 *
 * @deprecated 1.8.0
 * @param int $lesson_id
 * @return int
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
