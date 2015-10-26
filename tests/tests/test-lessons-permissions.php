<?php

class IB_Educator_Test_Lessons_Permissions extends IB_Educator_Tests {
	protected $lesson3;
	protected $open_lesson;

	/**
	 * Setup the tests object.
	 */
	public function setUp() {
		parent::setUp();
		$this->basicSetUp();

		// Lesson with more tag only.
		$lesson1 = get_post( $this->lessons[0] );
		$lesson1->post_excerpt = '';
		$lesson1->post_content = <<<EOT
This text can be visible to anyone.<!--more-->
This text must be visible to the students who registered for this course.
EOT;
		wp_update_post( $lesson1 );

		// Lesson without more tag and excerpt.
		$lesson2 = get_post( $this->lessons[1] );
		$lesson2->post_excerpt = '';
		$lesson2->post_content = <<<EOT
This text must be visible to the students who registered for this course.
EOT;
		wp_update_post( $lesson2 );

		// Lesson with excerpt only.
		$this->lesson3 = $this->addLesson( array(
			'course_id' => $this->courses[0],
			'author_id' => $this->users['lecturer1'],
			'slug'      => 'course-1-lesson-3',
			'title'     => 'course 1 lesson 3',
		) );
		$lesson3 = get_post( $this->lesson3 );
		$lesson3->post_excerpt = 'This excerpt is visible to everyone.';
		$lesson3->post_content = <<<EOT
This text must be visible to the students who registered for this course.
EOT;
		wp_update_post( $lesson3 );

		$this->open_lesson = $this->addLesson( array(
			'course_id' => $this->courses[0],
			'author_id' => $this->users['lecturer1'],
			'slug'      => 'course-1-open-lesson',
			'title'     => 'course 1 open lesson',
		) );

		$open_lesson = get_post( $this->open_lesson );
		$open_lesson->post_excerpt = 'Post excerpt.';
		$open_lesson->post_content = 'Hidden content.';
		wp_update_post( $open_lesson );
	}

	/**
	 * Test lesson permissions (more tag, no excerpt).
	 */
	public function testLessonContentPermissions1() {
		global $post, $more;
		$tmp_more = $more;
		$post = get_post( $this->lessons[0] );
		setup_postdata( $post );

		// Test with more set to 0
		$more = 0;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( "<p>This text can be visible to anyone. <a href=\"http://example.org/?lesson=course-1-lesson-1#more-$post->ID\" class=\"more-link\">(more&hellip;)</a></p>\n", $the_content );

		// Test with more set to 1.
		$more = 1;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( "<p>This text can be visible to anyone.</p>", $the_content );

		// Test the feed content.
		$this->assertEquals( '', get_the_content_feed( 'rss2' ) );

		// Test RSS excerpt.
		ob_start();
		the_excerpt_rss();
		$rss_excerpt = ob_get_clean();
		$this->assertEquals( 'This text can be visible to anyone.', $rss_excerpt );

		// Reset post data.
		wp_reset_postdata();
		$more = $tmp_more;
	}

	/**
	 * Test lesson permissions (no more tag, no excerpt).
	 */
	public function testLessonContentPermissions2() {
		global $post, $more;
		$tmp_more = $more;
		$post = get_post( $this->lessons[1] );
		setup_postdata( $post );

		// Test with more set to 0
		$more = 0;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( '', $the_content );

		// Test with more set to 1.
		$more = 1;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( '', $the_content );

		// Test the_excerpt().
		$more = 0;
		ob_start();
		the_excerpt();
		$the_excerpt = ob_get_clean();
		$this->assertEquals( '', $the_excerpt );

		// Test get_the_excerpt().
		$more = 1;
		$the_excerpt = get_the_excerpt();
		$this->assertEquals( '', $the_excerpt );

		// Test RSS excerpt.
		ob_start();
		the_excerpt_rss();
		$rss_excerpt = ob_get_clean();
		$this->assertEquals( '', $rss_excerpt );

		// Reset post data.
		wp_reset_postdata();
		$more = $tmp_more;
	}

	/**
	 * Test lesson permissions (no more tag, with excerpt).
	 */
	public function testLessonContentPermissions3() {
		global $post, $more;
		$tmp_more = $more;
		$post = get_post( $this->lesson3 );
		setup_postdata( $post );

		// Test with more set to 0
		$more = 0;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( 'This excerpt is visible to everyone.', $the_content );

		// Test with more set to 1.
		$more = 1;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( 'This excerpt is visible to everyone.', $the_content );

		// Test RSS excerpt.
		ob_start();
		the_excerpt_rss();
		$rss_excerpt = ob_get_clean();
		$this->assertEquals( 'This excerpt is visible to everyone.', $rss_excerpt );

		// Reset post data.
		wp_reset_postdata();
		$more = $tmp_more;
	}

	/**
	 * Check if rss_use_excerpt returns 1 for lessons.
	 * to make sure that lessons display excerpts instead of content in RSS.
	 */
	public function testUseRSSExcerpt() {
		global $post;
		$post = get_post( $this->lesson3 );
		setup_postdata( $post );
		$this->assertEquals( 1, get_option( 'rss_use_excerpt' ) );
		wp_reset_postdata();
	}

	/**
	 * Make sure no content is returned for lessons in the feeds.
	 */
	public function testLessonFeedContentPermissions() {
		global $post;
		$post = get_post( $this->lessons[1] );
		setup_postdata( $post );

		// Check rss2 feed.
		$the_content = get_the_content_feed( 'rss2' );
		$this->assertEquals( '', $the_content );

		// Check atom feed.
		$the_content = get_the_content_feed( 'atom' );
		$this->assertEquals( '', $the_content );

		// Check rss feed.
		$the_content = get_the_content_feed( 'rss' );
		$this->assertEquals( '', $the_content );

		// Check rdf feed.
		$the_content = get_the_content_feed( 'rdf' );
		$this->assertEquals( '', $the_content );

		wp_reset_postdata();
	}

	/**
	 * Test lesson registration option (registered and public).
	 */
	public function testLessonRegistrationOption() {
		global $post;
		$post = get_post( $this->open_lesson );
		setup_postdata( $post );

		// lesson is closed, return excerpt.
		$more = 1;
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( 'Post excerpt.', $the_content );

		// lesson is open to public, return content.
		update_post_meta( $this->open_lesson, '_ib_educator_access', 'public' );
		ob_start();
		the_content();
		$the_content = ob_get_clean();
		$this->assertEquals( "<p>Hidden content.</p>\n", $the_content );

		wp_reset_postdata();
	}

	/**
	 * Test if lesson comments aren't retrieved for unregistered users.
	 * Simulate main query.
	 */
	public function testLessonCommentsQueryUnregistered() {
		global $wp_the_query;
		$tmp = $wp_the_query;

		// Add a comment.
		wp_insert_comment( array(
			'comment_post_ID' => $this->lessons[0],
			'comment_author' => 'admin',
			'comment_author_email' => 'admin@example.com',
			'comment_approved' => 1,
			'comment_date' => current_time( 'mysql' ),
			'comment_content' => 'lesson comment content',
			'comment_author_IP' => '127.0.0.1',
			'comment_type' => '',
		) );

		Edr_Post_Types::clear_current_user_courses();

		$wp_the_query = new WP_Query();
		$wp_the_query->query( array(
			'feed'      => 'rss2',
			'post_type' => 'ib_educator_lesson',
			'name'      => 'course-1-lesson-1',
		) );

		$this->assertEquals( 0, $wp_the_query->comment_count );

		$wp_the_query = $tmp;
	}

	/**
	 * Test if lesson comments are retrieved for registered users.
	 */
	public function testLessonCommentsQueryRegistered() {
		global $wp_the_query;
		$tmp = $wp_the_query;

		// Add a comment.
		wp_insert_comment( array(
			'comment_post_ID' => $this->lessons[2],
			'comment_author' => 'admin',
			'comment_author_email' => 'admin@example.com',
			'comment_approved' => 1,
			'comment_date' => current_time( 'mysql' ),
			'comment_content' => 'lesson comment content',
			'comment_author_IP' => '127.0.0.1',
			'comment_type' => '',
		) );

		Edr_Post_Types::clear_current_user_courses();

		$wp_the_query = new WP_Query();
		$wp_the_query->query( array(
			'feed'      => 'rss2',
			'post_type' => 'ib_educator_lesson',
			'name'      => 'course-3-lesson-1',
		) );

		$this->assertEquals( 1, $wp_the_query->comment_count );

		$wp_the_query = $tmp;
	}

	public function testGetLessonComments() {
		// Add a comment.
		wp_insert_comment( array(
			'comment_post_ID' => $this->lessons[2],
			'comment_author' => 'admin',
			'comment_author_email' => 'admin@example.com',
			'comment_approved' => 1,
			'comment_date' => current_time( 'mysql' ),
			'comment_content' => 'lesson comment content',
			'comment_author_IP' => '127.0.0.1',
			'comment_type' => '',
		) );

		$comments = get_comments( array(
			'status'      => 'approve',
			'post_status' => 'publish',
		) );

		$this->assertEquals( 0, count($comments) );
	}
}