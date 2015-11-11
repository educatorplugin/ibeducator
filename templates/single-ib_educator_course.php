<?php
/**
 * This template renders a single course page.
 * To override this template you should copy
 * this file to your theme's root directory.
 *
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<?php get_header( 'ibeducator' ); ?>

<?php
	/**
	 * Add HTML before output of educator's content.
	 */
	do_action( 'ib_educator_before_main_loop' );
?>

<?php while ( have_posts() ) : the_post(); ?>
<?php Edr_View::template_part( 'content', 'single-course' ); ?>
<?php endwhile; ?>

<?php
	/**
	 * Add HTML after output of educator's content.
	 */
	do_action( 'ib_educator_after_main_loop' );
?>

<?php
	/**
	 * Add sidebar.
	 */
	do_action( 'ib_educator_sidebar' );
?>

<?php get_footer( 'ibeducator' ); ?>