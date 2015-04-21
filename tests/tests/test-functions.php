<?php

class IB_Educator_Test_Functions extends IB_Educator_Tests {
	public function setUp() {
		parent::setUp();
		$this->basicSetUp();
	}

	public function testStudentCanStudy() {
		// Current user cannot study course 1.
		$can_study = ib_edu_student_can_study( $this->lessons[0] );
		$this->assertEquals( false, $can_study );

		// Current user can study course 3.
		$can_study = ib_edu_student_can_study( $this->lessons[2] );
		$this->assertEquals( true, $can_study );
	}
}