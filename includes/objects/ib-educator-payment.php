<?php

class IB_Educator_Payment {
	public $ID = 0;
	public $parent_id = 0;
	public $course_id = 0;
	public $user_id = 0;
	public $object_id = 0;
	public $txn_id = '';
	public $payment_type = '';
	public $payment_gateway = '';
	public $payment_status = '';
	public $amount = 0.00;
	public $tax = 0.00;
	public $currency = '';
	public $payment_date = '';
	public $first_name = '';
	public $last_name = '';
	public $address = '';
	public $address_2 = '';
	public $city = '';
	public $state = '';
	public $postcode = '';
	public $country = '';
	public $ip = '';
	protected $table_name;
	protected $lines_table;

	/**
	 * Get instance.
	 *
	 * @param mixed $data
	 * @return IB_Educator_Payment
	 */
	public static function get_instance( $data = null ) {
		return new self( $data );
	}

	/**
	 * Get available statuses.
	 *
	 * @return array
	 */
	public static function get_statuses() {
		_ib_edu_deprecated_function( 'IB_Educator_Payment::get_statuses', '1.7', 'edr_get_payment_statuses' );

		return edr_get_payment_statuses();
	}

	/**
	 * Get available types.
	 *
	 * @return array
	 */
	public static function get_types() {
		_ib_edu_deprecated_function( 'IB_Educator_Payment::get_types', '1.7', 'edr_get_payment_types' );

		return edr_get_payment_types();
	}


	/**
	 * Constructor
	 *
	 * @param mixed $data
	 */
	public function __construct( $data ) {
		global $wpdb;
		$tables = ib_edu_table_names();
		$this->table_name = $tables['payments'];
		$this->lines_table = $tables['payment_lines'];

		if ( is_numeric( $data ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE ID = %d", $data ) );
		}

		if ( ! empty( $data ) ) {
			$this->set_data( $data );
		}
	}

	/**
	 * Set data.
	 *
	 * @param object $data
	 */
	public function set_data( $data ) {
		$this->ID = $data->ID;
		$this->parent_id = $data->parent_id;
		$this->course_id = $data->course_id;
		$this->user_id = $data->user_id;
		$this->object_id = $data->object_id;
		$this->txn_id = $data->txn_id;
		$this->payment_type = $data->payment_type;
		$this->payment_gateway = $data->payment_gateway;
		$this->payment_status = $data->payment_status;
		$this->amount = $data->amount;
		$this->tax = $data->tax;
		$this->currency = $data->currency;
		$this->payment_date = $data->payment_date;
		$this->first_name = $data->first_name;
		$this->last_name = $data->last_name;
		$this->address = $data->address;
		$this->address_2 = $data->address_2;
		$this->city = $data->city;
		$this->state = $data->state;
		$this->postcode = $data->postcode;
		$this->country = $data->country;
		$this->ip = ( $data->ip ) ? inet_ntop( $data->ip ) : '';
	}

	/**
	 * Save to database.
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;
		$affected_rows = 0;
		$update = ( is_numeric( $this->ID ) && $this->ID > 0 );
		$ip = '';

		if ( $this->ip ) {
			$ip = inet_pton( $this->ip );

			if ( ! $ip ) {
				$ip = '';
			}
		}
		
		if ( ! $update && empty( $this->payment_date ) ) {
			$this->payment_date = date( 'Y-m-d H:i:s' );
		}

		$data = array(
			'parent_id'       => $this->parent_id,
			'course_id'       => $this->course_id,
			'user_id'         => $this->user_id,
			'object_id'       => $this->object_id,
			'txn_id'          => $this->txn_id,
			'payment_type'    => $this->payment_type,
			'payment_gateway' => $this->payment_gateway,
			'payment_status'  => sanitize_text_field( $this->payment_status ),
			'amount'          => $this->amount,
			'tax'             => $this->tax,
			'currency'        => $this->currency,
			'payment_date'    => $this->payment_date,
			'first_name'      => $this->first_name,
			'last_name'       => $this->last_name,
			'address'         => $this->address,
			'address_2'       => $this->address_2,
			'city'            => $this->city,
			'state'           => $this->state,
			'postcode'        => $this->postcode,
			'country'         => $this->country,
			'ip'              => $ip,
		);

		$data_format = array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( $update ) {
			$affected_rows = $wpdb->update( $this->table_name, $data, array( 'ID' => $this->ID ), $data_format, array( '%d' ) );
		} else {
			$affected_rows = $wpdb->insert( $this->table_name, $data, $data_format );
			$this->ID = $wpdb->insert_id;
		}
		
		return ( 1 === $affected_rows || 0 === $affected_rows ) ? true : false;
	}

	/**
	 * Delete from database.
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;
		
		if ( $wpdb->delete( $this->table_name, array( 'ID' => $this->ID ), array( '%d' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Update payment status.
	 *
	 * @param string $new_status
	 * @return int Number of rows updated.
	 */
	public function update_status( $new_status ) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name,
			array( 'payment_status' => $new_status ),
			array( 'ID' => $this->ID ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get lines.
	 *
	 * @return array
	 */
	public function get_lines() {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->lines_table . ' WHERE payment_id = %d', $this->ID ) );
	}

	/**
	 * Update line.
	 *
	 * @param array $meta_item
	 */
	public function update_line( $line ) {
		global $wpdb;
		$update = isset( $line['ID'] ) && is_numeric( $line['ID'] ) && $line['ID'] > 0;
		$data = array(
			'payment_id' => $this->ID,
			'object_id'  => $line['object_id'],
			'line_type'  => $line['line_type'],
			'amount'     => $line['amount'],
			'name'       => $line['name'],
		);
		$data_format = array( '%d', '%d', '%s', '%f', '%s' );

		if ( isset( $line['tax'] ) ) {
			$data['tax'] = $line['tax'];
			$data_format[] = '%f';
		}

		if ( $update ) {
			$wpdb->update( $this->lines_table, $data, array( 'ID' => $line['ID'] ), $data_format, array( '%d' ) );
		} else {
			$wpdb->insert( $this->lines_table, $data, $data_format );
		}
	}
}
