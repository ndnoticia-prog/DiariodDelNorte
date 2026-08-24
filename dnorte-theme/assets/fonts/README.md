# Fuentes autoalojadas

`Playfair Display` (700/800, titulares/masthead) y `Source Sans 3` (400/600/700,
cuerpo/UI) — descargadas de Google Fonts (subconjunto `latin`, cubre los
diacríticos del español) y servidas aquí en local (`@font-face` en
`assets/scss/app.scss`) en vez de desde `fonts.googleapis.com`/`fonts.gstatic.com`:
sin esa petición adicional a un tercero en cada visita, mismo motivo de
rendimiento/privacidad que ya documentaba `app.scss` cuando el tema usaba solo
pilas de fuentes del sistema. Reemplaza esa elección desde `v0.1.0-alpha.17`,
a partir de la maqueta de portada real que exige esta tipografía.

Ambas bajo [SIL Open Font License 1.1](https://openfontlicense.org/) — libres
para uso comercial, embebido y redistribución sin atribución obligatoria en
el sitio; se conserva aquí solo como referencia de origen/licencia.
