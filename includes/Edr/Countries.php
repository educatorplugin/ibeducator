<?php

class Edr_Countries {
	/**
	 * @var Edr_Countries
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected $countries;

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get countries.
	 *
	 * @return array
	 */
	public function get_countries() {
		if ( ! $this->countries ) {
			$this->countries = apply_filters( 'ib_educator_countries', include( IBEDUCATOR_PLUGIN_DIR . 'includes/data/countries.php' ) );
		}

		return $this->countries;
	}

	/**
	 * Get states.
	 *
	 * @param string $country.
	 * @return array
	 */
	public function get_states( $country ) {
		switch ( $country ) {
			case 'AU':
			case 'BR':
			case 'CA':
			case 'ES':
			case 'IR':
			case 'IT':
			case 'JP':
			case 'TH':
			case 'US':
				$states = include( IBEDUCATOR_PLUGIN_DIR . 'includes/data/states/' . $country . '.php' );
				break;

			default:
				$states = array();
		}

		return apply_filters( 'ib_educator_states', $states, $country );
	}
}
