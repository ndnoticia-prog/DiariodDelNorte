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

	# Sin dependencias de producción todavía (composer.json "require" solo pide PHP);
	# si en el futuro se añade alguna, aquí iría un
	# `composer install --no-dev --working-dir="${stage}"` antes de zipear.

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
	cp "${ROOT_DIR}/dnorte-theme/functions.php" "${stage}/"
	cp "${ROOT_DIR}/dnorte-theme/header.php" "${stage}/"
	cp "${ROOT_DIR}/dnorte-theme/footer.php" "${stage}/"
	cp "${ROOT_DIR}/dnorte-theme/index.php" "${stage}/"
	cp -R "${ROOT_DIR}/dnorte-theme/src" "${stage}/src"
	cp -R "${ROOT_DIR}/dnorte-theme/dist" "${stage}/dist"

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
