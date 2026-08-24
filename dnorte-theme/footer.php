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
			<div class="site-footer__top">
				<div class="site-footer__brand">
					<?php if ( is_readable( DNORTE_THEME_DIR . '/assets/images/dnorte-logo.png' ) ) : ?>
						<img src="<?php echo esc_url( DNORTE_THEME_URI . '/assets/images/dnorte-logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="187" height="40" loading="lazy" />
					<?php else : ?>
						<p><strong><?php bloginfo( 'name' ); ?></strong></p>
					<?php endif; ?>
					<?php if ( get_bloginfo( 'description' ) !== '' ) : ?>
						<p><?php bloginfo( 'description' ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( has_nav_menu( 'footer_sites' ) ) : ?>
					<div>
						<h2 class="site-footer__heading"><?php esc_html_e( 'Nuestros sitios', 'dnorte-theme' ); ?></h2>
						<nav class="footer-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Nuestros sitios', 'dnorte-theme' ); ?>">
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer_sites',
									'container'      => false,
									'fallback_cb'    => false,
								)
							);
							?>
						</nav>
					</div>
				<?php endif; ?>

				<?php
				$socialLinks = array(
					'facebook'  => array( get_theme_mod( 'dnorte_social_facebook', '' ), __( 'Facebook', 'dnorte-theme' ), 'M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H8v3h3v7h3v-7h3l1-3h-4V9c0-.6.4-1 1-1Z' ),
					'x'         => array( get_theme_mod( 'dnorte_social_x', '' ), __( 'X (Twitter)', 'dnorte-theme' ), 'M4 4l7.5 9.5L4.5 20H7l5.8-5.8L17.5 20H20l-8-10.2L18.7 4H16.3l-5.3 5.3L6.5 4H4Z' ),
					'instagram' => array( get_theme_mod( 'dnorte_social_instagram', '' ), __( 'Instagram', 'dnorte-theme' ), 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 6.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5ZM16.5 6.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z' ),
				);
				$hasSocial   = false;
				foreach ( $socialLinks as $social ) {
					if ( is_string( $social[0] ) && trim( $social[0] ) !== '' ) {
						$hasSocial = true;
						break;
					}
				}
				?>
				<?php if ( $hasSocial ) : ?>
					<div>
						<h2 class="site-footer__heading"><?php esc_html_e( 'Síguenos', 'dnorte-theme' ); ?></h2>
						<div class="site-footer__social">
							<?php foreach ( $socialLinks as $social ) : ?>
								<?php if ( is_string( $social[0] ) && trim( $social[0] ) !== '' ) : ?>
									<a class="social-icon" href="<?php echo esc_url( $social[0] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social[1] ); ?>">
										<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?php echo esc_attr( $social[2] ); ?>" /></svg>
									</a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="site-footer__bottom">
				<nav class="legal-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Enlaces legales', 'dnorte-theme' ); ?>">
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
		</div>
	</footer>

<?php wp_footer(); ?>
</body>
</html>
