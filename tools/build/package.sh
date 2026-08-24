#!/usr/bin/env bash
#
# Genera dist/<paquete>-<version>.zip listo para instalar en WordPress
# (Plugins → Añadir nuevo → Subir plugin / Apariencia → Temas → Añadir nuevo → Subir tema).
#
# Uso: tools/build/package.sh dnorte-core
#      tools/build/package.sh dnorte-theme
#      tools/build/package.sh            (empaqueta ambos)

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"

package_dnorte_core() {
	local version
	version="$(grep -m1 "Version:" "${ROOT_DIR}/dnorte-core/dnorte-core.php" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
	local stage="${ROOT_DIR}/tools/build/.stage/dnorte-core"

	echo "==> Empaquetando dnorte-core ${version}"
	rm -rf "${stage}"
	mkdir -p "${stage}"

	cp "${ROOT_DIR}/dnorte-core/dnorte-core.php" "${stage}/"
	cp -R "${ROOT_DIR}/dnorte-core/src" "${stage}/src"
	cp -R "${ROOT_DIR}/dnorte-core/config" "${stage}/config"
	cp -R "${ROOT_DIR}/dnorte-core/assets" "${stage}/assets"
	cp "${ROOT_DIR}/dnorte-core/composer.json" "${ROOT_DIR}/dnorte-core/composer.lock" "${stage}/"

	# Primera dependencia de producción real de la plataforma (dompdf, para
	# Ads\CampaignReportPdfRenderer) desde v0.1.0-alpha.16 — antes "require" solo
	# pedía PHP y no hacía falta ningún vendor/ en el zip. `--no-dev` deja fuera
	# PHPUnit/PHPStan/WPCS/etc (nunca deben viajar al sitio real); el composer.json/
	# .lock copiados se borran después de instalar, el zip final no los necesita.
	( cd "${stage}" && composer install --no-dev --no-interaction --optimize-autoloader --quiet )
	rm -f "${stage}/composer.json" "${stage}/composer.lock"

	mkdir -p "${DIST_DIR}"
	local zip_path="${DIST_DIR}/dnorte-core-${version}.zip"
	rm -f "${zip_path}"
	(cd "${ROOT_DIR}/tools/build/.stage" && zip -rq "${zip_path}" "dnorte-core")

	echo "    ${zip_path}"
}

package_dnorte_theme() {
	local version
	version="$(grep -m1 "Version:" "${ROOT_DIR}/dnorte-theme/style.css" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
	local stage="${ROOT_DIR}/tools/build/.stage/dnorte-theme"

	echo "==> Empaquetando dnorte-theme ${version}"

	if [ ! -f "${ROOT_DIR}/dnorte-theme/dist/app.css" ]; then
		echo "    ERROR: dnorte-theme/dist/app.css no existe. Corre 'npm run build' primero." >&2
		exit 1
	fi

	rm -rf "${stage}"
	mkdir -p "${stage}"

	cp "${ROOT_DIR}/dnorte-theme/style.css" "${stage}/"
	# Todas las plantillas de la jerarquía de WordPress en la raíz del tema
	# (functions.php, header.php, footer.php, index.php, front-page.php, single.php,
	# archive.php, ...), en vez de una lista fija: una plantilla nueva no debe
	# requerir acordarse de actualizar este script para quedar incluida en el zip
	# — bug real encontrado en la verificación de v0.1.0-alpha.6 (front-page.php
	# faltaba en el zip, la portada usaba el fallback index.php sin que ningún test
	# lo detectara).
	cp "${ROOT_DIR}"/dnorte-theme/*.php "${stage}/"
	rm -f "${stage}/phpstan-bootstrap.php" # solo para análisis estático en desarrollo
	cp -R "${ROOT_DIR}/dnorte-theme/template-parts" "${stage}/template-parts"
	cp -R "${ROOT_DIR}/dnorte-theme/src" "${stage}/src"
	cp -R "${ROOT_DIR}/dnorte-theme/dist" "${stage}/dist"
	# Solo assets/images (el logo real, servido directo por PHP en header.php/
	# footer.php — desde v0.1.0-alpha.17). assets/fonts y assets/scss|js NO se
	# copian: son fuente de Vite, ya compilada dentro de dist/ (las fuentes woff2
	# las copia Vite solo a dist/assets/ por referenciarse desde app.scss);
	# copiar también la carpeta assets/ completa duplicaría ese peso en el zip.
	mkdir -p "${stage}/assets"
	cp -R "${ROOT_DIR}/dnorte-theme/assets/images" "${stage}/assets/images"

	mkdir -p "${DIST_DIR}"
	local zip_path="${DIST_DIR}/dnorte-theme-${version}.zip"
	rm -f "${zip_path}"
	(cd "${ROOT_DIR}/tools/build/.stage" && zip -rq "${zip_path}" "dnorte-theme")

	echo "    ${zip_path}"
}

case "${1:-}" in
	dnorte-core)
		package_dnorte_core
		;;
	dnorte-theme)
		package_dnorte_theme
		;;
	"")
		package_dnorte_core
		package_dnorte_theme
		;;
	*)
		echo "Paquete desconocido: ${1}" >&2
		exit 1
		;;
esac

rm -rf "${ROOT_DIR}/tools/build/.stage"
