import '../scss/app.scss';

// Punto de entrada de JS del front-end. El anti-parpadeo de modo oscuro vive
// inline en header.php (debe ejecutarse antes del primer paint, así que no
// puede depender de este bundle, que carga después) — aquí va el resto de la
// interacción posterior a la carga: tema oscuro, menú móvil, buscador
// colapsable, el filtro de "Lo más leído", el formulario del newsletter y
// "Cargar más".
//
// Todo aquí es progresivo: sin JS, el menú queda desplegado siempre, el
// buscador visible siempre, "Lo más leído" muestra solo la pestaña "24
// horas" (la primera lista sin [hidden] en el HTML), el newsletter hace un
// POST normal de formulario, y el botón "Cargar más" simplemente no
// responde — nada se rompe, solo se pierde la interacción de un clic.

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

	function initSearchToggle() {
		var toggle = document.querySelector('.search-toggle');
		var panel = document.querySelector('.site-header__search');

		if (!toggle || !panel) {
			return;
		}

		toggle.addEventListener('click', function () {
			var isOpen = panel.classList.toggle('is-open');

			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

			if (isOpen) {
				var field = panel.querySelector('.search-form__field');

				if (field) {
					field.focus();
				}
			}
		});
	}

	// "Más" en la tira horizontal de móvil (mobile-quick-nav) no tiene destino
	// propio (no es una categoría real, ver DefaultContentSeeder) — en vez de
	// dejar que su href="#" no haga nada, abre el menú completo (☰), que sí
	// trae sus nueve subcategorías.
	function initMobileQuickNavMore() {
		var quickNav = document.querySelector('.mobile-quick-nav');
		var navToggle = document.querySelector('.nav-toggle');
		var nav = document.getElementById('site-navigation');

		if (!quickNav || !navToggle || !nav) {
			return;
		}

		var moreLink = quickNav.querySelector('.menu-item-has-children > a');

		if (!moreLink) {
			return;
		}

		moreLink.addEventListener('click', function (event) {
			event.preventDefault();
			nav.classList.add('is-open');
			navToggle.setAttribute('aria-expanded', 'true');
			navToggle.scrollIntoView({ block: 'start', behavior: 'smooth' });
		});
	}

	// Las tres listas (24h/7d/30d) ya vienen en el HTML (most-read.php) — el
	// filtro solo muestra/oculta con [hidden], sin volver a pedir nada al
	// servidor.
	function initMostReadFilter() {
		var tabsContainer = document.querySelector('[data-most-read-tabs]');

		if (!tabsContainer) {
			return;
		}

		var tabs = Array.prototype.slice.call(tabsContainer.querySelectorAll('[data-most-read-tab]'));
		var panels = Array.prototype.slice.call(document.querySelectorAll('[data-most-read-panel]'));

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				var target = tab.getAttribute('data-most-read-tab');

				tabs.forEach(function (otherTab) {
					var isActive = otherTab === tab;

					otherTab.classList.toggle('is-active', isActive);
					otherTab.setAttribute('aria-selected', isActive ? 'true' : 'false');
				});

				panels.forEach(function (panel) {
					panel.hidden = panel.getAttribute('data-most-read-panel') !== target;
				});
			});
		});
	}

	// Envía a POST /wp-json/dnorte/v1/newsletter/subscribe (dnorte-core,
	// Newsletter\NewsletterController) — un correo real, guardado de verdad.
	// Sin JS, el <form> ya apunta ahí como action con method="post" normal.
	function initNewsletterForm() {
		var form = document.querySelector('[data-newsletter-form]');
		var status = document.querySelector('[data-newsletter-status]');

		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var field = form.querySelector('.newsletter__field');
			var submit = form.querySelector('.newsletter__submit');
			var email = field ? field.value : '';

			if (submit) {
				submit.disabled = true;
			}

			fetch(form.getAttribute('action'), {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ email: email }),
			})
				.then(function (response) {
					return response.json().then(function (data) {
						return { ok: response.ok, data: data };
					});
				})
				.then(function (result) {
					if (!status) {
						return;
					}

					status.textContent = result.data && result.data.message ? result.data.message : '';
					status.setAttribute('data-state', result.ok ? 'success' : 'error');

					if (result.ok && field) {
						field.value = '';
					}
				})
				.catch(function () {
					if (status) {
						status.textContent = 'No se pudo enviar. Intenta de nuevo en un momento.';
						status.setAttribute('data-state', 'error');
					}
				})
				.finally(function () {
					if (submit) {
						submit.disabled = false;
					}
				});
		});
	}

	function escapeHtml(value) {
		var div = document.createElement('div');

		div.textContent = value || '';

		return div.innerHTML;
	}

	// Arma el mismo marcado que template-parts/post-card.php a partir de la
	// respuesta JSON de la API REST nativa de WordPress (wp/v2/posts?_embed=1) —
	// sin esto "Cargar más" tendría que pedirle HTML a un endpoint propio nuevo
	// solo para esto.
	function postCardHtml(post) {
		var title = post.title && post.title.rendered ? post.title.rendered : '';
		var link = post.link || '#';
		var date = '';

		if (post.date) {
			try {
				date = new Date(post.date).toLocaleDateString('es-CO', {
					day: 'numeric',
					month: 'long',
					year: 'numeric',
				});
			} catch (error) {
				date = '';
			}
		}

		var media = '';
		var embedded = post._embedded || {};
		var featured = embedded['wp:featuredmedia'] && embedded['wp:featuredmedia'][0];
		var terms = embedded['wp:term'] && embedded['wp:term'][0] && embedded['wp:term'][0][0];

		var kicker = '';

		if (terms) {
			kicker =
				'<span class="kicker kicker--pill" data-category="' +
				escapeHtml(terms.slug) +
				'">' +
				escapeHtml(terms.name) +
				'</span>';
		}

		if (featured && featured.media_details && featured.media_details.sizes) {
			var sizes = featured.media_details.sizes;
			var size = sizes['dnorte-card'] || sizes.medium || sizes.full;

			if (size) {
				media =
					'<div class="post-card__media">' +
					kicker +
					'<img src="' +
					escapeHtml(size.source_url) +
					'" alt="' +
					escapeHtml(title) +
					'" loading="lazy" /></div>';
			}
		} else if (kicker) {
			media = kicker;
		}

		return (
			'<article class="post-card"><a class="post-card__link" href="' +
			escapeHtml(link) +
			'">' +
			media +
			'<div class="post-card__body"><h3 class="post-card__title">' +
			title +
			'</h3><time class="entry-date">' +
			escapeHtml(date) +
			'</time></div></a></article>'
		);
	}

	function initLoadMore() {
		var button = document.querySelector('[data-load-more]');
		var grid = document.querySelector('[data-news-grid]');

		if (!button || !grid) {
			return;
		}

		var excluded = (grid.getAttribute('data-excluded-ids') || '')
			.split(',')
			.filter(function (id) {
				return id !== '';
			});
		var page = 1;
		var loading = false;

		button.addEventListener('click', function () {
			if (loading) {
				return;
			}

			loading = true;
			page += 1;
			button.disabled = true;

			var params = new URLSearchParams({
				per_page: '8',
				page: String(page),
				_embed: '1',
			});

			excluded.forEach(function (id) {
				params.append('exclude[]', id);
			});

			fetch('/wp-json/wp/v2/posts?' + params.toString())
				.then(function (response) {
					if (!response.ok) {
						throw new Error('load-more request failed');
					}

					var totalPages = parseInt(response.headers.get('X-WP-TotalPages') || '1', 10);

					return response.json().then(function (posts) {
						return { posts: posts, totalPages: totalPages };
					});
				})
				.then(function (result) {
					result.posts.forEach(function (post) {
						grid.insertAdjacentHTML('beforeend', postCardHtml(post));
					});

					if (page >= result.totalPages || result.posts.length === 0) {
						button.hidden = true;
					}
				})
				.catch(function () {
					// Sin conexión/endpoint caído: el botón se queda como estaba,
					// el visitante puede intentar de nuevo con un segundo clic.
				})
				.finally(function () {
					loading = false;
					button.disabled = false;
				});
		});
	}

	function init() {
		initThemeToggle();
		initNavToggle();
		initSearchToggle();
		initMobileQuickNavMore();
		initMostReadFilter();
		initNewsletterForm();
		initLoadMore();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
