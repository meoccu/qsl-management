# Changelog

## 1.0.5

- Share the `cf-turnstile` script handle with the Theme Wing comment form, preventing a second copy of the Cloudflare Turnstile API on pages that contain both comments and an exchange form.
- Render QSL exchange Turnstile widgets explicitly so they work alongside the theme's explicit comment widget.

## 1.0.4

- Add English README.
- Add automated GitHub Release workflow that builds and uploads the plugin ZIP.
- Link Chinese and English documentation.

## 1.0.3

- Show visible card style cards with image, version name and remaining stock in the public online exchange and card application forms.
- Add a gradient placeholder card face when no image is configured.
- Add card face previews in the station card styles admin screen.

## 1.0.2

- Clarify station card styles vs. formal card records.
- Add workflow guidance on the card records and online exchange admin pages.
- Show a clear message when no card styles are configured.

## 1.0.1

- Improve card creation errors: duplicate card, missing inventory, missing version and database errors are now shown explicitly.
- Add Cloudflare Turnstile to online and offline exchange forms.
- Add Turnstile Site Key / Secret Key settings.

## 1.0.0

- Initial WordPress port: admin CRUD, public shortcodes, reviews, receiving, backups and audit log.
