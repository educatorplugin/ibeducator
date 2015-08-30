<?php

// Sanitize question content before saving.
add_filter( 'edr_add_question_pre_content', 'edr_kses_data', 30 );
add_filter( 'edr_edit_question_pre_content', 'edr_kses_data', 30 );
