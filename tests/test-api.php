<?php
/**
 * Unit tests for the API functions.
 * Tests the IB_Educator class.
 */
class IB_Educator_Test_API extends IB_Educator_Tests {
	/**
	 * Setup tests.
	 */
	public function setUp() {
		parent::setUp();
		$this->basicSetUp();
	}

	/**
	 * Test add_payment method.
	 */
	public function testAddPayment() {
		$this->assertTrue( absint( $this->payments['complete'] ) > 0 );
	}

	/**
	 * Test IB_Educator_Entry->save.
	 */
	public function testAddEntry() {
		$this->assertTrue( absint( $this->entries['inprogress'] ) > 0 );
	}

	/**
	 * Test get_access_status method.
	 */
	public function testGetAccessStatus() {
		// Check existing course entry.
		$access_status = $this->api->get_access_status( $this->courses[0], $this->users['student1'] );
		$this->assertEquals( 'inprogress', $access_status );

		// Check non-existing course entry.
		$access_status = $this->api->get_access_status( 9999, $this->users['student1'] );
		$this->assertEquals( 'forbidden', $access_status );
	}

	/**
	 * Test get_entry method.
	 */
	public function testGetEntry() {
		// by payment id.
		$entry = $this->api->get_entry( array( 'payment_id' => $this->payments['complete'] ) );
		$this->assertNotFalse( $entry );

		// by course_id.
		$entry = $this->api->get_entry( array( 'course_id' => $this->courses[0] ) );
		$this->assertNotFalse( $entry );

		// by course_id.
		$entry = $this->api->get_entry( array( 'user_id' => $this->users['student1'] ) );
		$this->assertNotFalse( $entry );

		// by course_id.
		$entry = $this->api->get_entry( array( 'entry_status' => 'inprogress' ) );
		$this->assertNotFalse( $entry );

		// not found.
		$entry = $this->api->get_entry( array( 'entry_status' => 'non-existing-status' ) );
		$this->assertFalse( $entry );
	}

	/**
	 * Test get_entries method.
	 */
	public function testGetEntries() {
		// No pagination.
		$entries = $this->api->get_entries( array(
			'entry_id'     => $this->entries['inprogress'],
			'course_id'    => $this->courses[0],
			'user_id'      => $this->users['student1'],
			'entry_status' => 'inprogress',
		) );

		$this->assertNotFalse( $entries );

		// Pagination.
		$entries = $this->api->get_entries( array(
			'entry_id' => $this->entries['inprogress'],
			'page'     => 1,
			'per_page' => 1,
		) );

		$this->assertArrayHasKey( 'num_pages', $entries );
		$this->assertArrayHasKey( 'num_items', $entries );
		$this->assertArrayHasKey( 'rows', $entries );
		$this->assertNotEmpty( $entries['rows'] );

		// Not found.
		$entries = $this->api->get_entries( array(
			'entry_id' => 0,
		) );
		$this->assertEquals( array(), $entries );
	}

	/**
	 * Test get_student_courses method.
	 */
	public function testGetStudentCourses() {
		$courses = $this->api->get_student_courses( $this->users['student1'] );

		$this->assertNotEmpty( $courses['courses'] );
		$this->assertContainsOnlyInstancesOf( 'WP_Post', $courses['courses'] );
		$this->assertNotEmpty( $courses['entries'] );
		$this->assertContainsOnlyInstancesOf( 'IB_Educator_Entry', $courses['entries'] );
		$this->assertArrayHasKey( 'statuses', $courses );
	}

	/**
	 * Test get_pending_courses method.
	 */
	public function testGetPendingCourses() {
		// existing courses.
		$courses = $this->api->get_pending_courses( $this->users['student1'] );
		$this->assertNotEmpty( $courses );
		$this->assertContainsOnlyInstancesOf( 'WP_Post', $courses );

		// not found.
		$not_found = $this->api->get_pending_courses( 0 );
		$this->assertFalse( $not_found );
	}

	/**
	 * Test get_lessons method.
	 */
	public function testGetLessons() {
		$lessons = $this->api->get_lessons( $this->courses[0] );

		$this->assertInstanceOf( 'WP_Query', $lessons );
		$this->assertEquals( 1, $lessons->found_posts );
	}

	/**
	 * Test get_payments method.
	 */
	public function testGetPayments() {
		$args = array(
			'payment_id'     => $this->payments['complete'],
			'user_id'        => $this->users['student1'],
			'course_id'      => $this->courses[0],
			'payment_type'   => 'course',
			'payment_status' => array( 'complete', 'pending' )
		);

		// No pagination.
		$payments = $this->api->get_payments( $args );

		$this->assertNotEmpty( $payments );
		$this->assertContainsOnlyInstancesOf( 'IB_Educator_Payment', $payments );

		// Pagination.
		$args['page'] = 1;
		$args['per_page'] = 1;
		$payments = $this->api->get_payments( $args );

		$this->assertArrayHasKey( 'num_pages', $payments );
		$this->assertArrayHasKey( 'num_items', $payments );
		$this->assertArrayHasKey( 'rows', $payments );
		$this->assertNotEmpty( $payments['rows'] );
		$this->assertContainsOnlyInstancesOf( 'IB_Educator_Payment', $payments['rows'] );
	}

	/**
	 * Test get_lecturer_courses method.
	 */
	public function testGetLecturerCourses() {
		$courses = $this->api->get_lecturer_courses( $this->users['lecturer1'] );

		$this->assertContains( $this->courses[0], $courses );
		$this->assertContains( $this->courses[1], $courses );
	}
}