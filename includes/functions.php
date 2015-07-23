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
 * @param string $sep
 * @return string
 */
function ib_edu_breadcrumbs( $sep = ' &raquo; ' ) {
	$breadcrumbs = array();
	$is_lesson = is_singular( 'ib_educator_lesson' );
	$is_course = is_singular( 'ib_educator_course' );

	if ( $is_course || $is_lesson ) {
		$student_courses_page_id = ib_edu_page_id( 'student_courses' );

		if ( $student_courses_page_id ) {
			$page = get_post( $student_courses_page_id );

			if ( $page ) {
				$breadcrumbs[] = '<a href="' . get_permalink( $page->ID ) . '">' . esc_html( $page->post_title ) . '</a>';
			}
		}
	}

	if ( $is_lesson ) {
		$course_id = ib_edu_get_course_id( get_the_ID() );

		if ( $course_id ) {
			$course = get_post( $course_id );

			if ( $course ) {
				$breadcrumbs[] = '<a href="' . get_permalink( $course->ID ) . '">' . esc_html( $course->post_title ) . '</a>';
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
/*$currencies = ib_edu_get_currencies();
asort( $currencies );
foreach ( $currencies as $currency => $name ) {
	echo "case '$currency':\n";
}*/

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
 * Get the course ID for a lesson.
 *
 * @param int $lesson_id
 * @return int
 */
function ib_edu_get_course_id( $lesson_id = null ) {
	// Is this function called inside the loop?
	if ( ! $lesson_id ) {
		$lesson_id = get_the_ID();
	}

	$course_id = get_post_meta( $lesson_id, '_ibedu_course', true );
	
	return is_numeric( $course_id ) ? $course_id : 0;
}

/**
 * Check if the current user can view the lesson.
 *
 * @param int $lesson_id
 * @return bool
 */
function ib_edu_student_can_study( $lesson_id ) {
	$lesson_access = ib_edu_lesson_access( $lesson_id );
	$user_id = get_current_user_id();
	$access = false;

	if ( 'public' == $lesson_access ) {
		$access = true;
	} elseif ( $user_id ) {
		if ( 'logged_in' == $lesson_access ) {
			$access = true;
		} else {
			$course_id = ib_edu_get_course_id( $lesson_id );

			if ( $course_id ) {
				$access_status = IB_Educator::get_instance()->get_access_status( $course_id, $user_id );

				if ( in_array( $access_status, array( 'inprogress', 'course_complete' ) ) ) {
					$access = true;
				}
			}
		}
	}

	return $access;
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
 * @since 1.0.0
 * @return array
 */
function ib_edu_get_difficulty_levels() {
	return array(
		'beginner'     => __( 'Beginner', 'ibeducator' ),
		'intermediate' => __( 'Intermediate', 'ibeducator' ),
		'advanced'     => __( 'Advanced', 'ibeducator' ),
	);
}

/**
 * Get course difficulty.
 *
 * @since 1.0.0
 * @param int $course_id
 * @return null|array
 */
function ib_edu_get_difficulty( $course_id ) {
	$difficulty = get_post_meta( $course_id, '_ib_educator_difficulty', true );
	
	if ( $difficulty ) {
		$levels = ib_edu_get_difficulty_levels();

		return array(
			'key'   => $difficulty,
			'label' => ( isset( $levels[ $difficulty ] ) ) ? $levels[ $difficulty ] : '',
		);
	}

	return null;
}

/**
 * Get database table names.
 *
 * @since 1.0.0
 * @param string $key
 * @return string
 */
function ib_edu_table_names() {
	global $wpdb;
	$prefix = $wpdb->prefix . 'ibeducator_';
	
	return array(
		'payments'     => $prefix . 'payments',
		'entries'      => $prefix . 'entries',
		'questions'    => $prefix . 'questions',
		'choices'      => $prefix . 'choices',
		'answers'      => $prefix . 'answers',
		'grades'       => $prefix . 'grades',
		'members'      => $prefix . 'members',
		'tax_rates'    => $prefix . 'tax_rates',
		'payment_lines' => $prefix . 'payment_lines',
	);
}

/**
 * Can the current user edit a given lesson?
 *
 * @param int $lesson_id
 * @return bool
 */
function ib_edu_user_can_edit_lesson( $lesson_id ) {
	if ( current_user_can( 'manage_educator' ) ) return true;

	$course_id = ib_edu_get_course_id( $lesson_id );

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
	require_once IBEDUCATOR_PLUGIN_DIR . '/includes/ib-educator-email.php';

	// Set default template vars.
	$template_vars['login_link'] = apply_filters( 'ib_educator_login_url', wp_login_url() );

	// Send email.
	$email = new IB_Educator_Email();
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
 * Get HTML for the course price widget.
 *
 * @param int $course_id
 * @param int $user_id
 * @param string $before
 * @param string $after
 * @return string
 */
function ib_edu_get_price_widget( $course_id, $user_id, $before = '<div class="ib-edu-course-price">', $after = '</div>' ) {
	// Registration allowed?
	if ( 'closed' == ib_edu_registration( $course_id ) ) {
		return '';
	}

	// Check membership.
	$membership_access = IB_Educator_Memberships::get_instance()->membership_can_access( $course_id, $user_id );

	/**
	 * Filter the course price widget.
	 *
	 * @since 1.3.2
	 *
	 * @param bool $membership_access Whether the user's current membership allows him/her to take the course.
	 */
	$output = apply_filters( 'ib_educator_course_price_widget', null, $membership_access, $course_id, $user_id );
	
	if ( null !== $output ) {
		return $output;
	}

	// Generate the widget.
	$output = $before;

	if ( $membership_access ) {
		$register_url = ib_edu_get_endpoint_url( 'edu-action', 'join', get_permalink( $course_id ) );
		$output .= '<form action="' . esc_url( $register_url ) . '" method="post">';
		$output .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'ib_educator_join' ) . '">';
		$output .= '<input type="submit" class="ib-edu-button" value="' . __( 'Join', 'ibeducator' ) . '">';
		$output .= '</form>';
	} else {
		$price = ib_edu_get_course_price( $course_id );
		$price = ( 0 == $price ) ? __( 'Free', 'ibeducator' ) : ib_edu_format_course_price( $price );
		$register_url = ib_edu_get_endpoint_url( 'edu-course', $course_id, get_permalink( ib_edu_page_id( 'payment' ) ) );
		$output .= '<span class="price">' . $price . '</span><a href="' . esc_url( $register_url )
				. '" class="ib-edu-button">' . __( 'Register', 'ibeducator' ) . '</a>';
	}

	$output .= $after;
	return $output;
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
 * Get the adjacent lesson.
 *
 * @param bool $previous
 * @return mixed If global post object is not set returns null, if post is not found, returns empty string, else returns WP_Post.
 */
function ib_edu_get_adjacent_lesson( $previous = true ) {
	global $wpdb;

	if ( ! $lesson = get_post() ) {
		return null;
	}

	$course_id = ib_edu_get_course_id( $lesson->ID );
	$cmp = $previous ? '<' : '>';
	$order = $previous ? 'DESC' : 'ASC';
	$join = "INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID";
	$where = $wpdb->prepare( "WHERE p.post_type = 'ib_educator_lesson' AND p.post_status = 'publish' AND p.menu_order $cmp %d AND pm.meta_key = '_ibedu_course' AND pm.meta_value = %d", $lesson->menu_order, $course_id );
	$sort = "ORDER BY p.menu_order $order";
	$query = "SELECT p.ID FROM $wpdb->posts as p $join $where $sort LIMIT 1";
	$result = $wpdb->get_var( $query );

	if ( null === $result ) {
		return '';
	}

	return get_post( $result );
}

/**
 * Get the adjacent lesson's link.
 *
 * @param string $dir
 * @param string $format
 * @param string $title
 * @return string
 */
function ib_edu_get_adjacent_lesson_link( $dir = 'previous', $format, $title ) {
	$previous = ( 'previous' == $dir ) ? true : false;
	
	if ( ! $lesson = ib_edu_get_adjacent_lesson( $previous ) ) {
		return '';
	}

	$url = apply_filters( "ib_educator_{$dir}_lesson_url", get_permalink( $lesson->ID ), get_the_ID() );
	$title = str_replace( '%title', esc_html( $lesson->post_title ), $title );
	$link = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';
	return str_replace( '%link', $link, $format );
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
			$price = IB_Educator_Memberships::get_instance()->get_price( $object->ID );
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
 * Get registration status for a given course.
 *
 * @param int $course_id
 * @return string
 */
function ib_edu_registration( $course_id ) {
	return get_post_meta( $course_id, '_ib_educator_register', true );
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
 * Trigger deprecated function error.
 *
 * @param string $function
 * @param string $version
 * @param string $replacement
 */
function _ib_edu_deprecated_function( $function, $version, $replacement = null ) {
	if ( WP_DEBUG && current_user_can( 'manage_options' ) ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since Educator version %2$s! Use %3$s instead.'), $function, $version, $replacement ) );
		} else {
			trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since Educator version %2$s with no alternative available.'), $function, $version ) );
		}
	}
}
