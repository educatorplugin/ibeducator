<?php

class IB_Educator_Account {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'ib_educator_register_form', array( __CLASS__, 'register_form' ), 10, 2 );
		add_filter( 'ib_educator_register_form_validate', array( __CLASS__, 'register_form_validate' ), 10, 2 );
		add_filter( 'ib_educator_register_user_data', array( __CLASS__, 'register_user_data' ), 10, 2 );
		add_action( 'ib_educator_new_student', array( __CLASS__, 'new_student' ), 10, 2 );
		add_action( 'ib_educator_update_student', array( __CLASS__, 'update_student' ), 10, 2 );
	}

	/**
	 * Determine fields that can have multiple error codes.
	 *
	 * @param array $error_codes
	 * @return array
	 */
	protected static function parse_register_errors( &$error_codes ) {
		$has_error = array();

		foreach ( $error_codes as $error_code ) {
			switch ( $error_code ) {
				case 'account_info_empty':
					$has_error['account_username'] = true;
					$has_error['account_email'] = true;
					break;
				case 'invalid_username':
				case 'existing_user_login':
					$has_error['account_username'] = true;
					break;
				case 'invalid_email':
				case 'existing_user_email':
					$has_error['account_email'] = true;
					break;
			}
		}

		return $has_error;
	}

	/**
	 * Output default user register form.
	 *
	 * @param WP_Error $errors
	 * @param WP_Post $object
	 */
	public static function register_form( $errors, $object ) {
		// Get current user.
		$user = wp_get_current_user();

		$error_codes = is_wp_error( $errors ) ? $errors->get_error_codes() : array();

		// Determine fields that can have multiple errors.
		$has_error = self::parse_register_errors( $error_codes );

		// Setup form.
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-form.php';
		$form = new IB_Educator_Form();
		$form->default_decorators();

		if ( ! $user->ID ) {
			// Add account details group.
			$form->add_group( array(
				'name'  => 'account',
				'label' => __( 'Create an Account', 'ibeducator' ),
			) );

			// Set values.
			$form->set_value( 'account_username', isset( $_POST['account_username'] ) ? $_POST['account_username'] : '' );
			$form->set_value( 'account_email', isset( $_POST['account_email'] ) ? $_POST['account_email'] : '' );

			// Username.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'account_username',
				'container_id' => 'account-username-field',
				'label'        => __( 'Username', 'ibeducator' ),
				'id'           => 'account-username',
				'class'        => isset( $has_error['account_username'] ) ? 'error' : '',
				'required'     => true,
			), 'account' );

			// Email.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'account_email',
				'container_id' => 'account-email-field',
				'label'        => __( 'Email', 'ibeducator' ),
				'id'           => 'account-email',
				'class'        => isset( $has_error['account_email'] ) ? 'error' : '',
				'required'     => true,
			), 'account' );
		}

		if ( ib_edu_collect_billing_data( $object ) ) {
			// Add billing details group.
			$form->add_group( array(
				'name'  => 'billing',
				'label' => __( 'Billing Details', 'ibeducator' ),
			) );

			// Set values.
			$values = IB_Educator::get_instance()->get_billing_data( $user->ID );

			if ( empty( $values['country'] ) ) {
				$values['country'] = ib_edu_get_location( 'country' );
			}

			if ( empty( $values['state'] ) ) {
				$values['state'] = ib_edu_get_location( 'state' );
			}

			$values['first_name'] = ( $user->ID ) ? $user->first_name : '';
			$values['last_name'] = ( $user->ID ) ? $user->last_name : '';

			foreach ( $values as $key => $value ) {
				$post_key = 'billing_' . $key;

				if ( isset( $_POST[ $post_key ] ) ) {
					$form->set_value( $post_key, $_POST[ $post_key ] );
				} else {
					$form->set_value( $post_key, $value );
				}
			}

			// First Name.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_first_name',
				'container_id' => 'billing-first-name-field',
				'label'        => __( 'First Name', 'ibeducator' ),
				'id'           => 'billing-first-name',
				'class'        => in_array( 'billing_first_name_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Last Name.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_last_name',
				'container_id' => 'billing-last-name-field',
				'label'        => __( 'Last Name', 'ibeducator' ),
				'id'           => 'billing-last-name',
				'class'        => in_array( 'billing_last_name_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Address.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_address',
				'container_id' => 'billing-address-field',
				'label'        => __( 'Address', 'ibeducator' ),
				'id'           => 'billing-address',
				'class'        => in_array( 'billing_address_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Address Line 2.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_address_2',
				'container_id' => 'billing-address-2-field',
				'label'        => __( 'Address Line 2', 'ibeducator' ),
				'id'           => 'billing-address-2',
			), 'billing' );

			// City.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_city',
				'container_id' => 'billing-city-field',
				'label'        => __( 'City', 'ibeducator' ),
				'id'           => 'billing-city',
				'class'        => in_array( 'billing_city_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			$edu_countries = IB_Educator_Countries::get_instance();

			// State.
			$state_field = array(
				'name'         => 'billing_state',
				'container_id' => 'billing-state-field',
				'label'        => __( 'State / Province', 'ibeducator' ),
				'id'           => 'billing-state',
				'class'        => in_array( 'billing_state_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			);

			$country = $form->get_value( 'billing_country' );
			$states = $country ? $edu_countries->get_states( $country ) : null;

			if ( $states ) {
				$state_field['type'] = 'select';
				$state_field['options'] = array_merge( array( '' => '&nbsp;' ), $states );
				unset( $states );
			} else {
				$state_field['type'] = 'text';
			}

			$form->add( $state_field, 'billing' );

			// Postcode.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_postcode',
				'container_id' => 'billing-postcode-field',
				'label'        => __( 'Postcode / Zip', 'ibeducator' ),
				'id'           => 'billing-postcode',
				'class'        => in_array( 'billing_postcode_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Country.
			$form->add( array(
				'type'         => 'select',
				'name'         => 'billing_country',
				'container_id' => 'billing-country-field',
				'label'        => __( 'Country', 'ibeducator' ),
				'id'           => 'billing-country',
				'class'        => in_array( 'billing_country_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
				'options'      => array_merge( array( '' => '&nbsp;' ), $edu_countries->get_countries() ),
			), 'billing' );
		}

		$form->display();
	}

	/**
	 * Validate the default user registration form.
	 *
	 * @param WP_Error $errors
	 * @param WP_Post $object
	 * @return WP_Error
	 */
	public static function register_form_validate( $errors, $object ) {
		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			// Username.
			if ( ! empty( $_POST['account_username'] ) ) {
				if ( ! validate_username( $_POST['account_username'] ) ) {
					$errors->add( 'invalid_username', __( 'Please check if you entered your username correctly.', 'ibeducator' ) );
				}
			} else {
				$errors->add( 'account_info_empty', __( 'Please enter your username and email.', 'ibeducator' ) );
			}

			// Email.
			if ( ! empty( $_POST['account_email'] ) ) {
				if ( ! is_email( $_POST['account_email'] ) ) {
					$errors->add( 'invalid_email', __( 'Please check if you entered your email correctly.', 'ibeducator' ) );
				}
			} elseif ( ! $errors->get_error_message( 'account_info_empty' ) ) {
				$errors->add( 'account_info_empty', __( 'Please enter your username and email.', 'ibeducator' ) );
			}
		}

		if ( ib_edu_collect_billing_data( $object ) ) {
			// First Name.
			if ( empty( $_POST['billing_first_name'] ) ) {
				$errors->add( 'billing_first_name_empty', __( 'Please enter your first name.', 'ibeducator' ) );
			}

			// Last Name.
			if ( empty( $_POST['billing_last_name'] ) ) {
				$errors->add( 'billing_last_name_empty', __( 'Please enter your last name.', 'ibeducator' ) );
			}

			// Address.
			if ( empty( $_POST['billing_address'] ) ) {
				$errors->add( 'billing_address_empty', __( 'Please enter your billing address.', 'ibeducator' ) );
			}

			// Address Line 2.
			if ( empty( $_POST['billing_city'] ) ) {
				$errors->add( 'billing_city_empty', __( 'Please enter your billing city.', 'ibeducator' ) );
			}

			// State / Province.
			if ( empty( $_POST['billing_state'] ) ) {
				$errors->add( 'billing_state_empty', __( 'Please enter your billing state / province.', 'ibeducator' ) );
			}

			// Postcode / Zip.
			if ( empty( $_POST['billing_postcode'] ) ) {
				$errors->add( 'billing_postcode_empty', __( 'Please enter your billing postcode / zip.', 'ibeducator' ) );
			}

			// Country.
			if ( empty( $_POST['billing_country'] ) ) {
				$errors->add( 'billing_country_empty', __( 'Please select your billing country.', 'ibeducator' ) );
			}
		}

		return $errors;
	}

	/**
	 * Filter the default user registration data.
	 *
	 * @param array $data
	 * @param WP_Post $object
	 * @return array
	 */
	public static function register_user_data( $data, $object ) {
		$data['user_login'] = $_POST['account_username'];
		$data['user_email'] = $_POST['account_email'];
		$data['user_pass'] = wp_generate_password( 12, false );

		// Billing details.
		if ( ib_edu_collect_billing_data( $object ) ) {
			$data['first_name'] = $_POST['billing_first_name'];
			$data['last_name'] = $_POST['billing_last_name'];
		}

		return $data;
	}

	/**
	 * Save billing data.
	 *
	 * @param int $user_id
	 */
	public static function save_billing_data( $user_id ) {
		update_user_meta( $user_id, '_ib_educator_billing', array(
			'address'   => sanitize_text_field( $_POST['billing_address'] ),
			'address_2' => sanitize_text_field( $_POST['billing_address_2'] ),
			'city'      => sanitize_text_field( $_POST['billing_city'] ),
			'state'     => sanitize_text_field( $_POST['billing_state'] ),
			'postcode'  => sanitize_text_field( $_POST['billing_postcode'] ),
			'country'   => sanitize_text_field( $_POST['billing_country'] ),
		) );
	}

	/**
	 * Fires when a student is created through the payment page.
	 *
	 * @param int $user_id
	 * @param WP_Post $object
	 */
	public static function new_student( $user_id, $object ) {
		if ( ib_edu_collect_billing_data( $object ) ) {
			self::save_billing_data( $user_id );
		}
	}

	/**
	 * Fires when a student is updated through the payment page.
	 * For example, being logged in, a user purchases a new course or a membership.
	 *
	 * @param int $user_id
	 * @param WP_Post $object
	 */
	public static function update_student( $user_id, $object ) {
		$data = array();

		if ( ib_edu_collect_billing_data( $object ) ) {
			$data['first_name'] = $_POST['billing_first_name'];
			$data['last_name'] = $_POST['billing_last_name'];

			// Update billing data.
			self::save_billing_data( $user_id );
		}

		if ( ! empty( $data ) ) {
			$data['ID'] = $user_id;
			wp_update_user( $data );
		}
	}

	/**
	 * Get payment info table.
	 *
	 * @param WP_Post $object
	 * @param array $args
	 * @return string
	 */
	public static function payment_info( $object, $args = array() ) {
		// Get price.
		if ( ! isset( $args['price'] ) ) {
			if ( 'ib_educator_course' == $object->post_type ) {
				$args['price'] = ib_edu_get_course_price( $object->ID );
			} elseif ( 'ib_edu_membership' == $object->post_type ) {
				$args['price'] = IB_Educator_Memberships::get_instance()->get_price( $object->ID );
			}
		}

		// Get tax data.
		$tax_enabled = ib_edu_get_option( 'enable', 'taxes' );

		if ( $tax_enabled ) {
			$edu_tax = IB_Educator_Tax::get_instance();
			$tax_data = $edu_tax->calculate_tax( $edu_tax->get_tax_class_for( $object->ID ), $args['price'], $args['country'], $args['state'] );
		} else {
			$tax_data = array(
				'taxes'    => array(),
				'subtotal' => $args['price'],
				'tax'      => 0.0,
				'total'    => $args['price'],
			);
		}

		// Items list.
		$output = '<table class="edu-payment-table">';
		$output .= '<thead><tr><th>' . __( 'Item', 'ibeducator' ) . '</th><th>' . __( 'Price', 'ibeducator' ) . '</th></tr></thead>';

		if ( 'ib_educator_course' == $object->post_type ) {
			$output .= '<tbody><tr><td>';
			$output .= sprintf(
				__( '%s with %s', 'ibeducator' ),
				'<a href="' . esc_url( get_permalink( $object->ID ) ) . '" target="_blank">' . esc_html( $object->post_title ) . '</a>',
				esc_html( get_the_author_meta( 'display_name', $object->post_author ) )
			);
			$output .= '<input type="hidden" id="payment-object-id" name="course_id" value="' . intval( $object->ID ) . '"></td>';
			$output .= '<td>' . ib_edu_format_price( $tax_data['subtotal'], false ) . '</td></tr></tbody>';
		} elseif ( 'ib_edu_membership' == $object->post_type ) {
			$output .= '<tbody><tr><td>' . esc_html( $object->post_title );
			$output .= '<input type="hidden" id="payment-object-id" name="membership_id" value="' . intval( $object->ID ) . '"></td>';
			$ms = IB_Educator_Memberships::get_instance();
			$membership_meta = $ms->get_membership_meta( $object->ID );
			$output .= '<td>' . $ms->format_price( $tax_data['subtotal'], $membership_meta['duration'], $membership_meta['period'] ) . '</td></tr></tbody>';
		}

		$output .= '</table>';

		// Summary.
		$output .= '<dl class="edu-payment-summary edu-dl">';

		if ( $tax_data['tax'] > 0.0 ) {
			$output .= '<dt class="payment-subtotal">' . __( 'Subtotal', 'ibeducator' ) . '</dt><dd>' . ib_edu_format_price( $tax_data['subtotal'], false ) . '</dd>';

			foreach ( $tax_data['taxes'] as $tax ) {
				$output .= '<dt class="payment-tax">' . esc_html( $tax->name ) . '</dt><dd>' . ib_edu_format_price( $tax->amount, false ) . '</dd>';
			}
		}

		$output .= '<dt class="payment-total">' . __( 'Total', 'ibeducator' ) . '</dt><dd>' . ib_edu_format_price( $tax_data['total'], false ) . '</dd>';
		$output .= '</dl>';

		return $output;
	}
}