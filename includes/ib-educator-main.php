<?php

class IB_Educator_Main {
	public static function get_gateways() {
		edr_deprecated_function( 'IB_Educator_Main::get_gateways', '1.8.0', 'Edr_Main::get_instance()->get_gateways' );

		return Edr_Main::get_instance()->get_gateways();
	}
}
