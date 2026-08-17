# Handoff: lo aprendido en ND Platform (Nuevo Día Noticias) para Diario del Norte

> Compilación de decisiones, arquitectura y lecciones reales del proyecto **ND Platform**
> (`/Users/delvisiban/Documents/Proyecto ND`), como punto de partida para el motor y la
> plantilla **propios** de diariodelnorte.net, en repositorio independiente.
>
> Decisión ya tomada: este proyecto **no** reutiliza ni hace fork de `nd-core`. Se construye
> un plugin/motor nuevo, diseñado según las necesidades reales de Diario del Norte, pero
> informado por lo que funcionó (y lo que falló) en ND Platform. Este documento es esa
> memoria — no es código a copiar literalmente.

---

## 1. Qué es ND Platform, en una frase

Un CMS editorial construido *sobre* WordPress (no un tema con funciones sueltas): monorepo
de 12 paquetes Composer/npm independientes, cada uno con responsabilidad única, orquestados
por un núcleo (`nd-core`) que es el único punto de contacto con las APIs de WordPress.
Estado actual: **v0.1.0**, checklist de producción completo (ver §7), instalado y verificado
en un WordPress real de punta a punta.

Catálogo de capacidades que cubre (referencia de "menú de funcionalidades" al decidir qué
necesita Diario del Norte — no todo aplica igual a un diario regional más pequeño):

| Capacidad | Qué resuelve |
|---|---|
| Núcleo / DI / hooks / eventos / REST / DB / migraciones / caché / colas / scheduler / seguridad / permisos | Infraestructura base de cualquier plugin serio |
| Tema de presentación (sin lógica de negocio) | Layouts, bloques de portada, modo oscuro, responsive |
| Constructor visual de bloques editoriales | Portada armable sin tocar código |
| SEO técnico (Schema.org, OpenGraph, sitemap Google News, robots) | Visibilidad en buscadores y Google News |
| Multimedia (WebP/AVIF, CDN, video, podcast) | Optimización de imagen/video/audio |
| Flujo editorial (estados, notas internas, asignaciones, calendario) | Gestión de redacción |
| Publicidad propia (AdSense/GAM/patrocinados, segmentación, stats) | Monetización sin plugin de terceros |
| Analítica editorial propia (tiempo real, más leídas, CTR) | Sin depender de Google Analytics |
| Proveedor de IA desacoplado (OpenAI/Claude/Gemini/DeepSeek/local) | Asistencia de redacción |
| Caché de página completa | Rendimiento en alto tráfico |
| Google Discover | Tráfico desde Discover |
| Búsqueda interna con índice FULLTEXT propio | Búsqueda relevante, no el `LIKE` de WP |

**Para Diario del Norte**: no asumir que hace falta el mismo alcance desde el día uno. La
metodología de ND Platform (§7) — avanzar por versiones que compilan/pasan pruebas antes de
sumar la siguiente — es más valiosa de replicar que la lista completa de paquetes. Priorizar
según lo que el diario realmente necesita operar (probablemente: núcleo + tema + SEO +
multimedia primero; ads/analítica/IA/búsqueda propia después, si aportan sobre lo nativo de
WordPress o un plugin ya probado).

---

## 2. Principios de arquitectura que sí vale la pena repetir

1. **Un único punto de acceso a WordPress.** Todo `add_action`/`add_filter`/`$wpdb`/función
   nativa pasa por una capa de abstracción propia (`HookManager`, `DatabaseManager`, ...).
   Esto es lo que permite tests unitarios reales sin un WordPress real montado (Brain Monkey).
2. **La plantilla no contiene lógica de negocio.** Solo composición visual: recibe datos ya
   resueltos y los renderiza. Si Diario del Norte solo necesita un plugin fino, igual conviene
   mantener esta separación desde el principio — evita que el tema se vuelva imposible de
   sustituir más adelante.
3. **Inyección de dependencias explícita**, nada instanciado con `new` fuera de un
   proveedor/factory — todo resuelto por un contenedor con autowiring por reflexión.
4. **Sin duplicación de capacidades ya nativas de WordPress.** Antes de reimplementar algo,
   preguntar si WordPress core ya lo resuelve razonablemente bien. Ver §4 — es la lección más
   rentable de todo el proyecto.
5. **Hooks de WordPress vs. bus de eventos interno** son cosas distintas: hooks para
   integrarse con WordPress/otros plugins; un `EventDispatcher` interno para que los módulos
   propios se comuniquen sin acoplarse entre sí (ver `BlockRendered` en §4).

### Ciclo de vida de referencia

```
WordPress carga el plugin (bootstrap)
  → Application::boot()
      → Container: bindings base (Config, EventDispatcher, HookManager, ...)
      → Carga config → Config
      → Resuelve providers (core + cada módulo activo)
          → register(): bindings del módulo
          → boot(): hooks/rutas/comandos del módulo
      → HookManager::flush(): listeners registrados → add_action/add_filter reales
```

Si el proyecto de Diario del Norte separa plugin (motor) y tema (presentación) igual que ND
Platform, el punto más frágil de este ciclo es **cuándo arranca cada uno** — ver el bug real
en §5 (orden `plugins_loaded` vs `after_setup_theme`) antes de decidir la estrategia de boot.

---

## 3. Decisiones técnicas concretas que se pueden reutilizar tal cual (son WordPress-agnósticas del "branding" de ND)

- **JSON-LD como un único `@graph`**, no un `<script>` por tipo, codificado con
  `JSON_HEX_TAG | JSON_HEX_AMP`. Sin esos flags, un título de artículo con `</script>`
  literal rompe el bloque e inyecta HTML/JS — vulnerabilidad real y conocida en plugins de
  SEO para WordPress.
- **Reutilizar `wp-sitemap.xml` nativo** (WP ≥ 5.5) en vez de reimplementar un sitemap
  general; solo añadir lo que WP no cubre (ej. sitemap de Google News, que exige el namespace
  `news:` y ventana de tiempo corta).
- **`srcset`/lazy load nativos de WordPress** (4.4 y 5.5 respectivamente) — no reimplementar.
  Solo intervenir donde WP se equivoca por defecto (ej. `sizes` calculado solo del ancho
  intrínseco, sin conocer la cuadrícula real del tema).
- **WebP/AVIF vía el filtro nativo `image_editor_output_format`** (WP 5.8+), comprobando en
  tiempo real soporte real de GD (`imagewebp()`/`imageavif()`) antes de activarlo — nunca
  forzar un formato no soportado por el servidor.
- **Credenciales de terceros cifradas en reposo** (`sodium_crypto_secretbox`), nunca en texto
  plano en `wp_options` — aplica a cualquier clave de API que el plugin vaya a guardar (IA,
  anuncios, servicios externos).
- **Redirección de clics resuelta en servidor a partir de un ID**, nunca aceptando una URL de
  destino como parámetro de la petición — evita open-redirect por diseño, no por validación.
- **No reimplementar comentarios/versionado si WordPress ya los resuelve.** Notas editoriales
  internas sí ameritan tabla propia (flujo distinto al de comentarios públicos); versionado de
  contenido no — las revisiones nativas de WP ya sirven.
- **No alterar el esquema de tablas core de WordPress** (ej. no añadir FULLTEXT a `wp_posts`)
  para funcionalidad propia — crear una tabla propia indexada y sincronizarla vía hooks.
- **Patrón de "menú de admin compartido" / "rutas REST distribuidas”**: un filtro +
  un contrato + un value object, centralizado por un único proveedor — evita que cada módulo
  reinvente cómo registrarse, y evita un plugin `-api` vacío que solo "existe" sin
  responsabilidad propia.

---

## 4. Qué reimplementar y qué no — la pregunta a hacerse en cada feature

La lección más rentable de ND Platform no es una pieza de código sino el hábito: **antes de
construir algo, preguntar si WordPress core (o el hosting) ya lo resuelve razonablemente
bien**, y documentar la respuesta. Ejemplos ya resueltos así:

| Necesidad | Se reimplementó | Se reutilizó de WP core |
|---|---|---|
| Sitemap general | — | `wp-sitemap.xml` (5.5+) |
| Sitemap de Google News | Sí (namespace propio, ventana 48h) | — |
| `srcset` responsive | — | Nativo desde 4.4 |
| Lazy load | — | Nativo desde 5.5 |
| WebP/AVIF | Sí (filtro nativo, con detección de soporte) | — |
| Comentarios públicos | — | Sistema nativo |
| Notas editoriales internas | Sí (tabla propia, flujo distinto) | — |
| Versionado de contenido | — | Revisiones nativas |
| Búsqueda con ranking real | Sí (índice FULLTEXT propio, tabla propia) | — |
| Caché de objetos | Ya cubierta por capa propia | — |
| Caché de página completa | Sí, pero reutilizando la capa de caché de objetos como backend | — |

Para Diario del Norte: repetir esta tabla como ejercicio temprano por cada módulo que se
plantee construir. Evita meses de trabajo en algo que un filtro de una línea ya resuelve.

---

## 5. Bugs y lecciones reales (para no repetirlos)

Todos encontrados en producción/verificación manual, no por tests automáticos — vale la pena
leerlos como checklist de verificación, no solo como historia:

1. **Orden de arranque plugin vs. tema.** Si el motor arranca en `plugins_loaded`, un tema
   que se auto-registra en su propio `functions.php` (que carga *después* de
   `plugins_loaded`) nunca llega a tiempo. Sus assets, menús y theme supports simplemente no
   se registran, sin error visible — solo se detecta activando el tema en un sitio real y
   revisando el front-end. **Mitigación aplicada**: mover el arranque del lado del tema a
   `after_setup_theme` con prioridad temprana.
2. **Caché de `null`/`false` en un driver de transients.** Guardar `null` como valor cacheado
   se serializaba como cadena vacía en una columna `NOT NULL`, rompiendo la distinción entre
   "no cacheado" y "cacheado como null/false" al leer de vuelta — provocó un `TypeError`
   fatal río abajo. Mitigación: patrón de valor centinela explícito para "no encontrado".
3. **Una página de admin completamente implementada y testeada, pero nunca registrada** en la
   lista de proveedores que el bootstrap resuelve. Ningún test unitario ni de integración lo
   detectó porque ninguno ejercitaba la cadena completa `boot() → menú realmente en
   wp-admin`. Solo verificación manual en navegador contra un WordPress real lo reveló.
4. **`__DIR__` y symlinks de Composor (repos `path`).** Un paquete instalado como symlink
   resuelve `__DIR__` a la ruta *canónica* real, no a la ruta tal como se alcanza desde
   `vendor/` — una utilidad que construye URLs a partir de `__DIR__` devolvía cadena vacía en
   desarrollo (funcionaba en producción, donde no hay symlinks). Mitigación: construir la URL
   a partir del nombre de paquete conocido (cadena literal), no introspección del filesystem.
5. **Rewrite rules registradas dentro del propio hook de activación no llegan a tiempo** para
   el `flush_rewrite_rules()` que corre en la misma petición (limitación conocida de
   WordPress: `init` ya se disparó antes del hook de activación). Si una URL nueva da 404
   justo tras activar el plugin, revisar primero si hace falta guardar permalinks una vez.
6. **Empaquetar dependencias hermanas sin duplicar clases.** Si dos "paquetes" activables por
   separado (plugin + tema) pueden compartir una tercera dependencia, esa dependencia debe
   vendorizarse en **uno solo** de los dos (quien la declara en `require`, no en
   `require-dev`) — de lo contrario WordPress carga el mismo namespace dos veces desde dos
   `vendor/` distintos y truena con `Cannot declare class ..., already declared`. Aplica
   igual aunque Diario del Norte no adopte un monorepo de 12 paquetes: aplica en cuanto haya
   más de un punto de entrada de WordPress (plugin + tema) compartiendo código.

---

## 6. Toolchain y flujo de trabajo replicable

- **Composer + PHPUnit + PHPStan (nivel máximo) + PHPCS/WPCS**, todo detrás de un único
  `composer run check`. Ninguna versión avanza hasta que compila y pasa limpio.
- **Dos suites de PHPUnit**: unitarias con Brain Monkey (rápidas, sin BD, interceptan
  funciones de WP) e integración con un WordPress real (para `WP_Query`/`WP_Post`/`$wpdb`
  reales). Sin Docker/`wp-env` disponible aquí, se resolvió con `git sparse-checkout` de
  `WordPress/wordpress-develop` + MariaDB local sin `sudo` (Homebrew de usuario) — ver
  `tools/wp-tests/README.md` del proyecto ND como receta paso a paso si Diario del Norte
  tampoco tiene Docker disponible.
- **PHPUnit 9 aislado para el arnés de integración** cuando el propio arnés de WordPress
  core todavía no soporta la versión de PHPUnit usada por las pruebas unitarias — proceso
  separado con autoloader propio, sin cargar el `vendor/autoload.php` del paquete bajo
  prueba (evitaría un choque de versiones de PHPUnit en el mismo proceso).
- **Empaquetado**: un script que genera el `.zip` instalable resolviendo symlinks de
  Composor un solo nivel (nunca de forma recursiva — cae en explosión combinatoria si los
  paquetes hermanos se referencian entre sí) y copiando a mano solo las carpetas de
  producción (`src/`, `assets/`, `config/`), nunca `vendor/`/`tests/` de cada hermano.
- **Verificación final no negociable**: instalar el `.zip` real en un WordPress limpio
  (base de datos nueva, sin symlinks de desarrollo) y confirmar `debug.log` vacío durante
  instalación + activación + navegación del front-end + páginas de admin.

---

## 7. Metodología de versionado (la parte más exportable de todas)

No se avanza de versión hasta que la anterior:
1. Compila.
2. Pasa `composer run check` (lint + análisis estático + tests) en verde.
3. Queda documentada en un `CHANGELOG.md`.

Esto se combina con una regla más blanda pero igual de importante: **cada pieza con UI se
verifica en un navegador contra un WordPress real antes de darse por terminada** — varios de
los bugs de §5 solo salieron a la luz así, nunca por un test automático. Para un proyecto más
chico como el de Diario del Norte, la propuesta es adoptar la disciplina (checklist de verde +
documentación + verificación manual en navegador) sin necesariamente adoptar la misma
granularidad de 12 paquetes — puede ser un único plugin con módulos internos bien separados
por namespace/carpeta, y solo partirlo en paquetes Composer independientes si en el futuro
hace falta reusarlos en otro sitio.

---

## 8. Preguntas abiertas específicas de Diario del Norte

Este documento compila lo aprendido en ND Platform; lo que sigue son decisiones que faltan y
son propias de este proyecto nuevo, no heredables de ND:

- Alcance real v1: ¿motor + tema con qué módulos desde el día uno? (sugerido: núcleo + tema +
  SEO + multimedia; el resto según necesidad real del diario, no por paridad con ND).
- Identidad de marca / diseño de la plantilla (paleta, tipografía, logo, layout de portada) —
  no derivable de este documento, es trabajo de diseño propio de diariodelnorte.net.
- ¿Estructura de un solo plugin+tema, o monorepo de paquetes desde el inicio? (ND empezó
  igual de simple y fue creciendo — no hace falta copiar la estructura de 12 paquetes si el
  alcance inicial no la justifica).
- Requisitos de infraestructura del hosting real de Diario del Norte (PHP/MySQL/Redis
  disponibles, CDN existente, etc.) — determina qué de §1 tiene sentido construir ya.
- Nombre de namespace/prefijo propio para el nuevo motor (evitar cualquier colisión o
  parecido con `NDCore`/`nd-core` de ND Platform, que es un proyecto distinto).

---

*Fuente: `/Users/delvisiban/Documents/Proyecto ND` — `README.md`, `ROADMAP.md`,
`docs/Architecture.md`, `tools/wp-tests/README.md`, estado en `v0.1.0` al 2026-08-17.*
