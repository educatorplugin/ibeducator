<?php

class IB_Educator_Learning_Settings extends IB_Educator_Admin_Settings {
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
			'ib_educator_learning_settings', // id
			__( 'Learning', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_learning_page' // page
		);

		// Setting: 
		add_settings_field(
			'edu_lesson_comments',
			__( 'Enable comments on lessons', 'ibeducator' ),
			array( $this, 'setting_checkbox' ),
			'ib_educator_learning_page', // page
			'ib_educator_learning_settings', // section
			array(
				'name'           => 'lesson_comments',
				'settings_group' => 'ib_educator_learning',
				'default'        => 0,
				'id'             => 'edu_lesson_comments',
			)
		);

		register_setting(
			'ib_educator_learning_settings', // option group
			'ib_educator_learning',
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
				case 'lesson_comments':
					$clean[ $key ] = ( 1 == $value ) ? 1 : 0;
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
		$tabs['learning'] = __( 'Learning', 'ibeducator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'learning' == $tab ) {
			include IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-learning.php';
		}
	}
}