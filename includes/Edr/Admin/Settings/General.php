<?php

class Edr_Admin_Settings_General extends Edr_Admin_Settings_Base {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'ib_educator_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'ib_educator_settings_page', array( $this, 'settings_page' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		add_settings_section(
			'ib_educator_pages', // id
			__( 'Pages', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_general' // page
		);

		// Get pages.
		$tmp_pages = get_pages();
		$pages = array();
		foreach ( $tmp_pages as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}
		unset( $tmp_pages );

		$student_courses_sc = apply_filters( 'ib_educator_shortcode_tag', 'ibedu_student_courses' );

		add_settings_field(
			'student_courses_page',
			__( 'Student\'s Courses', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'student_courses_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the student\'s pending, in progress and complete courses. Add the following shortcode to this page: %s', 'ibeducator' ), '[' . esc_html( $student_courses_sc ) . ']' ),
			)
		);

		$payment_sc = apply_filters( 'ib_educator_shortcode_tag', 'ibedu_payment_page' );

		add_settings_field(
			'payment_page',
			__( 'Payment', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'payment_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the payment details of the course. Add the following shortcode to this page: %s', 'ibeducator' ), '[' . esc_html( $payment_sc ) . ']' ),
			)
		);

		$memberships_sc = apply_filters( 'ib_educator_shortcode_tag', 'memberships_page' );

		add_settings_field(
			'ib_educator_memberships_page',
			__( 'Memberships', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'memberships_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs all memberships. Shortcode: %s', 'ibeducator' ), '[' . esc_html( $memberships_sc ) . ']' ),
			)
		);

		$user_membership_sc = apply_filters( 'ib_educator_shortcode_tag', 'user_membership_page' );

		add_settings_field(
			'ib_educator_user_membership_page',
			__( 'User\'s Membership', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'user_membership_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the membership settings for the current user. Shortcode: %s', 'ibeducator' ), '[' . esc_html( $user_membership_sc ) . ']' ),
			)
		);

		$user_payments_sc = apply_filters( 'ib_educator_shortcode_tag', 'user_payments_page' );

		add_settings_field(
			'ib_educator_user_payments_page',
			__( 'User\'s Payments', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'user_payments_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the user\'s payments. Shortcode: %s', 'ibeducator' ), '[' . esc_html( $user_payments_sc ) . ']' ),
			)
		);

		// Selling settings.
		add_settings_section(
			'ib_educator_selling', // id
			__( 'Selling', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_general' // page
		);

		// Location.
		add_settings_field(
			'ib_educator_location',
			__( 'Location', 'ibeducator' ),
			array( $this, 'setting_location' ),
			'ib_educator_general', // page
			'ib_educator_selling', // section
			array(
				'name'           => 'location',
				'settings_group' => 'ib_educator_settings',
				'description'    => __( 'The location where you sell from.', 'ibeducator' ),
			)
		);

		// Save customers' IP.
		add_settings_field(
			'ib_educator_payment_ip',
			__( 'Store customers&apos; IPs on purchases', 'ibeducator' ),
			array( $this, 'setting_checkbox' ),
			'ib_educator_general', // page
			'ib_educator_selling', // section
			array(
				'name'           => 'payment_ip',
				'settings_group' => 'ib_educator_settings',
				'default'        => 0,
			)
		);

		// Show course lecturer on the payment page.
		add_settings_field(
			'ib_educator_payment_lecturer',
			__( 'Show course lecturer on the payment page', 'ibeducator' ),
			array( $this, 'setting_checkbox' ),
			'ib_educator_general', // page
			'ib_educator_selling', // section
			array(
				'name'           => 'payment_lecturer',
				'settings_group' => 'ib_educator_settings',
				'default'        => 1,
			)
		);

		// Currency settings.
		add_settings_section(
			'ib_educator_currency', // id
			__( 'Currency', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_general' // page
		);

		// Currency.
		add_settings_field(
			'currency',
			__( 'Currency', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'currency',
				'settings_group' => 'ib_educator_settings',
				'choices'        => ib_edu_get_currencies(),
			)
		);

		// Currency position.
		add_settings_field(
			'currency_position',
			__( 'Currency Position', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'currency_position',
				'settings_group' => 'ib_educator_settings',
				'choices'        => array(
					'before' => __( 'Before', 'ibeducator' ),
					'after'  => __( 'After', 'ibeducator' ),
				),
			)
		);

		// Decimal point separator.
		add_settings_field(
			'decimal_point',
			__( 'Decimal Point Separator', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'decimal_point',
				'settings_group' => 'ib_educator_settings',
				'size'           => 3,
				'default'        => '.',
			)
		);

		// Thousands separator.
		add_settings_field(
			'thousands_sep',
			__( 'Thousands Separator', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'thousands_sep',
				'settings_group' => 'ib_educator_settings',
				'size'           => 3,
				'default'        => ',',
			)
		);

		register_setting(
			'ib_educator_settings', // option group
			'ib_educator_settings',
			array( $this, 'validate' )
		);
	}

	/**
	 * The description of the section.
	 *
	 * @param array $args
	 */
	public function section_description( $args ) {
		if ( is_array( $args ) && isset( $args['id'] ) ) {
			switch ( $args['id'] ) {
				case 'ib_educator_pages':
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php _e( 'Courses Archive', 'ibeducator' ); ?></th>
								<td>
									<?php
										$archive_link = get_post_type_archive_link( 'ib_educator_course' );

										if ( $archive_link ) {
											echo '<a href="' . esc_url( $archive_link ) . '" target="_blank">' . esc_url( $archive_link ) . '</a>';
										}
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php
					break;
			}
		}
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'student_courses_page':
				case 'payment_page':
				case 'memberships_page':
				case 'user_membership_page':
				case 'user_payments_page':
					$clean[ $key ] = intval( $value );
					break;

 				case 'currency':
 					if ( array_key_exists( $input[ $key ], ib_edu_get_currencies() ) ) {
 						$clean[ $key ] = $input[ $key ];
 					}
 					break;

 				case 'currency_position':
 					if ( in_array( $value, array( 'before', 'after' ) ) ) {
 						$clean[ $key ] = $value;
 					}
 					break;

 				case 'decimal_point':
 				case 'thousands_sep':
 					$clean[ $key ] = preg_replace( '/[^,. ]/', '', $value );
 					break;

 				case 'location':
 					$clean[ $key ] = sanitize_text_field( $value );
 					break;

 				case 'payment_ip':
 				case 'payment_lecturer':
 					$clean[ $key ] = ( 1 != $value ) ? 0 : 1;
 					break;
			}
		}

		return $clean;
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['general'] = __( 'General', 'ibeducator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( empty( $tab ) || 'general' == $tab ) {
			include IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-general.php';
		}
	}
}
