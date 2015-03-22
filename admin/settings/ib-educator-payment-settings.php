<?php

class IB_Educator_Payment_Settings extends IB_Educator_Admin_Settings {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'ib_educator_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'ib_educator_settings_page', array( $this, 'settings_page' ) );
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['payment'] = __( 'Payment Gateways', 'ibeducator' );

		return $tabs;
	}
	
	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'payment' == $tab ) {
			include IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-payment.php';
		}
	}
}