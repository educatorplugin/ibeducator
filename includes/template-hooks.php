<?php

add_action( 'ib_educator_before_main_loop', array( 'IB_Educator_Main', 'action_before_main_loop' ) );
add_action( 'ib_educator_after_main_loop', array( 'IB_Educator_Main', 'action_after_main_loop' ) );
add_action( 'ib_educator_sidebar', array( 'IB_Educator_Main', 'action_sidebar' ) );
add_action( 'ib_educator_before_course_content', array( 'IB_Educator_Main', 'before_course_content' ) );

add_action( 'edr_course_footer', 'edr_display_lessons' );
