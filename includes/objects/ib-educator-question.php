<?php

class IB_Educator_Question {
	public $ID = 0;
	public $lesson_id = 0;
	public $question = '';
	public $question_type = '';
	public $question_content = '';
	public $menu_order = 0;
	protected $table_name;

	/**
	 * Get instance.
	 *
	 * @param mixed $data
	 * @return IB_Educator_Question
	 */
	public static function get_instance( $data = null ) {
		return new self( $data );
	}

	/**
	 * Constructor.
	 *
	 * @param mixed $data
	 */
	public function __construct( $data ) {
		global $wpdb;
		$tables = ib_edu_table_names();
		$this->table_name = $tables['questions'];

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
		$this->lesson_id = $data->lesson_id;
		$this->question = $data->question;
		$this->question_type = $data->question_type;
		$this->question_content = $data->question_content;
		$this->menu_order = $data->menu_order;
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
			'lesson_id'        => $this->lesson_id,
			'question'         => $this->question,
			'question_type'    => $this->question_type,
			'question_content' => $this->question_content,
			'menu_order'       => $this->menu_order
		);
		$data_format = array( '%d', '%s', '%s', '%s', '%d' );

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

		return ( 1 === $affected_rows || 0 === $affected_rows );
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
