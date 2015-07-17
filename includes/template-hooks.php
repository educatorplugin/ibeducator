<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Add HTML for default WP themes.
add_action( 'ib_educator_before_main_loop', 'edr_before_main_loop' );
add_action( 'ib_educator_after_main_loop', 'edr_after_main_loop' );

// Default sidebar.
add_action( 'ib_educator_sidebar', 'edr_show_sidebar' );

// Display the course difficulty and categories on the single course page.
add_action( 'ib_educator_before_course_content', 'edr_show_course_difficulty' );
add_action( 'ib_educator_before_course_content', 'edr_show_course_categories' );

// Display lessons on the single course page.
add_action( 'edr_course_footer', 'edr_display_lessons' );
