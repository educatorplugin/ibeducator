<?php

class Edr_Admin_Settings_Memberships extends Edr_Admin_Settings_Base {
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
			'ib_educator_memberships_settings', // id
			__( 'Memberships Settings', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_memberships_page' // page
		);

		// Setting: Notify a user X days before his/her membership expires.
		add_settings_field(
			'ib_educator_days_notify',
			__( 'Send an email notification to a user X days before his/her membership expires', 'ibeducator' ),
			array( $this, 'setting_text' ),
			'ib_educator_memberships_page', // page
			'ib_educator_memberships_settings', // section
			array(
				'name'           => 'days_notify',
				'settings_group' => 'ib_educator_memberships',
				'default'        => 5,
				'id'             => 'ib_educator_days_notify',
			)
		);

		register_setting(
			'ib_educator_memberships_settings', // option group
			'ib_educator_memberships',
			array( $this, 'validate' )
		);
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		if ( ! is_array( $input ) ) {
			return '';
		}

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'days_notify':
					$clean[ $key ] = absint( $value );
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
		$tabs['memberships'] = __( 'Memberships', 'ibeducator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'memberships' == $tab ) {
			include IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-memberships.php';
		}
	}
}
