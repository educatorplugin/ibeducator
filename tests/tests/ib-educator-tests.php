<?php
/**
 * General methods(api) for various test cases.
 * Can be extended by test case classes.
 */
class IB_Educator_Tests extends WP_UnitTestCase {
	protected $courses = array();
	protected $lessons = array();
	protected $payments = array();
	protected $entries = array();
	protected $users = array();
	protected $categories = array();
	protected $admin_id;

	/**
	 * Setup initial test data.
	 * e.g., courses, payments, entries.
	 * Current user: $this->admin_id
	 * Lessons:
	 *  - $this->lessons[0] => lecturer: $this->users['lecturer1'], course: $this->courses[0].
	 *  - $this->lessons[2] => lecturer: $this->users['lecturer1'], course: $this->courses[2].
	 *  - $this->lessons[3] => lecturer: $this->admin_id, course: $this->courses[3].
	 * Entries:
	 *  - $this->entries['current'] => student: $this->admin_id, course: $this->courses[2], status: inprogress.
	 */
	public function basicSetUp() {
		$this->api = IB_Educator::get_instance();

		// Add users.
		// lecturer1:
		$this->users['lecturer1'] = wp_insert_user( array(
			'user_login' => 'lecturer1',
			'user_pass'  => '123456',
			'role'       => 'lecturer',
		) );
		// student1:
		$this->users['student1'] = wp_insert_user( array(
			'user_login' => 'student1',
			'user_pass'  => '123456',
			'role'       => 'student',
		) );

		// Set current user:
		global $current_user;
		$current_user = new WP_User( 1 );
		$current_user->set_role( 'administrator' );
		$this->admin_id = $current_user->ID;

		// Add categories.
		$this->categories[] = $this->addCategory( 'Category 1' );
		$this->categories[] = $this->addCategory( 'Category 2' );
		$this->categories[] = $this->addCategory( 'Category 3' );

		// Add courses.
		$this->courses[] = $this->addCourse( 'test-course-1', 'test course 1', $this->users['lecturer1'], 199.99 );
		$this->courses[] = $this->addCourse( 'test-course-2', 'test course 2', $this->users['lecturer1'], 287.83 );
		$this->courses[] = $this->addCourse( 'test-course-3', 'test course 3', $this->users['lecturer1'], 329.83 );
		$this->courses[] = $this->addCourse( 'test-course-4', 'test course 4', $this->admin_id, 329.83 );

		// Add lessons.
		// Lesson for course 1:
		$this->lessons[] = $this->addLesson( array(
			'course_id' => $this->courses[0],
			'author_id' => $this->users['lecturer1'],
			'slug'      => 'course-1-lesson-1',
			'title'     => 'course 1 lesson 1',
		) );
		// Lesson for course 2:
		$this->lessons[] = $this->addLesson( array(
			'course_id' => $this->courses[1],
			'author_id' => $this->users['lecturer1'],
			'slug'      => 'course-2-lesson-1',
			'title'     => 'course 2 lesson 1',
		) );
		// Lesson for course 3:
		$this->lessons[] = $this->addLesson( array(
			'course_id' => $this->courses[2],
			'author_id' => $this->users['lecturer1'],
			'slug'      => 'course-3-lesson-1',
			'title'     => 'course 3 lesson 1',
		) );
		// Lesson for course 4:
		$this->lessons[] = $this->addLesson( array(
			'course_id' => $this->courses[3],
			'author_id' => $this->admin_id,
			'slug'      => 'course-4-lesson-1',
			'title'     => 'course 4 lesson 1',
		) );

		// Add payments.
		// Payment from student1 for course 1 (complete):
		$this->payments['complete'] = $this->addPayment( array(
			'payment_type'   => 'course',
			'payment_status' => 'complete',
			'course_id'      => $this->courses[0],
			'user_id'        => $this->users['student1'],
		) );
		// Payment from student1 for course 2 (pending):
		$this->payments['pending'] = $this->addPayment( array(
			'payment_type'   => 'course',
			'payment_status' => 'pending',
			'course_id'      => $this->courses[1],
			'user_id'        => $this->users['student1'],
		) );
		// Payment from current user for course 3 (complete):
		$this->payments['current'] = $this->addPayment( array(
			'payment_type'   => 'course',
			'payment_status' => 'complete',
			'course_id'      => $this->courses[2],
			'user_id'        => $this->admin_id,
		) );

		// Add entries.
		// Entry for student1 for course 1:
		$this->entries['inprogress'] = $this->addEntry( array(
			'payment_id'   => $this->payments['complete'],
			'course_id'    => $this->courses[0],
			'entry_status' => 'inprogress',
		) );
		// Entry for current user for course 3:
		$this->entries['current'] = $this->addEntry( array(
			'payment_id'   => $this->payments['current'],
			'course_id'    => $this->courses[2],
			'entry_status' => 'inprogress',
		) );
	}

	/**
	 * Add course.
	 */
	public function addCourse( $slug, $title, $author_id, $price ) {
		$course_data = array(
			'post_type'   => 'ib_educator_course',
			'post_author' => $author_id,
			'post_name'   => $slug,
			'post_title'  => $title,
			'post_status' => 'publish',
		);

		$course_id = wp_insert_post( $course_data );

		$course_meta = array(
			'_ibedu_price' => $price,
		);

		foreach ( $course_meta as $key => $value ) {
			update_post_meta( $course_id, $key, $value );
		}

		return $course_id;
	}

	/**
	 * Add lesson.
	 */
	public function addLesson( $input ) {
		$lesson_data = array(
			'post_type'   => 'ib_educator_lesson',
			'post_author' => $input['author_id'],
			'post_name'   => $input['slug'],
			'post_title'  => $input['title'],
			'post_status' => 'publish',
		);

		$lesson_id = wp_insert_post( $lesson_data );

		$lesson_meta = array(
			'_ibedu_course' => $input['course_id'],
		);

		foreach ( $lesson_meta as $key => $value ) {
			update_post_meta( $lesson_id, $key, $value );
		}

		return $lesson_id;
	}

	/**
	 * Add course category.
	 */
	public function addCategory( $name ) {
		$term = wp_insert_term( $name, 'ib_educator_category' );
		return $term['term_id'];
	}

	/**
	 * Add payment.
	 */
	public function addPayment( $input ) {
		$data = array(
			'user_id'         => $input['user_id'],
			'payment_type'    => $input['payment_type'],
			'payment_gateway' => 'paypal',
			'payment_status'  => $input['payment_status'],
			'currency'        => 'USD',
		);

		if ( 'course' == $input['payment_type'] ) {
			$data['course_id'] = $input['course_id'];
			$data['amount'] = ib_edu_get_course_price( $input['course_id'] );
		} elseif ( 'membership' == $input['payment_type'] ) {
			$data['object_id'] = $input['object_id'];
			$ms = Edr_Memberships::get_instance();
			$data['amount'] = $ms->get_price( $input['object_id'] );
		}

		$payment = $this->api->add_payment( $data );
		return $payment->ID;
	}

	/**
	 * Add course entry.
	 */
	public function addEntry( $data ) {
		$payment = edr_get_payment( $data['payment_id'] );
		$entry = edr_get_entry();
		$entry->course_id = $data['course_id'];
		$entry->user_id = $payment->user_id;
		$entry->payment_id = $payment->ID;
		$entry->entry_status = $data['entry_status'];
		$entry->entry_date = date( 'Y-m-d H:i:s' );
		$entry->save();
		return $entry->ID;
	}
}