<?php

class Edr_Admin_Settings_Email extends Edr_Admin_Settings_Base {
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
			'ib_educator_email_settings', // id
			__( 'Email Settings', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_email_page' // page
		);

		// Setting: From Name.
		add_settings_field(
			'ib_educator_from_name',
			__( 'From Name', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_settings', // section
			array(
				'name'           => 'from_name',
				'settings_group' => 'ib_educator_email',
				'description'    => __( 'The name email notifications are said to come from.', 'ibeducator' ),
				'default'        => get_bloginfo( 'name' ),
			)
		);

		// Setting: From Email.
		add_settings_field(
			'ib_educator_from_email',
			__( 'From Email', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_settings', // section
			array(
				'name'           => 'from_email',
				'settings_group' => 'ib_educator_email',
				'description'    => __( 'Email to send notifications from.', 'ibeducator' ),
				'default'        => get_bloginfo( 'admin_email' ),
			)
		);

		// Email templates.
		add_settings_section(
			'ib_educator_email_templates', // id
			__( 'Email Templates', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_email_page' // page
		);

		// Subject: student registered.
		add_settings_field(
			'ib_subject_student_registered',
			__( 'Student registered subject', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'ib_educator_student_registered',
				'description'    => sprintf( __( 'Subject of the student registered notification email. Placeholders: %s', 'ibeducator' ), '{course_title}, {login_link}' ),
			)
		);

		// Template: student registered.
		add_settings_field(
			'ib_template_student_registered',
			__( 'Student registered template', 'ibeducator' ),
			array( $this, 'setting_textarea' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'ib_educator_student_registered',
				'description'    => sprintf( __( 'Placeholders: %s', 'ibeducator' ), '{student_name}, {course_title}, {course_excerpt}, {login_link}' ),
			)
		);

		// Subject: quiz grade.
		add_settings_field(
			'ib_subject_quiz_grade',
			__( 'Quiz grade subject', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'ib_educator_quiz_grade',
				'description'    => __( 'Subject of the quiz grade email.', 'ibeducator' ),
			)
		);

		// Template: quiz grade.
		add_settings_field(
			'ib_template_quiz_grade',
			__( 'Quiz grade template', 'ibeducator' ),
			array( $this, 'setting_textarea' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'ib_educator_quiz_grade',
				'description'    => sprintf( __( 'Placeholders: %s', 'ibeducator' ), '{student_name}, {lesson_title}, {grade}, {login_link}' ),
			)
		);

		// Subject: membership_register.
		add_settings_field(
			'ib_subject_membership_register',
			__( 'Membership registration subject', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'ib_educator_membership_register',
			)
		);

		// Template: membership_register.
		add_settings_field(
			'ib_template_membership_register',
			__( 'Membership registration template', 'ibeducator' ),
			array( $this, 'setting_textarea' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'ib_educator_membership_register',
				'description'    => sprintf( __( 'Placeholders: %s', 'ibeducator' ), '{student_name}, {membership}, {expiration}, {price}, {login_link}' ),
			)
		);

		// Subject: membership_renew.
		add_settings_field(
			'ib_subject_membership_renew',
			__( 'Membership renew subject', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'ib_educator_membership_renew',
			)
		);

		// Template: membership_renew.
		add_settings_field(
			'ib_template_membership_renew',
			__( 'Membership renew template', 'ibeducator' ),
			array( $this, 'setting_textarea' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'ib_educator_membership_renew',
				'description'    => sprintf( __( 'Placeholders: %s', 'ibeducator' ), '{student_name}, {membership}, {membership_payment_url}, {login_link}' ),
			)
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_email',
			array( $this, 'validate' )
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_student_registered',
			array( $this, 'validate_email_template' )
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_quiz_grade',
			array( $this, 'validate_email_template' )
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_membership_register',
			array( $this, 'validate_email_template' )
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_membership_renew',
			array( $this, 'validate_email_template' )
		);
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		if ( ! is_array( $input ) ) return '';

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'from_name':
					$clean[ $key ] = esc_html( $value );
					break;

				case 'from_email':
					$clean[ $key ] = sanitize_email( $value );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Validate an email template.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function validate_email_template( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['email'] = __( 'Email', 'ibeducator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'email' == $tab ) {
			include IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-email.php';
		}
	}
}
