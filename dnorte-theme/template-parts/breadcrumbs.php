<?php
/**
 * Migas de pan visibles. Usa Seo\Breadcrumbs\BreadcrumbBuilder de dnorte-core (el
 * mismo origen de datos que ya alimenta el BreadcrumbList de Schema.org, para que el
 * HTML visible y el JSON-LD nunca puedan divergir) — degrada sin salida si el plugin
 * no está activo.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

if ( ! class_exists( 'DNorteCore\\Seo\\Breadcrumbs\\BreadcrumbBuilder' ) ) {
	return;
}

$items = ( new DNorteCore\Seo\Breadcrumbs\BreadcrumbBuilder( get_bloginfo( 'name' ), home_url( '/' ) ) )->build();

if ( count( $items ) < 2 ) {
	return;
}
?>
<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'dnorte-theme' ); ?>">
	<ol>
		<?php foreach ( $items as $index => $item ) : ?>
			<li>
				<?php if ( $index === count( $items ) - 1 ) : ?>
					<span aria-current="page"><?php echo esc_html( $item['name'] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['name'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
