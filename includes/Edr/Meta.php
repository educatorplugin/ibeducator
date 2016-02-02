<?php

class Edr_Meta {
	/**
	 * @var string
	 */
	protected $table;

	/**
	 * Constructor.
	 *
	 * @param string $table
	 */
	public function __construct( $table ) {
		$this->table = $table;
	}

	/**
	 * Get a given object's meta.
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 * @param bool $single
	 * @return (array|string|null)
	 */
	public function get_meta( $object_id, $meta_key, $single = false ) {
		global $wpdb;

		$sql = 'SELECT meta_value FROM ' . $this->table . ' WHERE object_id = %d AND meta_key = %s';
		$results = $wpdb->get_col( $wpdb->prepare( $sql, $object_id, $meta_key ) );
		
		if ( $single ) {
			return ( ! empty( $results ) ) ? $results[0] : null;
		}

		return $results;
	}

	/**
	 * Update a given object's meta.
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 * @param mixed $prev_value
	 * @return (int|false) The number of rows affected or false on error.
	 */
	public function update_meta( $object_id, $meta_key, $meta_value, $prev_value ) {
		global $wpdb;

		$sql = 'SELECT meta_id FROM ' . $this->table . ' WHERE object_id = %d AND meta_key = %s LIMIT 1';
		$meta_id = $wpdb->get_var( $wpdb->prepare( $sql, $object_id, $meta_key ) );

		if ( $meta_id ) {
			$where = array( 'object_id' => $object_id, 'meta_key' => $meta_key );
			$where_format = array( '%d', '%s' );

			if ( ! empty( $prev_value ) ) {
				$where['meta_value'] = maybe_serialize( $prev_value );
				$where_format[] = '%s';
			}

			return $wpdb->update(
				$this->table,
				array( 'meta_value' => maybe_serialize( $meta_value ) ),
				$where,
				array( '%s' ),
				$where_format
			);
		}

		return $wpdb->insert(
			$this->table,
			array(
				'object_id'  => $object_id,
				'meta_key'   => $meta_key,
				'meta_value' => maybe_serialize( $meta_value ),
			),
			array( '%d', '%s', '%s' )
		);
	}
}

