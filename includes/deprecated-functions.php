<?php

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
