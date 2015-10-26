<?php

class IB_Educator_Test_Split_Shared_Term extends IB_Educator_Tests {
	protected $terms;
	protected $posts = array();

	public function setUp() {
		global $wpdb;
		parent::setUp();

		register_taxonomy( 'edu_test_tax_1', 'post' );
		register_taxonomy( 'edu_test_tax_2', 'post' );

		$t1 = wp_insert_term( 'Edu', 'edu_test_tax_1' );
		$t2 = wp_insert_term( 'Edu', 'edu_test_tax_2' );
		$t3 = wp_insert_term( 'Edu', 'ib_educator_category' );
		$t4 = wp_insert_term( 'Mathematics', 'ib_educator_category' ); // Arbitrary term.

		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'term_id' => $t1['term_id'] ),
			array( 'term_taxonomy_id' => $t2['term_taxonomy_id'] ),
			array( '%d' ),
			array( '%d' )
		);

		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'term_id' => $t1['term_id'] ),
			array( 'term_taxonomy_id' => $t3['term_taxonomy_id'] ),
			array( '%d' ),
			array( '%d' )
		);

		$t2['term_id']  = $t1['term_id'];
		$t3['term_id']  = $t1['term_id'];

		// Create membership.
		$post_id = wp_insert_post( array(
			'post_type'   => 'ib_edu_membership',
			'post_name'   => 'edu-membership-1',
			'post_title'  => 'edu membership 1',
			'post_status' => 'publish',
		) );
		$ms = Edr_Memberships::get_instance();
		$meta = $ms->get_membership_meta();
		$meta['price'] = '10.05';
		$meta['period'] = '';
		$meta['duration'] = '';
		$meta['categories'] = array( $t3['term_id'], $t4['term_id'] );
		update_post_meta( $post_id, '_ib_educator_membership', $meta );

		$this->posts[] = $post_id;

		$this->terms = array(
			'term1' => $t1,
			'term2' => $t2,
			'term3' => $t3,
			'term4' => $t4,
		);
	}

	public function testSplitSharedTerm() {
		$ms = Edr_Memberships::get_instance();
		$meta = $ms->get_membership_meta( $this->posts[0] );

		// Validate initial condition.
		$this->assertEquals( $this->terms['term3']['term_id'], $meta['categories'][0] );

		// Split shared term.
		$new_term = wp_update_term( $this->terms['term3']['term_id'], 'ib_educator_category', array(
			'name' => 'Edu',
		) );

		$meta = $ms->get_membership_meta( $this->posts[0] );

		// The term_id stored in the membership's categories setting should be updated.
		$this->assertEquals( array( $new_term['term_id'], $this->terms['term4']['term_id'] ), $meta['categories'] );
	}

	public function testUpdateSplittedTerms() {
		// Remove split_shared_term to prevent automatic categories update for memberships.
		remove_action( 'split_shared_term', array( 'IB_Educator_Main', 'split_shared_term' ) );

		// Split shared term.
		$new_term = wp_update_term( $this->terms['term3']['term_id'], 'ib_educator_category', array(
			'name' => 'Edu',
		) );

		// Update shared terms for use in memberships.
		$install = new Edr_Install();
		$install->update_1_4_4();

		$ms = Edr_Memberships::get_instance();
		$meta = $ms->get_membership_meta( $this->posts[0] );

		// The term_id stored in the membership's categories setting should be updated.
		$this->assertEquals( array( $new_term['term_id'], $this->terms['term4']['term_id'] ), $meta['categories'] );
	}
}