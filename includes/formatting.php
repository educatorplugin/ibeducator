<?php

/**
 * Allowed HTML tags.
 * Used to sanitize question content.
 *
 * @return array
 */
function edr_kses_allowed_tags() {
	return array(
		// Lists.
		'ul'         => array(),
		'ol'         => array(),
		'li'         => array(),

		// Code.
		'pre'        => array(),
		'code'       => array(),

		// Links.
		'a'          => array(
			'href'   => array(),
			'title'  => array(),
			'rel'    => array(),
			'target' => array(),
		),

		// Formatting.
		'strong'     => array(),
		'em'         => array(),

		// Images.
		'img'        => array(
			'src'    => true,
			'alt'    => true,
			'height' => true,
			'width'  => true,
		),
	);
}

/**
 * Sanitize data leaving whitelisted tags only.
 *
 * @see edr_kses_allowed_tags()
 * @param string $data
 * @return string
 */
function edr_kses_data( $data ) {
	return wp_kses( $data, edr_kses_allowed_tags() );
}
