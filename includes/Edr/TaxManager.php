<?php

class Edr_TaxManager {
	/**
	 * @var Edr_TaxManager
	 */
	protected static $instance;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var float
	 */
	protected $inclusive_rate;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$tables = ib_edu_table_names();
		$this->table = $tables['tax_rates'];
	}

	/**
	 * Get insntance of this class (singleton).
	 *
	 * @return Edr_TaxManager
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get a tax rate.
	 *
	 * @param string $tax_class Tax class.
	 * @param string $country A two-letter country code.
	 * @param string $state
	 * @return float Percentage tax rate.
	 */
	protected function get_tax_rate( $tax_class, $country, $state = '' ) {
		global $wpdb;
		$rates = array();
		$priorities = array();
		$location = ib_edu_get_location();
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE tax_class = %s AND country IN (\'\', %s, %s) AND state IN (\'\', %s, %s) ORDER BY priority ASC, rate_order ASC';
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $tax_class, $location[0], $country, $location[1], $state ) );
		$inclusive = ib_edu_get_option( 'tax_inclusive', 'taxes' );
		if ( ! $inclusive ) $inclusive = 'y';
		$inc_rate = 0.0;
		$inc_priorities = array();

		foreach ( $results as $row ) {
			if ( 'y' == $inclusive
				&& ! in_array( $row->priority, $inc_priorities )
				&& ( $location[0] == $row->country || '' == $row->country )
				&& ( $location[1] == $row->state || '' == $row->state ) ) {
				// Calculate inclusive tax rate.
				$inc_rate += $row->rate;
				$inc_priorities[] = $row->priority;
			}

			if ( ! in_array( $row->priority, $priorities )
				&& ( $country == $row->country || '' == $row->country )
				&& ( $state == $row->state || '' == $row->state ) ) {
				// Select tax rates.
				$rates[] = $row;
				$priorities[] = $row->priority;
			}
		}

		if ( $inc_rate ) {
			$this->inclusive_rate = $inc_rate;
		}

		return array(
			'inclusive' => $inc_rate,
			'rates'     => $rates,
		);
	}

	/**
	 * Calculate tax.
	 *
	 * @param string $tax_class
	 * @param float $price
	 * @param string $country
	 * @param string $state
	 * @return array Tax data (tax, subtotal, total).
	 */
	public function calculate_tax( $tax_class, $price, $country, $state ) {
		// Are prices entered with tax?
		$inclusive = ib_edu_get_option( 'tax_inclusive', 'taxes' );
		if ( ! $inclusive ) $inclusive = 'y';

		// Get rates.
		$rates_data = $this->get_tax_rate( $tax_class, $country, $state );

		// Calculate tax.
		$tax_data = array(
			'taxes' => array(),
			'tax'   => 0.0,
		);

		if ( 'y' == $inclusive ) {
			$tax_data['subtotal'] = round( $price / ( 1 + $rates_data['inclusive'] / 100 ), 2 );
			$tax_data['total'] = $tax_data['subtotal'];
		} else {
			$tax_data['subtotal'] = $price;
			$tax_data['total'] = $price;
		}

		foreach ( $rates_data['rates'] as $rate ) {
			// Calculate tax amount.
			$tax = round( $tax_data['subtotal'] * $rate->rate / 100, 2 );
			
			// Setup tax object.
			$tmp = new stdClass;
			$tmp->ID = $rate->ID;
			$tmp->name = $rate->name;
			$tmp->rate = $rate->rate;
			$tmp->amount = $tax;
			$tax_data['taxes'][] = $tmp;

			// Totals.
			$tax_data['tax'] += $tax;
			$tax_data['total'] += $tax;
		}

		return $tax_data;
	}

	/**
	 * Sanitize tax class data.
	 *
	 * @param array $input
	 * @return WP_Error|array
	 */
	public function sanitize_tax_class( $input ) {
		$data = array();
		$errors = new WP_Error();

		if ( empty( $input['name'] ) ) {
			$errors->add( 'name_empty', __( 'Name cannot be empty.', 'ibeducator' ) );
		} else {
			$data['name'] = preg_replace( '/[^a-zA-Z0-9-_]+/', '', $input['name'] );

			if ( empty( $data['name'] ) ) {
				$errors->add( 'name_invalid', __( 'Invalid name.', 'ibeducator' ) );
			}
		}

		if ( empty( $input['description'] ) ) {
			$errors->add( 'description_empty', __( 'Description cannot be empty.', 'ibeducator' ) );
		} else {
			$data['description'] = sanitize_text_field( $input['description'] );
		}

		if ( count( $errors->get_error_messages() ) ) {
			return $errors;
		}

		return $data;
	}

	/**
	 * Sanitize tax rate.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_tax_rate( $input ) {
		$data = array();
		$data['ID'] = isset( $input['ID'] ) ? (int) $input['ID'] : '';
		$data['tax_class'] = isset( $input['tax_class'] ) ? sanitize_text_field( $input['tax_class'] ) : '';
		$data['country'] = isset( $input['country'] ) ? sanitize_text_field( $input['country'] ) : '';
		$data['state'] = isset( $input['state'] ) ? sanitize_text_field( $input['state'] ) : '';
		$data['name'] = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : '';
		$data['rate'] = isset( $input['rate'] ) ? (float) $input['rate'] : '';
		$data['priority'] = isset( $input['priority'] ) ? (int) $input['priority'] : '';
		$data['rate_order'] = isset( $input['rate_order'] ) ? (int) $input['rate_order'] : '';
		
		return $data;
	}

	/**
	 * Add tax class.
	 *
	 * @param array $data
	 */
	public function add_tax_class( $data ) {
		$classes = $this->get_tax_classes();
		$classes[ $data['name'] ] = $data['description'];

		update_option( 'ib_educator_tax_classes', $classes );
	}

	/**
	 * Delete tax class.
	 *
	 * @param string $name
	 */
	public function delete_tax_class( $name ) {
		// Do not delete default tax class.
		if ( 'default' == $name ) {
			return;
		}

		$classes = $this->get_tax_classes();
		
		if ( isset( $classes[ $name ] ) ) {
			unset( $classes[ $name ] );
			update_option( 'ib_educator_tax_classes', $classes );
		}
	}

	/**
	 * Get tax classes.
	 *
	 * @return array
	 */
	public function get_tax_classes() {
		$classes = get_option( 'ib_educator_tax_classes' );

		if ( ! is_array( $classes ) ) {
			return array();
		}

		return $classes;
	}

	/**
	 * Get a tax class name for a given object (course, membership).
	 *
	 * @param int $object_id
	 * @return string
	 */
	public function get_tax_class_for( $object_id ) {
		$tax_class = get_post_meta( $object_id, '_ib_educator_tax_class', true );

		if ( ! $tax_class ) {
			$tax_class = 'default';
		}

		return $tax_class;
	}

	/**
	 * Get tax rates.
	 *
	 * @param string $tax_class
	 * @return array
	 */
	public function get_tax_rates( $tax_class ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table . ' WHERE tax_class = %s ORDER BY rate_order ASC', $tax_class ), ARRAY_A );;
	}

	/**
	 * Update tax rate.
	 *
	 * @param array $rate
	 * @return int
	 */
	public function update_tax_rate( $rate ) {
		global $wpdb;
		$id = absint( $rate['ID'] );
		$data = array(
			'name'       => $rate['name'],
			'country'    => $rate['country'],
			'state'      => $rate['state'],
			'tax_class'  => $rate['tax_class'],
			'priority'   => $rate['priority'],
			'rate'       => $rate['rate'],
			'rate_order' => $rate['rate_order'],
		);

		if ( $id ) {
			$wpdb->update(
				$this->table,
				$data,
				array( 'ID' => $id ),
				array( '%s', '%s', '%s', '%s', '%d', '%f', '%d' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$this->table,
				$data,
				array( '%s', '%s', '%s', '%s', '%d', '%f', '%d' )
			);

			$id = $wpdb->insert_id;
		}

		return $id;
	}

	/**
	 * Delete a tax rate.
	 *
	 * @param int $id
	 */
	public function delete_tax_rate( $id ) {
		global $wpdb;

		$wpdb->delete( $this->table, array( 'ID' => $id ), array( '%d' ) );
	}
}
