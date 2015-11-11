<?php

class Edr_MembershipsRun {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_user_membership' ) );
		add_filter( 'the_content', array( __CLASS__, 'add_price_widget' ) );
		add_action( 'ib_educator_expired_memberships', array( __CLASS__, 'process_expired_memberships' ) );
		add_action( 'ib_educator_membership_notifications', array( __CLASS__, 'send_expiration_notifications' ) );
		add_filter( 'manage_ib_edu_membership_posts_columns', array( __CLASS__, 'memberships_columns' ) );
		add_filter( 'manage_ib_edu_membership_posts_custom_column', array( __CLASS__, 'memberships_column_output' ), 10, 2 );
		add_action( 'deleted_user', array( __CLASS__, 'on_deleted_user' ) );

		if ( is_admin() ) {
			add_action( 'pre_get_posts', array( __CLASS__, 'memberships_menu_order' ) );
		}
	}

	/**
	 * Check if the user's membership has expired.
	 */
	public static function check_user_membership() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$ms = Edr_Memberships::get_instance();
		$user_membership = $ms->get_user_membership( $user_id );

		if ( ! $user_membership ) {
			return;
		}

		if ( 0 == $user_membership['expiration'] ) {
			// A membership with onetime fee doesn't have expiration date.
			return;
		}

		if ( 'active' == $user_membership['status'] && time() > $user_membership['expiration'] ) {
			$ms->process_expired_membership( $user_id );
		}
	}

	/**
	 * Add membership price to single membership template.
	 *
	 * @param string $the_content
	 * @return string
	 */
	public static function add_price_widget( $the_content ) {
		if ( 'ib_edu_membership' == get_post_type() && is_single() ) {
			$ms = Edr_Memberships::get_instance();
			$the_content = $ms->get_price_widget() . $the_content;
		}
		
		return $the_content;
	}

	/**
	 * Set the entries of the expired memberships to "pending".
	 */
	public static function process_expired_memberships() {
		global $wpdb;
		$ms = Edr_Memberships::get_instance();
		$now = date( 'Y-m-d H:i:s' );
		$tables = ib_edu_table_names();
		$expired_memberships = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . $tables['members']
			. ' WHERE `expiration` <> %s AND `expiration` < %s AND `status` = %s', '0000-00-00 00:00:00', $now, 'active' ) );

		foreach ( $expired_memberships as $user_id ) {
			$ms->process_expired_membership( $user_id );
		}
	}

	/**
	 * Send the membership expiration emails to users.
	 */
	public static function send_expiration_notifications() {
		global $wpdb;
		$days_notify = ib_edu_get_option( 'days_notify', 'memberships' );

		if ( null === $days_notify ) {
			$days_notify = 5;
		} else {
			$days_notify = absint( $days_notify );
		}

		$expires_date = date( 'Y-m-d', strtotime( '+ ' . $days_notify . ' days' ) );
		$tables = ib_edu_table_names();
		$users = $wpdb->get_results( $wpdb->prepare(
			'SELECT u.ID, u.user_email, u.display_name, m.expiration, m.membership_id
			FROM ' . $tables['members'] . ' m
			INNER JOIN ' . $wpdb->users . ' u ON u.ID = m.user_id
			WHERE m.`expiration` LIKE %s AND m.`status` = %s',
			$expires_date . '%',
			'active'
		) );

		if ( empty( $users ) ) {
			return;
		}

		// Get memberships.
		$membership_ids = array();

		foreach ( $users as $user ) {
			if ( ! in_array( $user->membership_id, $membership_ids ) ) {
				$membership_ids[] = $user->membership_id;
			}
		}

		$memberships = get_posts( array(
			'post_type'      => 'ib_edu_membership',
			'include'        => $membership_ids,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		if ( $memberships ) {
			foreach ( $memberships as $key => $membership ) {
				$memberships[ $membership->ID ] = $membership;
				unset( $memberships[ $key ] );
			}
			
			foreach ( $users as $user ) {
				ib_edu_send_notification(
					$user->user_email,
					'membership_renew',
					array(),
					array(
						'student_name'           => $user->display_name,
						'membership'             => isset( $memberships[ $user->membership_id ] ) ? $memberships[ $user->membership_id ]->post_title : '',
						'expiration'             => date_i18n( get_option( 'date_format' ), strtotime( $user->expiration ) ),
						'membership_payment_url' => ib_edu_get_endpoint_url( 'edu-membership', $user->membership_id, get_permalink( ib_edu_page_id( 'payment' ) ) ),
					)
				);
			}
		}
	}

	/**
	 * Add the price column to the memberships list in the admin panel.
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function memberships_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' == $key ) {
				$new_columns['price'] = __( 'Price', 'ibeducator' );
			}
		}

		return $new_columns;
	}

	/**
	 * Output the price column on the memberships admin page.
	 *
	 * @param string $column_name
	 * @param int $post_id
	 */
	public static function memberships_column_output( $column_name, $post_id ) {
		if ( 'price' == $column_name ) {
			$ms = Edr_Memberships::get_instance();
			$membership_meta = $ms->get_membership_meta( $post_id );
			echo $ms->format_price( $membership_meta['price'], $membership_meta['duration'],
				$membership_meta['period'] );
		}
	}

	/**
	 * Delete membership data when a user is deleted.
	 *
	 * @param int $user_id
	 */
	public static function on_deleted_user( $user_id ) {
		global $wpdb;
		$tables = ib_edu_table_names();
		
		$wpdb->delete( $tables['members'], array( 'user_id' => $user_id ), array( '%d' ) );
	}

	/**
	 * Order memberships by menu_order of the memberships admin page.
	 *
	 * @param WP_Query $query
	 */
	public static function memberships_menu_order( $query ) {
		if ( $query->is_main_query() && 'ib_edu_membership' == $query->query['post_type'] ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
}
