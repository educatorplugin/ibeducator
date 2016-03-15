<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'the_content', 'edr_filter_course_content', 20 );
add_action( 'the_content', 'edr_filter_lesson_content', 20 );

// Display course status to a student.
add_action( 'edr_before_course_content', 'edr_display_course_status' );
add_action( 'edr_before_course_content', 'edr_display_course_errors' );

// Display the course difficulty and categories on the single course page.
add_action( 'edr_before_course_content', 'edr_course_meta' );

// Display lessons on the single course page.
add_action( 'edr_after_course_content', 'edr_display_lessons' );

add_action( 'edr_after_lesson_content', 'edr_lesson_after' );
