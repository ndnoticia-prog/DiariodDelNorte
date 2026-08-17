# DNorte Platform

Motor y plantilla de WordPress propios de [diariodelnorte.net](https://diariodelnorte.net/).

Repositorio independiente del proyecto ND Platform (Nuevo Día Noticias): no reutiliza ni
hace fork de `nd-core`/`nd-theme`. Construido desde cero, informado por las decisiones y
lecciones de ese proyecto — ver [`docs/handoff-nd-platform.md`](docs/handoff-nd-platform.md).

## Qué es

Dos instalables de WordPress en un mismo repositorio:

| Carpeta | Qué es |
|---|---|
| [`dnorte-core`](dnorte-core) | Plugin: núcleo de la plataforma (contenedor DI, configuración, hooks, eventos, orquestación de módulos). |
| [`dnorte-theme`](dnorte-theme) | Tema: presentación pura, sin lógica de negocio. Requiere `dnorte-core` activo. |

Alcance de la v1 (deliberadamente mínimo — ver `docs/handoff-nd-platform.md` §8):
núcleo + tema operables. Módulos adicionales (SEO técnico, multimedia, publicidad,
analítica propia, IA, búsqueda, workflow editorial) se evalúan uno a uno según necesidad
real de Diario del Norte, no por paridad con ND Platform.

## Requisitos

- PHP >= 8.1
- WordPress >= 6.4
- Composer >= 2.7
- Node.js >= 20 / npm >= 10

## Desarrollo

```bash
composer install
npm install

composer run check   # phpcs + phpstan + phpunit
npm run build          # build de assets (Vite) de dnorte-theme
```

## Instalar en un WordPress de desarrollo

```bash
ln -sfn "$(pwd)/dnorte-core" /ruta/a/wordpress/wp-content/plugins/dnorte-core
ln -sfn "$(pwd)/dnorte-theme" /ruta/a/wordpress/wp-content/themes/dnorte-theme
```

Activar el plugin **DNorte Core** primero y luego el tema **DNorte Theme** desde
`wp-admin`.

## Versionado

El proyecto avanza por versiones pre-release controladas (`v0.1.0-alpha.N` → ...).
Ninguna versión avanza hasta que compila, pasa `composer run check` en verde y queda
documentada en `CHANGELOG.md`. Cada pieza con interfaz se verifica además en un
navegador contra un WordPress real antes de darse por terminada — ver
`docs/handoff-nd-platform.md` §7 para el porqué. Ver [`ROADMAP.md`](ROADMAP.md) y
[`CHANGELOG.md`](CHANGELOG.md).

## Documentación

- [`docs/Architecture.md`](docs/Architecture.md) — decisiones de arquitectura propias.
- [`docs/handoff-nd-platform.md`](docs/handoff-nd-platform.md) — lo aprendido en ND Platform, y qué de eso aplica aquí.
- [`ROADMAP.md`](ROADMAP.md) — plan de versiones.
- [`CHANGELOG.md`](CHANGELOG.md) — historial de cambios.

## Licencia

GPL-2.0-or-later.
