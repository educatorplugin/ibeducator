<?php

/**
 * Educator plugin's admin setup.
 */
class Edr_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		self::includes();
		add_action( 'current_screen', array( __CLASS__, 'maybe_includes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'admin_actions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Include the necessary files.
	 */
	public static function includes() {
		new Edr_Admin_Settings_General();
		new Edr_Admin_Settings_Learning();
		new Edr_Admin_Settings_Payment();
		new Edr_Admin_Settings_Taxes();
		new Edr_Admin_Settings_Email();
		new Edr_Admin_Settings_Memberships();
		Edr_Autocomplete::init();
		Edr_Admin_PostTypes::init();
		Edr_Admin_Meta::init();
		Edr_Admin_Quiz::init();
		new Edr_Admin_Syllabus();
	}

	/**
	 * Include the files based on the current screen.
	 *
	 * @param WP_Screen $screen
	 */
	public static function maybe_includes( $screen ) {
		switch ( $screen->id ) {
			case 'options-permalink':
				new Edr_Admin_Settings_Permalink();
				break;
		}
	}

	/**
	 * Setup admin menu.
	 */
	public static function admin_menu() {
		add_menu_page(
			__( 'Educator', 'ibeducator' ),
			__( 'Educator', 'ibeducator' ),
			'manage_educator',
			'ib_educator_admin',
			array( __CLASS__, 'settings_page' ),
			IBEDUCATOR_PLUGIN_URL . '/admin/images/educator-icon.png'
		);

		add_submenu_page(
			'ib_educator_admin',
			__( 'Educator Settings', 'ibeducator' ),
			__( 'Settings', 'ibeducator' ),
			'manage_educator',
			'ib_educator_admin'
		);

		$payments_hook = add_submenu_page(
			'ib_educator_admin',
			__( 'Educator Payments', 'ibeducator' ),
			__( 'Payments', 'ibeducator' ),
			'manage_educator',
			'ib_educator_payments',
			array( __CLASS__, 'admin_payments' )
		);

		if ( $payments_hook ) {
			add_action( "load-$payments_hook", array( __CLASS__, 'add_payments_screen_options' ) );
		}

		$entries_hook = null;

		if ( current_user_can( 'manage_educator' ) ) {
			$entries_hook = add_submenu_page(
				'ib_educator_admin',
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'manage_educator',
				'ib_educator_entries',
				array( __CLASS__, 'admin_entries' )
			);
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			$entries_hook = add_menu_page(
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'educator_edit_entries',
				'ib_educator_entries',
				array( __CLASS__, 'admin_entries' )
			);
		}

		if ( $entries_hook ) {
			add_action( "load-$entries_hook", array( __CLASS__, 'add_entries_screen_options' ) );
		}

		$members_hook = add_submenu_page(
			'ib_educator_admin',
			__( 'Educator Members', 'ibeducator' ),
			__( 'Members', 'ibeducator' ),
			'manage_educator',
			'ib_educator_members',
			array( __CLASS__, 'admin_members' )
		);

		if ( $members_hook ) {
			add_action( "load-$members_hook", array( __CLASS__, 'add_members_screen_options' ) );
		}
	}

	/**
	 * Output the settings page.
	 */
	public static function settings_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

		do_action( 'ib_educator_settings_page', $tab );
	}

	/**
	 * Process the admin actions.
	 */
	public static function admin_actions() {
		if ( isset( $_GET['edu-action'] ) ) {
			switch ( $_GET['edu-action'] ) {
				case 'edit-entry':
					Edr_Admin_Actions::edit_entry();
					break;

				case 'edit-payment':
					Edr_Admin_Actions::edit_payment();
					break;

				case 'edit-member':
					Edr_Admin_Actions::edit_member();
					break;

				case 'edit-payment-gateway':
					Edr_Admin_Actions::edit_payment_gateway();
					break;

				case 'delete-entry':
					Edr_Admin_Actions::delete_entry();
					break;

				case 'delete-payment':
					Edr_Admin_Actions::delete_payment();
					break;
			}
		}
	}

	/**
	 * Output Educator payments page.
	 */
	public static function admin_payments() {
		$action = isset( $_GET['edu-action'] ) ? $_GET['edu-action'] : 'payments';

		switch ( $action ) {
			case 'payments':
			case 'edit-payment':
				require( IBEDUCATOR_PLUGIN_DIR . 'admin/templates/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Output Educator entries page.
	 */
	public static function admin_entries() {
		$action = isset( $_GET['edu-action'] ) ? $_GET['edu-action'] : 'entries';

		switch ( $action ) {
			case 'entries':
			case 'edit-entry':
			case 'entry-progress':
				require( IBEDUCATOR_PLUGIN_DIR . 'admin/templates/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Add screen options to the payments admin page.
	 */
	public static function add_payments_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_ib_educator_payments' != $screen->id || isset( $_GET['edu-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'payments_per_page',
			'label'   => __( 'Payments per page', 'ibeducator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Add screen options to the entries admin page.
	 */
	public static function add_entries_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_ib_educator_entries' != $screen->id || isset( $_GET['edu-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'entries_per_page',
			'label'   => __( 'Entries per page', 'ibeducator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Add screen options to the members admin page.
	 */
	public static function add_members_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_ib_educator_members' != $screen->id || isset( $_GET['edu-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'members_per_page',
			'label'   => __( 'Members per page', 'ibeducator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Output Educator members page.
	 */
	public static function admin_members() {
		$action = isset( $_GET['edu-action'] ) ? $_GET['edu-action'] : 'members';

		switch ( $action ) {
			case 'members':
			case 'edit-member':
				require( IBEDUCATOR_PLUGIN_DIR . 'admin/templates/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		wp_enqueue_style( 'edr-admin', IBEDUCATOR_PLUGIN_URL . 'admin/css/admin.css', array(), '1.5' );

		$screen = get_current_screen();

		if ( $screen ) {
			if ( 'educator_page_ib_educator_payments' == $screen->id ) {
				wp_enqueue_script( 'ib-educator-edit-payment', IBEDUCATOR_PLUGIN_URL . 'admin/js/edit-payment.js', array( 'jquery' ), '1.4.1', true );
				wp_enqueue_script( 'postbox' );
			} elseif ( 'educator_page_ib_educator_entries' == $screen->id ) {
				wp_enqueue_script( 'postbox' );
			} elseif ( 'educator_page_ib_educator_members' == $screen->id ) {
				wp_enqueue_script( 'postbox' );
			}
		}
	}

	/**
	 * Save screen options for various admin pages.
	 *
	 * @param mixed $result
	 * @param string $option
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set_screen_option( $result, $option, $value ) {
		if ( in_array( $option, array( 'payments_per_page', 'entries_per_page', 'members_per_page' ) ) ) {
			$result = (int) $value;
		}

		return $result;
	}
}
