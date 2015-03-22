<?php

class IB_Educator_Question {
	public $ID = 0;
	public $lesson_id = 0;
	public $question = '';
	public $question_type = '';
	public $menu_order = 0;
	protected $table_name;

	/**
	 * Get instance.
	 *
	 * @param mixed $data
	 * @return IB_Educator_Payment
	 */
	public static function get_instance( $data = null ) {
		if ( is_numeric( $data ) ) {
			global $wpdb;
			$tables = ib_edu_table_names();
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $tables['questions'] . " WHERE ID = %d", $data ) );
		}

		return new self( $data );
	}

	/**
	 * Constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		$tables = ib_edu_table_names();
		$this->table_name = $tables['questions'];

		if ( ! empty( $data ) ) {
			$this->ID = $data->ID;
			$this->lesson_id = $data->lesson_id;
			$this->question = $data->question;
			$this->question_type = $data->question_type;
			$this->menu_order = $data->menu_order;
		}
	}

	/**
	 * Save to database.
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;
		$affected_rows = 0;
		$data = array(
			'lesson_id'     => $this->lesson_id,
			'question'      => $this->question,
			'question_type' => $this->question_type,
			'menu_order'    => $this->menu_order
		);
		$data_format = array( '%d', '%s', '%s', '%d' );

		if ( is_numeric( $this->ID ) && $this->ID > 0 ) {
			$affected_rows = $wpdb->update(
				$this->table_name,
				$data,
				array( 'ID' => $this->ID ),
				$data_format,
				array( '%d' )
			);
		} else {
			$affected_rows = $wpdb->insert(
				$this->table_name,
				$data,
				$data_format
			);
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
}