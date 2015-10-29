<?php

// Forbid direct access.
if ( ! defined( 'ABSPATH' ) ) exit();

// Load the WP_List_Table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Payments list table.
 */
class Edr_Admin_PaymentsTable extends WP_List_Table {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Payment', 'ibeducator' ),
			'plural'   => __( 'Payments', 'ibeducator' ),
			'ajax'     => false,
		) );

		$this->process_bulk_action();
	}

	/**
	 * Display the filters form.
	 */
	public function display_payment_filters() {
		$types = edr_get_payment_types();
		$statuses = edr_get_payment_statuses();
		?>
		<div class="ib-edu-tablenav top">
			<form class="ib-edu-admin-search" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="ib_educator_payments">
				<div class="block">
					<label for="search-payment-id"><?php echo _x( 'ID', 'ID of an item', 'ibeducator' ); ?></label>
					<input type="text" id="search-payment-id" name="id" value="<?php if ( ! empty( $_GET['id'] ) ) echo intval( $_GET['id'] ); ?>">
				</div>
				<div class="block">
					<label for="search-payment-type"><?php _e( 'Payment Type', 'ibeducator' ); ?></label>
					<select id="search-payment-type" name="payment_type">
						<option value=""><?php _e( 'All', 'ibeducator' ); ?></option>
						<?php
							foreach ( $types as $t_value => $t_name ) {
								$selected = ( isset( $_GET['payment_type'] ) && $t_value == $_GET['payment_type'] ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $t_value ) . '"' . $selected . '>' . esc_html( $t_name ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<label for="search-payment-status"><?php _e( 'Status', 'ibeducator' ); ?></label>
					<select id="search-payment-status" name="status">
						<option value=""><?php _e( 'All', 'ibeducator' ); ?></option>
						<?php
							foreach ( $statuses as $key => $value ) {
								$selected = ( isset( $_GET['status'] ) && $key == $_GET['status'] ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<input type="submit" class="button" value="<?php _e( 'Search', 'ibeducator' ); ?>">
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Define columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox">',
			'ID'           => _x( 'ID', 'ID of an item', 'ibeducator' ),
			'item'         => __( 'Item', 'ibeducator' ),
			'payment_type' => __( 'Payment Type', 'ibeducator' ),
			'username'     => __( 'Username', 'ibeducator' ),
			'amount'       => __( 'Amount', 'ibeducator' ),
			'method'       => __( 'Method', 'ibeducator' ),
			'status'       => __( 'Status', 'ibeducator' ),
			'date'         => __( 'Date', 'ibeducator' ),
		);

		return $columns;
	}

	/**
	 * Column: cb.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="payment[]" value="' . intval( $item->ID ) . '">';
	}

	/**
	 * Column: ID.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_ID( $item ) {
		return intval( $item->ID );
	}

	/**
	 * Column: item.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_item( $item ) {
		$post = null;
		$object_title = '';

		if ( 'course' == $item->payment_type ) {
			$post = get_post( $item->course_id );
		} elseif ( 'membership' == $item->payment_type ) {
			$post = get_post( $item->object_id );
		}

		if ( $post ) {
			$object_title = $post->post_title;
		}

		$base_url = admin_url( 'admin.php?page=ib_educator_payments' );
		$edit_url = admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $item->ID );
		$delete_url = wp_nonce_url( add_query_arg( array( 'edu-action' => 'delete-payment', 'payment_id' => $item->ID ), $base_url ), 'edr_delete_payment' );

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'ibeducator' ) . '</a>';
		$actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" class="delete-payment">' . __( 'Delete', 'ibeducator' ) . '</a>';

		return '<strong>' . esc_html( $object_title ) . '</strong>' . $this->row_actions( $actions );
	}

	/**
	 * Column: payment_type.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_payment_type( $item ) {
		return esc_html( $item->payment_type );
	}

	/**
	 * Column: username.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_username( $item ) {
		$user = get_user_by( 'id', $item->user_id );

		if ( $user ) {
			return esc_html( $user->user_login );
		}

		return '';
	}

	/**
	 * Column: amount.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_amount( $item ) {
		return sanitize_title( $item->currency ) . ' ' . number_format( $item->amount, 2 );
	}

	/**
	 * Column: method.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_method( $item ) {
		return sanitize_title( $item->payment_gateway );
	}

	/**
	 * Column: status.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_status( $item ) {
		return sanitize_title( $item->payment_status );
	}

	/**
	 * Column: date.
	 *
	 * @param IB_Educator_Payment $item
	 * @return string
	 */
	public function column_date( $item ) {
		return date( 'j M, Y H:i', strtotime( $item->payment_date ) );
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'ibeducator' ),
		);

		return $actions;
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		$ids = isset( $_POST['payment'] ) ? $_POST['payment'] : null;

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return;
		}

		$action = $this->current_action();

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$payment = edr_get_payment( $id );

				if ( $payment->ID ) {
					$payment->delete();
				}
			}
		}
	}

	/**
	 * Prepare items.
	 * Fetch and setup payments(items).
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$statuses = edr_get_payment_statuses();
		$args = array(
			'per_page' => $this->get_items_per_page( 'payments_per_page', 10 ),
			'page'     => $this->get_pagenum(),
		);

		if ( ! empty( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ) {
			$args['payment_status'] = array( $_GET['status'] );
		}

		if ( ! empty( $_GET['id'] ) ) {
			$args['payment_id'] = $_GET['id'];
		}

		if ( ! empty( $_GET['payment_type'] ) ) {
			$args['payment_type'] = $_GET['payment_type'];
		}

		$payments = IB_Educator::get_instance()->get_payments( $args );

		if ( ! empty( $payments ) ) {
			$this->set_pagination_args( array(
				'total_items' => $payments['num_items'],
				'per_page'    => $args['per_page'],
			) );

			$this->items = $payments['rows'];
		}
	}
}
