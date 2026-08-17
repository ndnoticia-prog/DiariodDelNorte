<?php
/**
 * Plantilla de fallback (jerarquía de plantillas de WordPress). Cubre cualquier
 * contexto sin una plantilla más específica.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

get_header();
?>

<main id="contenido-principal" class="site-main" role="main">
	<?php if (have_posts()) : ?>
		<?php
		while (have_posts()) :
			the_post();
			?>
			<article <?php post_class(); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php the_excerpt(); ?>
			</article>
			<?php
		endwhile;

		the_posts_pagination();
	else :
		?>
		<p><?php esc_html_e('No se encontró contenido.', 'dnorte-theme'); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
