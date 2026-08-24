import '../scss/app.scss';

// Punto de entrada de JS del front-end. El anti-parpadeo de modo oscuro vive
// inline en header.php (debe ejecutarse antes del primer paint, así que no
// puede depender de este bundle, que carga después) — aquí va el resto de la
// interacción posterior a la carga: tema oscuro, menú móvil, buscador
// colapsable, el carrusel de "Lo último" y "Cargar más".
//
// Todo aquí es progresivo: sin JS, el menú queda desplegado siempre, el
// buscador visible siempre, cada miniatura del hero es un <a> normal a su
// propio artículo, y el botón "Cargar más" simplemente no responde — nada se
// rompe, solo se pierde la interacción de un clic.

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

	// Cada miniatura/flecha ya es un <a> normal a su propio artículo (funciona sin
	// JS); aquí solo se añade el intercambio en vivo del hero visible, leyendo los
	// data-* que trae cada miniatura — sin volver a pedir nada al servidor.
	function initHeroCarousel() {
		var carousel = document.querySelector('.hero-carousel');

		if (!carousel) {
			return;
		}

		var thumbs = Array.prototype.slice.call(carousel.querySelectorAll('[data-hero-id]'));

		if (thumbs.length < 2) {
			return;
		}

		var mainLink = carousel.querySelector('[data-hero-main]');
		var titleEl = carousel.querySelector('[data-hero-title]');
		var kickerEl = carousel.querySelector('[data-hero-kicker]');
		var imgEl = carousel.querySelector('[data-hero-img]');
		var tickerEl = carousel.querySelector('[data-hero-ticker] a');
		var current = 0;

		function applyThumb(index) {
			var thumb = thumbs[index];

			if (!thumb) {
				return;
			}

			current = index;

			var href = thumb.getAttribute('href');
			var title = thumb.getAttribute('data-hero-title') || '';
			var category = thumb.getAttribute('data-hero-category') || '';
			var categorySlug = thumb.getAttribute('data-hero-category-slug') || '';
			var image = thumb.getAttribute('data-hero-image') || '';

			if (mainLink) {
				mainLink.setAttribute('href', href);
			}

			if (tickerEl) {
				tickerEl.textContent = title;
				tickerEl.setAttribute('href', href);
			}

			if (titleEl) {
				titleEl.textContent = title;
			}

			if (kickerEl) {
				if (category) {
					kickerEl.textContent = category;
					kickerEl.setAttribute('data-category', categorySlug);
					kickerEl.hidden = false;
				} else {
					kickerEl.hidden = true;
				}
			}

			if (imgEl && image) {
				imgEl.setAttribute('src', image);
				imgEl.setAttribute('alt', title);
			}

			thumbs.forEach(function (thumbLink) {
				var item = thumbLink.closest('.hero-carousel__thumb');

				if (item) {
					item.classList.toggle('is-active', thumbLink === thumb);
				}
			});
		}

		thumbs.forEach(function (thumb, index) {
			thumb.addEventListener('click', function (event) {
				event.preventDefault();
				applyThumb(index);
			});
		});

		var prevBtn = carousel.querySelector('[data-hero-prev]');
		var nextBtn = carousel.querySelector('[data-hero-next]');

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				applyThumb((current - 1 + thumbs.length) % thumbs.length);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				applyThumb((current + 1) % thumbs.length);
			});
		}
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
		initHeroCarousel();
		initLoadMore();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
