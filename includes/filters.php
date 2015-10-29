<?php

// Sanitize question content before saving.
add_filter( 'edr_add_question_pre_content', 'edr_kses_data', 30 );
add_filter( 'edr_edit_question_pre_content', 'edr_kses_data', 30 );

// Sanitize choice text before saving.
add_filter( 'edr_edit_choice_pre_text', 'esc_html', 30 );

// Sanitize question title before output.
add_filter( 'edr_get_question_title', 'esc_html', 30 );

// Sanitize choice text before output.
add_filter( 'edr_get_choice_text', 'esc_html', 30 );
