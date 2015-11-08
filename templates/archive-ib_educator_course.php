<?php
/**
 * This template renders the courses archive page.
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
	do_action( 'ib_educator_before_main_loop', 'archive' );
?>

<header class="page-header">
	<h1 class="page-title">
		<?php ib_edu_page_title(); ?>
	</h1>
</header>

<?php while ( have_posts() ) : the_post(); ?>
<?php get_template_part( 'content', get_post_format() ); ?>
<?php endwhile; ?>

<?php
	/**
	 * Add HTML after output of educator's content.
	 */
	do_action( 'ib_educator_after_main_loop', 'archive' );
?>

<?php
	/**
	 * Add sidebar.
	 */
	do_action( 'ib_educator_sidebar' );
?>

<?php get_footer( 'ibeducator' ); ?>