<?php
/**
 * Formulario de búsqueda — get_search_form() lo usa en vez del genérico de WordPress
 * core en cuanto existe en el tema. Sin JS propio: es un <form method="get"> normal
 * que envía a la home con ?s=..., WordPress lo enruta solo a search.php.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="dnorte-search-field">
		<?php esc_html_e( 'Buscar', 'dnorte-theme' ); ?>
	</label>
	<input
		type="search"
		id="dnorte-search-field"
		class="search-form__field"
		placeholder="<?php esc_attr_e( 'Buscar en Diario del Norte…', 'dnorte-theme' ); ?>"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		name="s"
	/>
	<button type="submit" class="search-form__submit">
		<span class="screen-reader-text"><?php esc_html_e( 'Buscar', 'dnorte-theme' ); ?></span>
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<circle cx="11" cy="11" r="7" />
			<path d="m21 21-4.3-4.3" stroke-linecap="round" />
		</svg>
	</button>
</form>
