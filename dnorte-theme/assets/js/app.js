import '../scss/app.scss';

// Punto de entrada de JS del front-end. El anti-parpadeo de modo oscuro vive
// inline en header.php (debe ejecutarse antes del primer paint, así que no
// puede depender de este bundle, que carga después) — aquí solo va la
// interacción posterior a la carga: el botón de alternar tema y el menú móvil.

(function () {
	'use strict';

	function initThemeToggle() {
		var toggle = document.querySelector('.theme-toggle');

		if (!toggle) {
			return;
		}

		// El script anti-parpadeo del <head> ya puso data-theme antes de este punto;
		// sincroniza el aria-label (el HTML servido por PHP no puede saber de
		// antemano qué tema eligió el navegador) con el estado real.
		toggle.setAttribute(
			'aria-label',
			document.documentElement.getAttribute('data-theme') === 'dark'
				? 'Cambiar a modo claro'
				: 'Cambiar a modo oscuro'
		);

		toggle.addEventListener('click', function () {
			var root = document.documentElement;
			var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

			root.setAttribute('data-theme', next);
			localStorage.setItem('dnorte-theme', next);
			toggle.setAttribute(
				'aria-label',
				next === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'
			);
		});
	}

	function initNavToggle() {
		var toggle = document.querySelector('.nav-toggle');
		var nav = document.getElementById('site-navigation');

		if (!toggle || !nav) {
			return;
		}

		toggle.addEventListener('click', function () {
			var isOpen = nav.classList.toggle('is-open');

			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initThemeToggle();
			initNavToggle();
		});
	} else {
		initThemeToggle();
		initNavToggle();
	}
})();
