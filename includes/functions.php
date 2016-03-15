<?php

/**
 * Get the plugin's settings.
 *
 * @return array
 */
function ib_edu_get_settings() {
	return get_option( 'ib_educator_settings', array() );
}

/**
 * Get the plugin's option.
 *
 * @param string $option_key
 * @param string $option_section
 * @return mixed
 */
function ib_edu_get_option( $option_key, $option_section ) {
	$options = null;

	switch ( $option_section ) {
		case 'settings':
			$options = get_option( 'ib_educator_settings' );
			break;

		case 'learning':
			$options = get_option( 'ib_educator_learning' );
			break;

		case 'taxes':
			$options = get_option( 'ib_educator_taxes' );
			break;

		case 'email':
			$options = get_option( 'ib_educator_email' );
			break;

		case 'memberships':
			$options = get_option( 'ib_educator_memberships' );
			break;
	}

	if ( is_array( $options ) && isset( $options[ $option_key ] ) ) {
		return $options[ $option_key ];
	}

	return null;
}

/**
 * Get breadcrumbs HTML.
 *
 * @deprecated 1.8.0
 * @param string $sep
 * @return string
 */
function ib_edu_breadcrumbs( $sep = ' &raquo; ' ) {
	edr_deprecated_function( 'ib_edu_breadcrumbs', '1.8.0' );

	$breadcrumbs = array();
	$is_lesson = is_singular( 'ib_educator_lesson' );
	$is_course = is_singular( 'ib_educator_course' );

	if ( $is_course || $is_lesson ) {
		$student_courses_page_id = ib_edu_page_id( 'student_courses' );

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
 * Get educator API url (can be used to process payment notifications from payment gateways).
 *
 * @param string $request
 * @return string
 */
function ib_edu_request_url( $request ) {
	$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
	return esc_url_raw( add_query_arg( array( 'edu-request' => $request ), home_url( '/', $scheme ) ) );
}

/**
 * Get price of a course.
 *
 * @param int $course_id
 * @return float
 */
function ib_edu_get_course_price( $course_id ) {
	return (float) get_post_meta( $course_id, '_ibedu_price', true );
}

/**
 * Get the list of available currencies.
 *
 * @return array
 */
function ib_edu_get_currencies() {
	return apply_filters( 'ib_educator_currencies', array(
		'AUD' => __( 'Australian Dollars', 'ibeducator' ),
		'AZN' => __( 'Azerbaijani Manat', 'ibeducator' ),
		'BRL' => __( 'Brazilian Real', 'ibeducator' ),
		'CAD' => __( 'Canadian Dollars', 'ibeducator' ),
		'CNY' => __( 'Chinese Yuan', 'ibeducator' ),
		'CZK' => __( 'Czech Koruna', 'ibeducator' ),
		'DKK' => __( 'Danish Krone', 'ibeducator' ),
		'EUR' => __( 'Euros', 'ibeducator' ),
		'HKD' => __( 'Hong Kong Dollar', 'ibeducator' ),
		'HUF' => __( 'Hungarian Forint', 'ibeducator' ),
		'INR' => __( 'Indian Rupee', 'ibeducator' ),
		'IRR' => __( 'Iranian Rial', 'ibeducator' ),
		'ILS' => __( 'Israeli Shekel', 'ibeducator' ),
		'JPY' => __( 'Japanese Yen', 'ibeducator' ),
		'MYR' => __( 'Malaysian Ringgits', 'ibeducator' ),
		'MXN' => __( 'Mexican Peso', 'ibeducator' ),
		'NZD' => __( 'New Zealand Dollar', 'ibeducator' ),
		'NOK' => __( 'Norwegian Krone', 'ibeducator' ),
		'PHP' => __( 'Philippine Pesos', 'ibeducator' ),
		'PLN' => __( 'Polish Zloty', 'ibeducator' ),
		'GBP' => __( 'Pounds Sterling', 'ibeducator' ),
		'RUB' => __( 'Russian Rubles', 'ibeducator' ),
		'SGD' => __( 'Singapore Dollar', 'ibeducator' ),
		'SEK' => __( 'Swedish Krona', 'ibeducator' ),
		'KRW' => __( 'South Korean Won', 'ibeducator' ),
		'CHF' => __( 'Swiss Franc', 'ibeducator' ),
		'TWD' => __( 'Taiwan New Dollars', 'ibeducator' ),
		'THB' => __( 'Thai Baht', 'ibeducator' ),
		'TRY' => __( 'Turkish Lira', 'ibeducator' ),
		'USD' => __( 'US Dollars', 'ibeducator' ),
		'UAH' => __( 'Ukrainian Hryvnia', 'ibeducator' ),
	) );
}

/**
 * Get current currency.
 *
 * @return string
 */
function ib_edu_get_currency() {
	$settings = ib_edu_get_settings();

	if ( isset( $settings['currency'] ) ) {
		$currency = $settings['currency'];
	} else {
		$currency = 'USD';
	}

	return apply_filters( 'ib_educator_currency', $currency );
}

/**
 * Get currency symbol.
 *
 * @param string $currency
 * @return string
 */
function ib_edu_get_currency_symbol( $currency ) {
	switch ( $currency ) {
		case 'USD':
		case 'AUD':
		case 'CAD':
		case 'HKD':
		case 'MXN':
		case 'NZD':
		case 'SGD':
			$cs = "&#36;";
			break;
		case 'BRL': $cs = "&#82;&#36;"; break;
		case 'CNY': $cs = "&#165;"; break;
		case 'CZK': $cs = "&#75;&#269;"; break;
		case 'DKK': $cs = "&#107;&#114;"; break;
		case 'EUR': $cs = "&euro;"; break;
		case 'HUF': $cs = "&#70;&#116;"; break;
		case 'INR': $cs = "&#8377;"; break;
		case 'IRR': $cs = "&#65020;"; break;
		case 'ILS': $cs = "&#8362;"; break;
		case 'JPY': $cs = "&yen;"; break;
		case 'MYR': $cs = "&#82;&#77;"; break;
		case 'NOK': $cs = "&#107;&#114;"; break;
		case 'PHP': $cs = "&#8369;"; break;
		case 'PLN': $cs = "&#122;&#322;"; break;
		case 'GBP': $cs = "&pound;"; break;
		case 'RUB': $cs = "&#1088;&#1091;&#1073;."; break;
		case 'SEK': $cs = "&#107;&#114;"; break;
		case 'CHF': $cs = "&#67;&#72;&#70;"; break;
		case 'TWD': $cs = "&#78;&#84;&#36;"; break;
		case 'THB': $cs = "&#3647;"; break;
		case 'TRY': $cs = "&#84;&#76;"; break;
		case 'UAH': $cs = "&#8372;"; break;
		default: $cs = $currency;
	}

	return apply_filters( 'ib_educator_currency_symbol', $cs, $currency );
}

/**
 * Format course price.
 *
 * @param float $price
 * @return string
 */
function ib_edu_format_course_price( $price ) {
	return ib_edu_format_price( $price );
}

/**
 * Format price.
 *
 * @param float $price
 * @return string
 */
function ib_edu_format_price( $price, $apply_filters = true, $symbol = true ) {
	$settings = ib_edu_get_settings();
	$currency = ib_edu_get_currency();
	$decimal_point = ! empty( $settings['decimal_point'] ) ? esc_html( $settings['decimal_point'] ) : '.';
	$thousands_sep = ! empty( $settings['thousands_sep'] ) ? esc_html( $settings['thousands_sep'] ) : ',';
	$formatted = number_format( $price, 2, $decimal_point, $thousands_sep );
	$formatted = ib_edu_strip_zeroes( $formatted, $decimal_point );

	if ( $symbol ) {
		$currency_symbol = ib_edu_get_currency_symbol( $currency );
	} else {
		$currency_symbol = preg_replace( '/[^a-z]+/i', '', $currency );
	}

	if ( isset( $settings['currency_position'] ) && 'after' == $settings['currency_position'] ) {
		$formatted = "$formatted $currency_symbol";
	} else {
		$formatted = "$currency_symbol $formatted";
	}

	if ( $apply_filters ) {
		return apply_filters( 'ib_educator_format_price', $formatted, $currency, $price );
	}

	return $formatted;
}

/**
 * Remove trailing zeroes from a number.
 *
 * @param mixed $number
 * @param string $decimal_point
 * @return string
 */
function ib_edu_strip_zeroes( $number, $decimal_point ) {
	return preg_replace( '/' . preg_quote( $decimal_point, '/' ) . '0+$/', '', $number );
}

/**
 * Format grade.
 *
 * @param int|float $grade
 * @return string
 */
function ib_edu_format_grade( $grade ) {
	$formatted = (float) round( $grade, 2 );

	return apply_filters( 'ib_educator_format_grade', $formatted . '%', $grade );
}

/**
 * Get permalink endpoint URL.
 *
 * @param string $endpoint
 * @param string $value
 * @param string $url
 * @return string
 */
function ib_edu_get_endpoint_url( $endpoint, $value, $url ) {
	if ( get_option( 'permalink_structure' ) ) {
		// Pretty permalinks.
		$url = trailingslashit( $url ) . $endpoint . '/' . $value;
	} else {
		// Basic permalinks.
		$url = add_query_arg( $endpoint, $value, $url );
	}

	return $url;
}

/**
 * Get educator page id.
 *
 * @param string $page_name
 * @return int
 */
function ib_edu_page_id( $page_name ) {
	$settings = get_option( 'ib_educator_settings', array() );
	$page_name .= '_page';

	if ( isset( $settings[ $page_name ] ) && is_numeric( $settings[ $page_name ] ) ) {
		return $settings[ $page_name ];
	}

	return 0;
}

/**
 * Get course access status message for a student.
 *
 * @param string $access_status
 * @return string
 */
function ib_edu_get_access_status_message( $access_status ) {
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

/**
 * Pass the message from the back-end to a template.
 *
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function ib_edu_message( $key, $value = null ) {
	static $messages = array();

	if ( is_null( $value ) ) {
		return isset( $messages[ $key ] ) ? $messages[ $key ] : null;
	}

	$messages[ $key ] = $value;
}

/**
 * Get available course difficulty levels.
 *
 * @return array
 */
function edr_get_difficulty_levels() {
	return array(
		'beginner'     => __( 'Beginner', 'ibeducator' ),
		'intermediate' => __( 'Intermediate', 'ibeducator' ),
		'advanced'     => __( 'Advanced', 'ibeducator' ),
	);
}

/**
 * Get course difficulty.
 *
 * @param int $course_id
 * @return null|array
 */
function edr_get_difficulty( $course_id ) {
	$difficulty = get_post_meta( $course_id, '_ib_educator_difficulty', true );
	
	if ( $difficulty ) {
		$levels = edr_get_difficulty_levels();

		return array(
			'key'   => $difficulty,
			'label' => isset( $levels[ $difficulty ] ) ? $levels[ $difficulty ] : '',
		);
	}

	return null;
}

/**
 * Can the current user edit a given lesson?
 *
 * @param int $lesson_id
 * @return bool
 */
function ib_edu_user_can_edit_lesson( $lesson_id ) {
	if ( current_user_can( 'manage_educator' ) ) return true;

	$course_id = Edr_Courses::get_instance()->get_course_id( $lesson_id );

	if ( $course_id ) {
		$api = IB_Educator::get_instance();
		return in_array( $course_id, $api->get_lecturer_courses( get_current_user_id() ) );
	}

	return false;
}

/**
 * Send email notification.
 *
 * @param string $to
 * @param string $template
 * @param array $subject_vars
 * @param array $template_vars
 */
function ib_edu_send_notification( $to, $template, $subject_vars, $template_vars ) {
	// Set default template vars.
	$template_vars['login_link'] = apply_filters( 'ib_educator_login_url', wp_login_url() );

	// Send email.
	$email = new Edr_EmailAgent();
	$email->set_template( $template );
	$email->parse_subject( $subject_vars );
	$email->parse_template( $template_vars );
	$email->add_recipient( $to );
	$email->send();
}

/**
 * Check if the lesson has a quiz attached.
 *
 * @param int $lesson_id
 * @return bool
 */
function ib_edu_has_quiz( $lesson_id ) {
	return get_post_meta( $lesson_id, '_ibedu_quiz', true ) ? true : false;
}

/**
 * Output the default page title based on the page context.
 */
function ib_edu_page_title() {
	$title = '';

	if ( is_post_type_archive( array( 'ib_educator_course', 'ib_educator_lesson' ) ) ) {
		$title = post_type_archive_title( '', false );
	} elseif ( is_tax() ) {
		$title = single_term_title( '', false );
	}

	$title = apply_filters( 'ib_educator_page_title', $title );

	echo $title;
}

/**
 * Are we on the payment page?
 *
 * @return bool
 */
function ib_edu_is_payment() {
	return is_page( ib_edu_page_id( 'payment' ) );
}

/**
 * Find out whether to collect billing data or not.
 *
 * @param mixed $object
 * @return bool
 */
function ib_edu_collect_billing_data( $object ) {
	if ( is_numeric( $object ) ) {
		$object = get_post( $object );
	}

	$result = false;

	if ( $object ) {
		$price = null;

		if ( 'ib_edu_membership' == $object->post_type ) {
			$price = Edr_Memberships::get_instance()->get_price( $object->ID );
		} elseif ( 'ib_educator_course' == $object->post_type ) {
			$price = ib_edu_get_course_price( $object->ID );
		}

		if ( $price && ib_edu_get_option( 'enable', 'taxes' ) ) {
			$result = true;
		}
	}

	return $result;
}

/**
 * Get the business location.
 *
 * @param string $part
 * @return mixed
 */
function ib_edu_get_location( $part = null ) {
	$result = array('', '');

	if ( $location = ib_edu_get_option( 'location', 'settings' ) ) {
		$delimiter = strpos( $location, ';' );

		if ( false === $delimiter ) {
			$result[0] = $location;
		} else {
			$result[0] = substr( $location, 0, $delimiter );
			$result[1] = substr( $location, $delimiter + 1 );
		}
	}

	if ( 'country' == $part ) {
		return $result[0];
	} elseif ( 'state' == $part ) {
		return $result[1];
	}

	return $result;
}

/**
 * Get lesson's access.
 *
 * @param int $lesson_id
 * @return string
 */
function ib_edu_lesson_access( $lesson_id ) {
	return get_post_meta( $lesson_id, '_ib_educator_access', true );
}

/**
 * Get a purchase link.
 * Currently, supports memberships only.
 *
 * @param array $atts
 * @return string
 */
function ib_edu_purchase_link( $atts ) {
	$atts = wp_parse_args( $atts, array(
		'object_id' => null,
		'type'      => null,
		'text'      => __( 'Purchase', 'ib-educator' ),
		'class'     => array(),
	) );

	// Add default class.
	array_push( $atts['class'], 'edu-purchase-link' );

	$html = apply_filters( 'ib_edu_pre_purchase_link', null, $atts );

	if ( ! is_null( $html ) ) {
		return $html;
	}

	if ( 'membership' == $atts['type'] ) {
		$html = sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( ib_edu_get_endpoint_url( 'edu-membership', $atts['object_id'], get_permalink( ib_edu_page_id( 'payment' ) ) ) ),
			esc_attr( implode( ' ', $atts['class'] ) ),
			$atts['text']
		);
	}

	return $html;
}

/**
 * Get database table names.
 *
 * @return array
 */
function edr_db_tables() {
	global $wpdb;
	$prefix = $wpdb->prefix . 'ibeducator_';

	return array(
		'payments'      => $prefix . 'payments',
		'entries'       => $prefix . 'entries',
		'questions'     => $prefix . 'questions',
		'choices'       => $prefix . 'choices',
		'answers'       => $prefix . 'answers',
		'grades'        => $prefix . 'grades',
		'members'       => $prefix . 'members',
		'tax_rates'     => $prefix . 'tax_rates',
		'payment_lines' => $prefix . 'payment_lines',
		'entry_meta'    => $prefix . 'entry_meta',
	);
}

/**
 * Get a payment.
 *
 * @param int|object|null $data
 * @return IB_Educator_Payment
 */
function edr_get_payment( $data = null ) {
	return new IB_Educator_Payment( $data );
}

/**
 * Get the available payment statuses.
 *
 * @return array
 */
function edr_get_payment_statuses() {
	return array(
		'pending'   => __( 'Pending', 'ibeducator' ),
		'complete'  => __( 'Complete', 'ibeducator' ),
		'failed'    => __( 'Failed', 'ibeducator' ),
		'cancelled' => __( 'Cancelled', 'ibeducator' ),
	);
}

/**
 * Get the available payment types.
 *
 * @return array
 */
function edr_get_payment_types() {
	return array(
		'course'     => __( 'Course', 'ibeducator' ),
		'membership' => __( 'Membership', 'ibeducator' ),
	);
}

/**
 * Get an entry.
 *
 * @param int|object|null $data
 * @return IB_Educator_Entry
 */
function edr_get_entry( $data = null ) {
	return new IB_Educator_Entry( $data );
}

/**
 * Get the available entry statuses.
 *
 * @return array
 */
function edr_get_entry_statuses() {
	return array(
		'pending'    => __( 'Pending', 'ibeducator' ),
		'inprogress' => __( 'In progress', 'ibeducator' ),
		'complete'   => __( 'Complete', 'ibeducator' ),
		'cancelled'  => __( 'Cancelled', 'ibeducator' ),
		'paused'     => __( 'Paused', 'ibeducator' ),
	);
}

/**
 * Get the available entry origins.
 *
 * @return array
 */
function edr_get_entry_origins() {
	return apply_filters( 'ib_educator_entry_origins', array(
		'payment'    => __( 'Payment', 'ibeducator' ),
		'membership' => __( 'Membership', 'ibeducator' ),
	) );
}

/**
 * Get a question.
 *
 * @param int|object|null $data
 * @return IB_Educator_Question
 */
function edr_get_question( $data = null ) {
	return new IB_Educator_Question( $data );
}

function edr_deprecated_function( $function, $version, $replacement = null ) {
	if ( WP_DEBUG ) {
		if ( is_null( $replacement ) ) {
			trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since Educator version %2$s with no alternative available.'), $function, $version ) );
		} else {
			trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since Educator version %2$s! Use %3$s instead.'), $function, $version, $replacement ) );
		}
	}
}

/**
 * Get directory path for private file uploads.
 *
 * @return string
 */
function edr_get_private_uploads_dir() {
	$dir = apply_filters( 'edr_private_uploads_dir', '' );

	if ( ! $dir ) {
		$dir = wp_upload_dir();

		if ( $dir && false === $dir['error'] ) {
			return $dir['basedir'] . '/edr';
		}
	}

	return '';
}

/**
 * Check if the protection .htaccess file exists
 * in the private file uploads directory.
 *
 * @return boolean
 */
function edr_protect_htaccess_exists() {
	$dir = edr_get_private_uploads_dir();

	return file_exists( $dir . '/.htaccess' );
}

/**
 * Trigger deprecated function error.
 *
 * @deprecated 1.8.0
 * @param string $function
 * @param string $version
 * @param string $replacement
 */
function _ib_edu_deprecated_function( $function, $version, $replacement = null ) {
	edr_deprecated_function( $function, $version, $replacement );
}
