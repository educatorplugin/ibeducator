<?php
/**
 * Unit tests for memberships.
 */
class IB_Educator_Test_Memberships extends IB_Educator_Tests {
	protected $memberships = array();

	/**
	 * Setup initial tests data.
	 */
	public function setUp() {
		parent::setUp();
		$this->basicSetUp();

		$ms = Edr_Memberships::get_instance();

		// Add categories to courses.
		wp_set_post_terms( $this->courses[0], array( $this->categories[0] ), 'ib_educator_category' );
		wp_set_post_terms( $this->courses[1], array( $this->categories[1], $this->categories[2] ), 'ib_educator_category' );

		// This membership gives access to the first course only.
		$this->memberships['months'] = $this->addMembership( array(
			'price'      => 187.32,
			'period'     => 'months',
			'duration'   => 3,
			'categories' => array( $this->categories[0] ),
		) );

		// Expired membership.
		$this->memberships['expired'] = $this->addMembership( array(
			'price'      => 1283.32,
			'period'     => 'days',
			'duration'   => 10,
			'categories' => array( $this->categories[1] ),
		) );

		// Setup payment for the membership.
		$this->payments['membership_months'] = $this->addPayment( array(
			'payment_type'   => 'membership',
			'payment_status' => 'complete',
			'object_id'      => $this->memberships['months'],
			'user_id'        => $this->users['student1'],
		) );

		// Setup membership for the student.
		$ms->setup_membership( $this->users['student1'], $this->memberships['months'] );
		$ms->setup_membership( $this->admin_id, $this->memberships['expired'] );
	}

	/**
	 * Add membership.
	 */
	public function addMembership( $data ) {
		$post_id = wp_insert_post( array(
			'post_type'   => 'ib_edu_membership',
			'post_name'   => 'membership-1',
			'post_title'  => 'membership 1',
			'post_status' => 'publish',
		) );
		$ms = Edr_Memberships::get_instance();
		$meta = $ms->get_membership_meta();
		$meta['price'] = $data['price'];
		$meta['period'] = $data['period'];
		$meta['duration'] = $data['duration'];
		$meta['categories'] = $data['categories'];

		update_post_meta( $post_id, '_ib_educator_membership', $meta );

		return $post_id;
	}

	// ------
	// TESTS:
	// ------

	/**
	 * Get price of a membership.
	 */
	public function testGetPrice() {
		$ms = Edr_Memberships::get_instance();
		$this->assertEquals( 187.32, $ms->get_price( $this->memberships['months'] ) );
		$this->assertEquals( 1283.32, $ms->get_price( $this->memberships['expired'] ) );
	}

	/**
	 * Setup membership.
	 */
	public function testSetupMembership() {
		$ms = Edr_Memberships::get_instance();
		$user_membership = $ms->get_user_membership( $this->users['student1'] );
		$meta = $ms->get_membership_meta( $this->memberships['months'] );
		
		$this->assertEquals( $this->memberships['months'], $user_membership['membership_id'] );
		$this->assertEquals( $ms->calculate_expiration_date( $meta['duration'], $meta['period'], 0 ), $user_membership['expiration'] );
		$this->assertEquals( 'active', $user_membership['status'] );
	}

	/**
	 * Test access control based on memberships.
	 */
	public function testMembershipCanAccess() {
		$ms = Edr_Memberships::get_instance();
		
		// student1 should be able to access the first course.
		$can_access = $ms->membership_can_access( $this->courses[0], $this->users['student1'] );
		$this->assertTrue( $can_access );

		// student1 cannot access other courses.
		$can_access = $ms->membership_can_access( $this->courses[1], $this->users['student1'] );
		$this->assertFalse( $can_access );

		// admin's membership hasn't expired.
		$can_access = $ms->membership_can_access( $this->courses[1], $this->admin_id );
		$this->assertTrue( $can_access );

		// admin's membership has expired.
		$um = $ms->get_user_membership( $this->admin_id );
		$um['expiration'] = date( 'Y-m-d 23:59:59', strtotime( '- 1 day' ) );
		$ms->update_user_membership( $um );
		$can_access = $ms->membership_can_access( $this->courses[1], $this->admin_id );
		$this->assertFalse( $can_access );
	}

	/**
	 * Test the expiration date calculations.
	 */
	public function testExpirationCalculations() {
		$ms = Edr_Memberships::get_instance();
		$tomorrow = strtotime( '+ 1 days', strtotime( date( 'Y-m-d 23:59:59' ) ) );

		// Days.
		$expiration = $ms->calculate_expiration_date( 53, 'days', strtotime( '2014-01-01 23:59:59' ) );
		$this->assertEquals( '2014-02-23 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $ms->calculate_expiration_date( 1, 'days' );
		$this->assertEquals( date( 'Y-m-d H:i:s', strtotime( '+ 1 days', time() ) ), date( 'Y-m-d H:i:s', $expiration ) );

		// 3 Years.
		$expiration = $ms->calculate_expiration_date( 3, 'years', strtotime( '2014-09-18' ) );
		$this->assertEquals( '2017-09-18 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 9 Years.
		$expiration = $ms->calculate_expiration_date( 9, 'years', strtotime( '2014-09-18' ) );
		$this->assertEquals( '2023-09-18 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 19 Years.
		$expiration = $ms->calculate_expiration_date( 19, 'years', strtotime( '2014-12-18' ) );
		$this->assertEquals( '2033-12-18 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// Leap year.
		$expiration = $ms->calculate_expiration_date( 2, 'years', strtotime( '2016-02-29 14:30:24' ) );
		$this->assertEquals( '2018-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $ms->calculate_expiration_date( 1, 'years', strtotime( '2015-02-28' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 7 Months.
		$expiration = $ms->calculate_expiration_date( 7, 'months', strtotime( '2014-01-01' ) );
		$this->assertEquals( '2014-08-01 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 6 Months.
		$expiration = $ms->calculate_expiration_date( 6, 'months', strtotime( '2014-08-31' ) );
		$this->assertEquals( '2015-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// February.
		$expiration = $ms->calculate_expiration_date( 1, 'months', strtotime( '2015-01-31' ) );
		$this->assertEquals( '2015-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// Leap year.
		$expiration = $ms->calculate_expiration_date( 1, 'months', strtotime( '2016-01-31' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// Test the last day of the month rule.
		$expiration = $ms->calculate_expiration_date( 1, 'months', strtotime( '2015-02-28 22:45:23' ) );
		$this->assertEquals( '2015-03-31 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $ms->calculate_expiration_date( 1, 'months', strtotime( '2015-03-31' ) );
		$this->assertEquals( '2015-04-30 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $ms->calculate_expiration_date( 1, 'months', strtotime( '2017-01-31 12:00:01' ) );
		$this->assertEquals( '2017-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $ms->calculate_expiration_date( 1, 'months', strtotime( '2016-01-31' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );
	}

	public function testModifyExpirationDate() {
		$ms = Edr_Memberships::get_instance();

		// -1 Day.
		$expiration = $ms->modify_expiration_date( 1, 'days', '-', strtotime( '2016-01-01 23:59:59' ) );
		$this->assertEquals( '2015-12-31 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -3 Days.
		$expiration = $ms->modify_expiration_date( 3, 'days', '-', strtotime( '2016-03-01 23:59:59' ) );
		$this->assertEquals( '2016-02-27 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -30 Days.
		$expiration = $ms->modify_expiration_date( 30, 'days', '-', strtotime( '2016-02-29 23:59:59' ) );
		$this->assertEquals( '2016-01-30 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -1 Month.
		$expiration = $ms->modify_expiration_date( 1, 'months', '-', strtotime( '2016-03-31 23:59:59' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -3 Months.
		$expiration = $ms->modify_expiration_date( 3, 'months', '-', strtotime( '2015-04-05 23:59:59' ) );
		$this->assertEquals( '2015-01-05 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -6 Months.
		$expiration = $ms->modify_expiration_date( 6, 'months', '-', strtotime( '2016-03-31 23:59:59' ) );
		$this->assertEquals( '2015-09-30 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -1 Year.
		$expiration = $ms->modify_expiration_date( 1, 'years', '-', strtotime( '2016-08-25 23:59:59' ) );
		$this->assertEquals( '2015-08-25 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -2 Years (from common year to leap year).
		$expiration = $ms->modify_expiration_date( 2, 'years', '-', strtotime( '2018-02-28 23:59:59' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -3 Years (from common year to common year).
		$expiration = $ms->modify_expiration_date( 3, 'years', '-', strtotime( '2018-02-28 23:59:59' ) );
		$this->assertEquals( '2015-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );
	}

	/**
	 * Test "pause membership" and "resume membership" features.
	 */
	public function testPauseResumeMembership() {
		$ms = Edr_Memberships::get_instance();
		
		// Initial state of the user's membership.
		$initial_user_membership = $ms->get_user_membership( $this->users['student1'] );

		// Timestamps.
		$today = time();
		$pause_ts = strtotime( '+ 10 days', $today );
		$today_ts = strtotime( '+ 16 days', $today );

		// Pause and resume the user's membership.
		$ms->pause_membership( $this->users['student1'], $pause_ts );
		$ms->resume_membership( $this->users['student1'], $today_ts );

		// New state of the user's membership.
		$user_membership = $ms->get_user_membership( $this->users['student1'] );

		// Now, the expiration date must move 6 days further.
		$new_expiration = strtotime( '+ 6 days', $initial_user_membership['expiration'] );
		$this->assertEquals( $new_expiration, $user_membership['expiration'] );
		$this->assertEquals( date( 'Y-m-d 23:59:59', $new_expiration ), date( 'Y-m-d H:i:s', $user_membership['expiration'] ) );
	}

	/**
	 * Pause user's entries with origin of "membership".
	 */
	public function testPauseMembershipEntries() {
		$entry = edr_get_entry();
		$entry->course_id = $this->courses[0];
		$entry->user_id = $this->users['student1'];
		$entry->payment_id = 0;
		$entry->entry_status = 'inprogress';
		$entry->entry_origin = 'membership';
		$entry->entry_date = date( 'Y-m-d H:i:s' );
		$entry->save();

		$ms = Edr_Memberships::get_instance();

		$ms->pause_membership_entries( $this->users['student1'] );

		$entry = edr_get_entry( $entry->ID );

		$this->assertEquals( 'paused', $entry->entry_status );
	}

	/**
	 * Function to process the expired memberships.
	 * This feature is invoked by CRON on production.
	 */
	public function testProcessExpiredMemberships() {
		$ms = Edr_Memberships::get_instance();

		$user1 = wp_insert_user( array(
			'user_login' => 'expired1',
			'user_pass'  => '123456',
			'role'       => 'student',
		) );

		$user2 = wp_insert_user( array(
			'user_login' => 'expired2',
			'user_pass'  => '123456',
			'role'       => 'student',
		) );

		$m1 = $this->addMembership( array(
			'price'      => 100,
			'period'     => 'years',
			'duration'   => 1,
			'categories' => array( $this->categories[0] ),
		) );

		$m2 = $this->addMembership( array(
			'price'      => 10,
			'period'     => 'months',
			'duration'   => 1,
			'categories' => array( $this->categories[1] ),
		) );

		$ms->setup_membership( $user1, $m1 );
		$ms->setup_membership( $user2, $m2 );

		$yesterday = strtotime( '- 1 days', strtotime( date( 'Y-m-d 23:59:59' ) ) );

		$um1 = $ms->get_user_membership( $user1 );
		$um2 = $ms->get_user_membership( $user2 );
		$um1['expiration'] = date( 'Y-m-d H:i:s', $yesterday );
		$um2['expiration'] = date( 'Y-m-d H:i:s', $yesterday );
		$ms->update_user_membership( $um1 );
		$ms->update_user_membership( $um2 );

		Edr_Memberships_Run::process_expired_memberships();
		
		$this->assertEquals( array(
			'ID'            => $um1['ID'],
			'user_id'       => $user1,
			'membership_id' => $m1,
			'status'        => 'expired',
			'expiration'    => $yesterday,
			'paused'        => 0,
		), $ms->get_user_membership( $user1 ) );

		$this->assertEquals( array(
			'ID'            => $um2['ID'],
			'user_id'       => $user2,
			'membership_id' => $m2,
			'status'        => 'expired',
			'expiration'    => $yesterday,
			'paused'        => 0,
		), $ms->get_user_membership( $user2 ) );
	}

	/**
	 * Test the membership expiration notification email.
	 */
	public function wp_mail_expiration_notification( $args ) {
		$this->assertEquals( array(
			'to' => 'student1@educatorplugin.com',
			'subject' => 'Your membership expires',
			'message' => 'Dear expired3,

Your membership 1 membership expires on ' . date( get_option( 'date_format' ), strtotime( '+ 5 days', strtotime( date( 'Y-m-d 23:59:59' ) ) ) ) . '.

Please renew your membership: ?edu-membership=93

Log in: http://example.org/wp-login.php

Best regards,
Administration',
		), array(
			'to' => $args['to'][0],
			'subject' => $args['subject'],
			'message' => $args['message'],
		) );

		return array(
			'to' => array(),
			'subject' => '',
			'message' => '',
			'headers' => '',
			'attachments' => array(),
		);
	}

	/**
	 * Test if plugin sends membership expiration notifications to students.
	 */
	public function testMembershipExpirationNotification() {
		add_filter( 'wp_mail', array( $this, 'wp_mail_expiration_notification' ) );

		$ms = Edr_Memberships::get_instance();

		$user = wp_insert_user( array(
			'user_login' => 'expired3',
			'user_email' => 'student1@educatorplugin.com',
			'user_pass'  => '123456',
			'role'       => 'student',
		) );

		$membership = $this->addMembership( array(
			'price'      => 100,
			'period'     => 'months',
			'duration'   => 1,
			'categories' => array( $this->categories[0] ),
		) );

		$ms->setup_membership( $user, $membership );

		$in5days = strtotime( '+ 5 days', strtotime( date( 'Y-m-d 23:59:59' ) ) );

		$ms->update_user_membership( $user, array( 'expiration' => date( 'Y-m-d H:i:s', $in5days ) ) );
		$_SERVER['SERVER_NAME'] = 'localhost';
		Edr_Memberships_Run::send_expiration_notifications();
	}
}