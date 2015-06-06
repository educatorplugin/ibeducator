<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class IB_Educator_Memberships {
	/**
	 * @var IB_Educator_Memberships
	 */
	protected static $instance;

	/**
	 * @var string
	 */
	public $post_type = 'ib_edu_membership';

	/**
	 * @var string
	 */
	protected $tbl_members;

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$tables = ib_edu_table_names();
		$this->tbl_members = $tables['members'];
	}

	/**
	 * Get instance.
	 *
	 * @return IB_Educator_Memberships
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get available membership payment periods.
	 *
	 * @return array
	 */
	public function get_periods() {
		return array(
			'onetime' => __( 'Onetime Fee', 'ibeducator' ),
			'days'    => __( 'Day(s)', 'ibeducator' ),
			'months'  => __( 'Month(s)', 'ibeducator' ),
			'years'   => __( 'Year(s)', 'ibeducator' ),
		);
	}

	/**
	 * Get available membership statuses.
	 *
	 * @return array
	 */
	public function get_statuses() {
		return array(
			'expired' => __( 'Expired', 'ibeducator' ),
			'active'  => __( 'Active', 'ibeducator' ),
		);
	}

	/**
	 * Get all membership posts.
	 *
	 * @return array
	 */
	public function get_memberships() {
		return get_posts( array(
			'post_type'      => $this->post_type,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
	}

	/**
	 * Get one membership post.
	 *
	 * @param int $membership_id
	 * @return false|WP_Post
	 */
	public function get_membership( $membership_id ) {
		return get_post( $membership_id );
	}

	/**
	 * Get users who signed up for a membership.
	 *
	 * @param array $args
	 * @return WP_User_Query
	 */
	public function get_members( $args ) {
		global $wpdb;
		$user_query = new WP_User_Query();
		$user_query->prepare_query( $args );
		$user_query->query_from .= ' INNER JOIN ' . $this->tbl_members . ' ib_edu_m ON ib_edu_m.user_id = ' . $wpdb->users . '.ID';
		$user_query->query();

		return $user_query;
	}

	/**
	 * Get membership price.
	 *
	 * @param int $membership_id
	 * @return float
	 */
	public function get_price( $membership_id ) {
		$meta = $this->get_membership_meta( $membership_id );
		
		return (float) $meta['price'];
	}

	/**
	 * Format membership price.
	 *
	 * @param float $price
	 * @param int $duration
	 * @param string $period
	 * @return string
	 */
	public function format_price( $price, $duration, $period, $symbol = true ) {
		$price_str = ib_edu_format_price( $price, true, $symbol );

		switch ( $period ) {
			case 'days':
				$price_str .= ' ' . sprintf( _n( 'per day', 'per %d days', $duration, 'ibeducator' ), intval( $duration ) );
				break;

			case 'months':
				$price_str .= ' ' . sprintf( _n( 'per month', 'per %d months', $duration, 'ibeducator' ), intval( $duration ) );
				break;

			case 'years':
				$price_str .= ' ' . sprintf( _n( 'per year', 'per %d years', $duration, 'ibeducator' ), intval( $duration ) );
				break;
		}

		return $price_str;
	}

	/**
	 * Get membership meta.
	 *
	 * @param int $membership_id
	 * @return array
	 */
	public function get_membership_meta( $membership_id = 0 ) {
		if ( $membership_id ) {
			$meta = get_post_meta( $membership_id, '_ib_educator_membership', true );
			if ( ! is_array( $meta ) ) $meta = array();
		} else {
			$meta = array();
		}

		$meta = wp_parse_args( $meta, array(
			'price'      => 0.0,
			'period'     => 'onetime',
			'duration'   => 0,
			'categories' => array(),
		) );

		return $meta;
	}

	/**
	 * Calculate expiration date.
	 *
	 * @param int $duration
	 * @param string $period
	 * @param string $from_ts
	 * @return string
	 */
	public function calculate_expiration_date( $duration, $period, $from_ts = 0 ) {
		if ( empty( $from_ts ) ) {
			$from_ts = time();
		}

		$ts = 0;

		switch ( $period ) {
			case 'days':
				$ts = strtotime( '+ ' . $duration . ' days', $from_ts );
				break;

			case 'months':
				$cur_date = explode( '-', date( 'Y-n-j', $from_ts ) );
				$next_month = $cur_date[1] + $duration;
				$next_year = $cur_date[0];
				$next_day = $cur_date[2];

				if ( $next_month > 12 ) {
					$next_month -= 12;
					$next_year += 1;
				}

				$cur_month_days = date( 't', $from_ts );
				$next_month_days = date( 't', strtotime( "$next_year-$next_month-1" ) );

				if ( $cur_date[2] == $cur_month_days || $next_day > $next_month_days ) {
					// If today is the last day of the month or the next day
					// is bigger than the number of days in the next month,
					// set the next day to the last day of the next month.
					$next_day = $next_month_days;
				}

				$ts = strtotime( "$next_year-$next_month-$next_day 23:59:59" );
				break;

			case 'years':
				$cur_date = explode( '-', date( 'Y-n-j', $from_ts ) );
				
				$next_year = $cur_date[0] + $duration;
				$next_month = $cur_date[1];
				$next_day = $cur_date[2];

				$cur_month_days = date( 't', $from_ts );
				$next_month_days = date( 't', strtotime( "$next_year-$next_month-1" ) );

				if ( $cur_date[2] == $cur_month_days || $next_day > $next_month_days ) {
					// Account for February, where the number of days differs if it's leap year.
					$next_day = $next_month_days;
				}

				$ts = strtotime( "$next_year-$next_month-$next_day 23:59:59" );
				break;
		}

		return $ts;
	}

	/**
	 * Modify expiration date given duration (e.g., 3 months, 1 year, etc).
	 *
	 * @param int $duration
	 * @param string $period
	 * @param string $direction - or +
	 * @param int $from_ts
	 * @return int Timstamp
	 */
	public function modify_expiration_date( $duration, $period, $direction = '+', $from_ts = 0 ) {
		if ( empty( $from_ts ) ) {
			$from_ts = time();
		}

		$ts = 0;

		switch ( $period ) {
			case 'days':
				$ts = strtotime( $direction . ' ' . $duration . ' days', $from_ts );

				break;

			case 'months':
				$from_date = explode( '-', date( 'Y-n-j', $from_ts ) );
				$to_month = ( '-' == $direction ) ? $from_date[1] - $duration : $from_date[1] + $duration;
				$to_year = $from_date[0];
				$to_day = $from_date[2];

				if ( $to_month < 1 ) {
					$to_month += 12;
					$to_year -= 1;
				} elseif ( $to_month > 12 ) {
					$to_month -= 12;
					$to_year += 1;
				}

				$from_month_days = date( 't', $from_ts );
				$to_month_days = date( 't', strtotime( "$to_year-$to_month-1" ) );

				if ( $from_date[2] == $from_month_days || $to_day > $to_month_days ) {
					// If today is the last day of the month or the next day
					// is bigger than the number of days in the next month,
					// set the next day to the last day of the next month.
					$to_day = $to_month_days;
				}

				$ts = strtotime( "$to_year-$to_month-$to_day 23:59:59" );

				break;

			case 'years':
				$from_date = explode( '-', date( 'Y-n-j', $from_ts ) );

				$to_year = ( '-' == $direction ) ? $from_date[0] - $duration : $from_date[0] + $duration;
				$to_month = $from_date[1];
				$to_day = $from_date[2];

				$from_month_days = date( 't', $from_ts );
				$to_month_days = date( 't', strtotime( "$to_year-$to_month-1" ) );

				if ( $from_date[2] == $from_month_days || $to_day > $to_month_days ) {
					// Account for February, where the number of days differs if it's a leap year.
					$to_day = $to_month_days;
				}

				$ts = strtotime( "$to_year-$to_month-$to_day 23:59:59" );

				break;
		}

		return $ts;
	}

	/**
	 * Get the user's membership data.
	 *
	 * @param int $user_id
	 * @return null|array
	 */
	public function get_user_membership( $user_id ) {
		global $wpdb;
		
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->tbl_members WHERE `user_id` = %d", $user_id ) );

		if ( null != $row ) {
			return array(
				'ID'            => $row->ID,
				'user_id'       => $row->user_id,
				'membership_id' => $row->membership_id,
				'status'        => $row->status,
				'expiration'    => ( '0000-00-00 00:00:00' != $row->expiration ) ? strtotime( $row->expiration ) : 0,
				'paused'        => ( '0000-00-00 00:00:00' != $row->paused ) ? strtotime( $row->paused ) : 0,
			);
		}

		return null;
	}

	/**
	 * Update the user's membership data.
	 *
	 * @param array $input
	 * @return int
	 */
	public function update_user_membership( $input ) {
		global $wpdb;
		$data = array(
			'user_id'       => 0,
			'membership_id' => 0,
			'status'        => '',
			'expiration'    => '',
			'paused'        => '',
		);

		if ( isset( $input['user_id'] ) ) {
			$data['user_id'] = $input['user_id'];
		}

		if ( isset( $input['membership_id'] ) ) {
			$data['membership_id'] = $input['membership_id'];
		}

		if ( isset( $input['status'] ) ) {
			$data['status'] = sanitize_text_field( $input['status'] );
		}

		if ( isset( $input['expiration'] ) ) {
			$data['expiration'] = sanitize_text_field( $input['expiration'] );
		}

		if ( isset( $input['paused'] ) ) {
			$data['paused'] = sanitize_text_field( $input['paused'] );
		}

		// Save changes.
		if ( isset( $input['ID'] ) && intval( $input['ID'] ) == $input['ID'] && $input['ID'] > 0 ) {
			$wpdb->update(
				$this->tbl_members,
				$data,
				array( 'ID' => $input['ID'] ),
				array( '%d', '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);

			$data['ID'] = $input['ID'];
		} else {
			$wpdb->insert(
				$this->tbl_members,
				$data,
				array( '%d', '%d', '%s', '%s', '%s' )
			);

			$data['ID'] = $wpdb->insert_id;
		}

		return $data['ID'];
	}

	/**
	 * Setup membership for a user.
	 *
	 * @param int $user_id,
	 * @param int $membership_id
	 */
	public function setup_membership( $user_id, $membership_id ) {
		$user_membership = $this->get_user_membership( $user_id );
		$membership = get_post( $membership_id );

		// Does membership exist?
		if ( ! $membership ) {
			return;
		}

		// Get membership meta.
		$membership_meta = $this->get_membership_meta( $membership_id );

		// Pause the course entries that were originated by the current membership
		// if the new membership differs.
		if ( ! $user_membership || $membership_id != $user_membership['membership_id'] ) {
			$this->pause_membership_entries( $user_id );
		}

		// Setup/update user's membership.
		$expiration = 0;

		if ( 'onetime' != $membership_meta['period'] ) {
			$from_ts = 0;

			if ( $user_membership && 'expired' != $user_membership['status'] && $membership_id == $user_membership['membership_id'] ) {
				// Extend membership.
				$from_ts = $user_membership['expiration'];
			}

			$expiration = $this->calculate_expiration_date( $membership_meta['duration'],
				$membership_meta['period'], $from_ts );
		}

		$data = array(
			'ID'            => ( $user_membership ) ? $user_membership['ID'] : 0,
			'user_id'       => $user_id,
			'membership_id' => $membership->ID,
			'status'        => ( 'paused' != $user_membership['status'] ) ? 'active' : $user_membership['status'],
			'expiration'    => ( $expiration > 0 ) ? date( 'Y-m-d H:i:s', $expiration ) : '0000-00-00 00:00:00',
		);

		// Save changes.
		$this->update_user_membership( $data );
	}

	/**
	 * Check if the membership has expired.
	 *
	 * @param array $meta User's membership data.
	 * @return bool
	 */
	public function has_expired( $user_membership ) {
		if ( ! $user_membership || 'active' != $user_membership['status'] ) {
			return true;
		}

		$membership_meta = $this->get_membership_meta( $user_membership['membership_id'] );

		if ( 'onetime' == $membership_meta['period'] ) {
			return false;
		}

		$expiration_time = ! empty( $user_membership['expiration'] ) ? $user_membership['expiration'] : false;

		return ( $expiration_time && time() > $expiration_time );
	}

	/**
	 * Check if the user can access a given course.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return bool
	 */
	public function membership_can_access( $course_id, $user_id ) {
		global $wpdb;
		$user_membership = $this->get_user_membership( $user_id );

		if ( $this->has_expired( $user_membership ) ) {
			return false;
		}

		$membership_meta = $this->get_membership_meta( $user_membership['membership_id'] );

		if ( empty( $membership_meta['categories'] ) ) {
			return false;
		}

		$categories_sql = implode( ',', array_map( 'intval', $membership_meta['categories'] ) );
		
		$post_ids = $wpdb->get_col(
			"SELECT p.ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
			WHERE tt.term_id IN ($categories_sql) AND tt.taxonomy='ib_educator_category'"
		);
		
		return ( $post_ids && in_array( $course_id, $post_ids ) );
	}

	/**
	 * Update membership entries' status.
	 *
	 * @param int $user_id
	 * @param string $status
	 */
	public function update_membership_entries( $user_id, $status ) {
		global $wpdb;
		$tables = ib_edu_table_names();

		$wpdb->update(
			$tables['entries'],
			array(
				'entry_status' => $status,
			),
			array(
				'user_id'      => $user_id,
				'entry_origin' => 'membership',
				'entry_status' => 'inprogress',
			),
			array( '%s' ),
			array( '%d', '%s', '%s' )
		);
	}

	/**
	 * Pause the user's entries of entry_origin "membership".
	 *
	 * @param int $user_id
	 */
	public function pause_membership_entries( $user_id ) {
		$this->update_membership_entries( $user_id, 'paused' );
	}

	/**
	 * Process paused membership.
	 *
	 * @param int $user_id
	 */
	public function pause_membership( $user_id, $pause_ts = null ) {
		// Get the user's membership data.
		$user_membership = $this->get_user_membership( $user_id );

		if ( ! $user_membership ) {
			return;
		}

		$membership_meta = $this->get_membership_meta( $user_membership['membership_id'] );

		if ( ! $pause_ts ) {
			if ( 'days' == $membership_meta['period'] ) {
				$pause_ts = time();
			} else {
				$pause_ts = strtotime( date( 'Y-m-d 23:59:59' ) );
			}
		}

		// Update membership status.
		global $wpdb;

		$wpdb->update(
			$this->tbl_members,
			array(
				'status' => 'paused',
				'paused' => date( 'Y-m-d H:i:s', $pause_ts ),
			),
			array( 'ID' => $user_membership['ID'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// Pause entries.
		$this->pause_membership_entries( $user_id );
	}

	/**
	 * Process resumed membership.
	 *
	 * @param int $user_id
	 */
	public function resume_membership( $user_id, $today_ts = null ) {
		// Get the user's membership data.
		$user_membership = $this->get_user_membership( $user_id );

		if ( ! $user_membership ) {
			return;
		}

		$membership_meta = $this->get_membership_meta( $user_membership['membership_id'] );

		if ( ! $today_ts ) {
			if ( 'days' == $membership_meta['period'] ) {
				$today_ts = time();
			} else {
				$today_ts = strtotime( date( 'Y-m-d 23:59:59' ) );
			}
		}

		if ( $user_membership['paused'] ) {
			$expiration = $today_ts + $user_membership['expiration'] - $user_membership['paused'];
		} else {
			$expiration = $user_membership['expiration'];
		}

		global $wpdb;

		$wpdb->update(
			$this->tbl_members,
			array(
				'status'     => 'active',
				'expiration' => date( 'Y-m-d H:i:s', $expiration ),
			),
			array( 'ID' => $user_membership['ID'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Process expired membership.
	 *
	 * @param int $user_id
	 */
	public function process_expired_membership( $user_id ) {
		// Update membership status.
		global $wpdb;

		$wpdb->update(
			$this->tbl_members,
			array( 'status' => 'expired' ),
			array( 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Pause entries.
		$this->pause_membership_entries( $user_id );
	}

	/**
	 * Get the membership price widget.
	 *
	 * @param int $membership_id
	 * @return string
	 */
	public function get_price_widget( $membership_id = null ) {
		if ( is_null( $membership_id ) ) {
			$membership_id = get_the_ID();
		}

		$output = apply_filters( 'ib_educator_membership_price_widget', null, $membership_id );

		if ( ! is_null( $output ) ) {
			return $output;
		}

		$price = $this->get_price( $membership_id );
		$payment_url = ib_edu_get_endpoint_url( 'edu-membership', $membership_id, get_permalink( ib_edu_page_id( 'payment' ) ) );
		$output = '<div class="ib-edu-price-widget">';
		$output .= '<span class="price">' . ib_edu_format_price( $price ) . '</span>';
		$output .= '<a href="' . esc_url( $payment_url ) . '" class="ib-edu-button">' . __( 'Purchase', 'ibeducator' ) . '</a>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Record membership level change.
	 *
	 * @param IB_Educator_Payment $payment
	 */
	public function record_switch( $payment ) {
		return;
	}
}

class IB_Educator_Memberships_Run {
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

		$ms = IB_Educator_Memberships::get_instance();
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
			$ms = IB_Educator_Memberships::get_instance();
			$the_content = $ms->get_price_widget() . $the_content;
		}
		
		return $the_content;
	}

	/**
	 * Set the entries of the expired memberships to "pending".
	 */
	public static function process_expired_memberships() {
		global $wpdb;
		$ms = IB_Educator_Memberships::get_instance();
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
			$ms = IB_Educator_Memberships::get_instance();
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

IB_Educator_Memberships_Run::init();