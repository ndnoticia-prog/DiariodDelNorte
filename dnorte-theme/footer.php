<?php
/**
 * Pie del sitio.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

?>
	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-footer__inner">
			<nav class="footer-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Menú de pie de página', 'dnorte-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
			<p class="site-info">
				&copy; <?php echo esc_html( (string) gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
			</p>
		</div>
	</footer>

<?php wp_footer(); ?>
</body>
</html>
