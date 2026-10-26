# QSL Management for WordPress

A WordPress port of the Halo 2 plugin **qsl-management** for amateur radio QSL workflows: QSO/SWL records, QSL card records, online/offline exchanges, card receiving, public shortcodes, audit logs and backups.

## Credits

- **Original author:** bi1kbu
- **Original repository:** <https://github.com/bi1kbu/qsl-management>
- **Halo app store:** <https://www.halo.run/store/apps/app-z7ouhnfn>
- **WordPress port:** 砂糖橘猫
- **Port author website:** <https://www.luna.tel>
- **This repository:** <https://github.com/meoccu/qsl-management>
- **License:** GPL-3.0-or-later (original project is GPL-3.0)

## Contributors

- **meoccu** — WordPress port owner and maintainer · <https://github.com/meoccu>
- **bi1kbu** — original Halo plugin author · <https://github.com/bi1kbu>
- **砂糖橘猫** — WordPress port author · <https://www.luna.tel>

See [CONTRIBUTORS.md](CONTRIBUTORS.md) for details.

## Installation

1. Download the latest release ZIP.
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate **QSL Management**.
4. Open **QSL Management → Settings** and configure station details.
5. Add card styles in **QSL Management → Station Card Styles**.
6. Create pages and insert the public shortcodes.

## Public shortcodes

| Shortcode | Purpose |
| --- | --- |
| `[qsl-card]` / `[qsl-card-query]` | Look up QSOs by call sign and apply for a physical card |
| `[qsl-online-exchange-card]` | Online exchange application with visible card styles |
| `[qsl-offline-exchange-card]` | Offline exchange confirmation |
| `[qsl-receipt-card]` | Public receipt confirmation |

## Correct online exchange workflow

1. **Station Card Styles** — create card styles, inventory and card face images.
2. **Visitor application** — the visitor fills in the address and selects a visible card style. Submitting creates an **exchange request**, not a formal card record.
3. **Review** — approve or reject the request in **Online Exchange**.
4. **Create card** — only after approval, use **Create Card** to create the formal card record.

## Cloudflare Turnstile

Enable Turnstile in **QSL Management → Settings**, then enter the Site Key and Secret Key. Turnstile protects the online and offline exchange forms. The secret is validated server-side with Cloudflare `siteverify` and is never printed on public pages.

## Scope

This first WordPress port implements the core workflow. The Halo-specific AI parsing, QRZ.COM/QRZ.CN lookup, ADIF/Cabrillo import/export, CardPrint desktop bridge and Halo RBAC templates are not included yet. See [`docs/porting-notes.md`](docs/porting-notes.md).

## Development

- PHP 7.4+
- WordPress 6.2+
- PHP files are linted by GitHub Actions on PHP 7.4 and 8.2.

## Documentation

- [中文 README](README.md)
- [Authors and attribution](AUTHORS.md)
- [Porting notes](docs/porting-notes.md)

## License

GPL-3.0-or-later. The original project's GPL-3.0 `LICENSE` is included.
