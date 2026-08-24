<?php
/**
 * Pie del sitio — cuatro columnas (Secciones/Institucional/Legal/Redes
 * Sociales) más la marca. Cada columna es un <details> nativo: en escritorio
 * el CSS lo fuerza siempre abierto (sin JS, sin duplicar markup para
 * móvil/escritorio); en móvil el navegador ya sabe plegar/desplegar un
 * <details> — así "acordeón en móvil" no necesita ninguna línea de JS propia.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

/**
 * "Secciones" enlaza directo a las categorías reales de
 * DNorteTheme\Content\DefaultContentSeeder (no un menú aparte que alguien
 * tendría que mantener sincronizado a mano) — si alguna todavía no existe
 * (seeder no corrido, o la borraron), esa fila simplemente no se imprime.
 *
 * @var list<string> $footerSectionSlugs
 */
$footerSectionSlugs = array( 'la-guajira', 'politica', 'judiciales', 'caribe', 'nacion', 'mundo', 'opinion' );
?>
	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-footer__inner">
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

			<div class="site-footer__columns">
				<details class="footer-column" open>
					<summary class="site-footer__heading"><?php esc_html_e( 'Secciones', 'dnorte-theme' ); ?></summary>
					<ul>
						<?php foreach ( $footerSectionSlugs as $slug ) : ?>
							<?php $categoryTerm = get_term_by( 'slug', $slug, 'category' ); ?>
							<?php if ( $categoryTerm instanceof WP_Term ) : ?>
								<li><a href="<?php echo esc_url( (string) get_category_link( $categoryTerm ) ); ?>"><?php echo esc_html( $categoryTerm->name ); ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</details>

				<?php if ( has_nav_menu( 'footer_institutional' ) ) : ?>
					<details class="footer-column" open>
						<summary class="site-footer__heading"><?php esc_html_e( 'Institucional', 'dnorte-theme' ); ?></summary>
						<nav class="footer-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Institucional', 'dnorte-theme' ); ?>">
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer_institutional',
									'container'      => false,
									'fallback_cb'    => false,
								)
							);
							?>
						</nav>
					</details>
				<?php endif; ?>

				<?php if ( has_nav_menu( 'footer' ) ) : ?>
					<details class="footer-column" open>
						<summary class="site-footer__heading"><?php esc_html_e( 'Legal', 'dnorte-theme' ); ?></summary>
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
					</details>
				<?php endif; ?>

				<?php
				$socialLinks = array(
					'facebook'  => array( get_theme_mod( 'dnorte_social_facebook', '' ), __( 'Facebook', 'dnorte-theme' ), 'M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H8v3h3v7h3v-7h3l1-3h-4V9c0-.6.4-1 1-1Z' ),
					'x'         => array( get_theme_mod( 'dnorte_social_x', '' ), __( 'X (Twitter)', 'dnorte-theme' ), 'M4 4l7.5 9.5L4.5 20H7l5.8-5.8L17.5 20H20l-8-10.2L18.7 4H16.3l-5.3 5.3L6.5 4H4Z' ),
					'instagram' => array( get_theme_mod( 'dnorte_social_instagram', '' ), __( 'Instagram', 'dnorte-theme' ), 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 6.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5ZM16.5 6.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z' ),
					'youtube'   => array( get_theme_mod( 'dnorte_social_youtube', '' ), __( 'YouTube', 'dnorte-theme' ), 'M21.6 7.2s-.2-1.5-.8-2.1c-.8-.8-1.7-.8-2.1-.9C15.9 4 12 4 12 4h0s-3.9 0-6.7.2c-.4.1-1.3.1-2.1.9-.6.6-.8 2.1-.8 2.1S2.2 9 2.2 10.7v1.6C2.2 14 2.4 15.8 2.4 15.8s.2 1.5.8 2.1c.8.8 1.9.8 2.4.9 1.7.2 7.4.2 7.4.2s3.9 0 6.7-.2c.4-.1 1.3-.1 2.1-.9.6-.6.8-2.1.8-2.1s.2-1.8.2-3.5v-1.6c0-1.7-.2-3.5-.2-3.5ZM9.9 14.6V8.9l5.4 2.9-5.4 2.8Z' ),
					'tiktok'    => array( get_theme_mod( 'dnorte_social_tiktok', '' ), __( 'TikTok', 'dnorte-theme' ), 'M16.6 5.8a4.3 4.3 0 0 1-3-3.8h-3v13a2.6 2.6 0 1 1-2-2.5v-3.1a5.6 5.6 0 1 0 5 5.6V9.1a7.3 7.3 0 0 0 3.9 1.1V7.2a4.3 4.3 0 0 1-.9-1.4Z' ),
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
					<details class="footer-column" open>
						<summary class="site-footer__heading"><?php esc_html_e( 'Redes sociales', 'dnorte-theme' ); ?></summary>
						<div class="site-footer__social">
							<?php foreach ( $socialLinks as $social ) : ?>
								<?php if ( is_string( $social[0] ) && trim( $social[0] ) !== '' ) : ?>
									<a class="social-icon" href="<?php echo esc_url( $social[0] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social[1] ); ?>">
										<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?php echo esc_attr( $social[2] ); ?>" /></svg>
									</a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</details>
				<?php endif; ?>
			</div>

			<div class="site-footer__bottom">
				<p class="site-info">
					&copy; <?php echo esc_html( (string) gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
				</p>
			</div>
		</div>
	</footer>

<?php wp_footer(); ?>
</body>
</html>
