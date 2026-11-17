# Development

## Local environment

The repo runs with [Lando](https://lando.dev). The plugin folder is mounted into `dev/public/wp-content/plugins/tribe-embed`. Run `lando start`, then open [tribe-embed.lndo.site/wp-admin](https://tribe-embed.lndo.site/wp-admin) (user `admin`, password `password`).

To rebuild the Lando environment, delete the `./dev/public` folder. **Do not use `rm -rf ./dev/public`**: the volume link would delete the project root as well. If your `rm` supports it, use the `-x` option so it does not cross mount points.

## Requirements

- PHP 8.0 or newer (the Lando environment uses 8.3) and Composer
- Node 24 (see `.nvmrc`; with nvm run `nvm use`) and npm 11

```bash
composer install
npm ci
npm run build   # production build into dist/
npm start       # watch mode
```

## Checks

```bash
vendor/bin/phpcs                  # coding standards (ModernTribe)
vendor/bin/phpstan analyse        # static analysis
npm run lint:js
npm run lint:css
npm run lint:pkg-json
```

The same checks run on every pull request and on pushes to `main` in the `Lint` workflow (`.github/workflows/lint.yml`): one job for PHPCS and PHPStan, one for ESLint, Stylelint and the `package.json` lint.

## Source layout

| Path | Contents |
|---|---|
| `src/Core.php` | Creates the plugin's objects and registers their hooks; plugin lifecycle callbacks |
| `src/Providers/` | Video providers, the factory that finds them, the thumbnail cache |
| `src/Util/` | Block filter, facade builder, template loader, REST preview route, assets, hooks helper, remote client |
| `src/Admin/` | Settings page and API credentials |
| `templates/` | Facade view templates, overridable from a theme ([docs](templates.md)) |
| `assets/js/embed.js` | Front-end play script |
| `assets/js/editor.js` | Block editor preview ([docs](editor-preview.md)) |
| `assets/css/` | Front-end and editor styles |
| `dist/` | Build output (not committed) |

## Architecture

`Core::register_hooks()` (called on `plugins_loaded`) is the only place objects are created. It builds the object graph and passes each object its collaborators through the constructor. There are no singletons and no static helpers; the only static methods are the WordPress lifecycle callbacks (`Core::activate()`, `deactivate()` and `uninstall()`), which WordPress requires to be static.

How a `core/embed` block becomes a facade:

| Class | Role |
|---|---|
| `Util\Block_Filter` | Hooks `render_block`; skips feeds; asks the services below for a facade and enqueues assets |
| `Util\Url_Parser` | Parses the block URL |
| `Providers\Provider_Factory` | Picks the provider for the URL's host and creates it |
| `Providers\Provider` (`YouTube`, `Vimeo`, `Dailymotion`, `Wistia`) | Extracts the video ID and returns thumbnails. Providers that call a remote service extend `Remote_Provider` and receive `Util\Remote_Client`, `Providers\Thumbnail_Cache` and, for Vimeo and Wistia, `Admin\Credentials` |
| `Util\Thumbnail_Service` | Resolves provider, video ID and thumbnails for a URL |
| `Util\Facade_Builder` | Prepares the template data and renders the chunks through `Util\Template_Loader` |
| `Util\Facade_Rest` | REST route that renders the same facade for the block editor |

`Util\Hooks` applies the plugin's filters. Filters that were renamed in 2.1.0 are also applied under their old `tribe_embeds_*` name, see [Hooks reference](hooks.md#renamed-hooks-legacy-fallback). Add new renamed hooks to its list only when an existing hook changes name.

### Lifecycle

- **Activate / deactivate:** `Core::activate()` and `Core::deactivate()` are empty. Deactivating is often temporary, so nothing is deleted then.
- **Delete:** `Core::uninstall()` (registered with `register_uninstall_hook()` in `tribe-embed.php`) runs only when the plugin is deleted. It uses `Util\Data_Cleaner` to remove the stored API tokens and the thumbnail transients, on every site of a multisite network.

### Conventions

- Constructor injection; no `new` for collaborators outside `Core`.
- No error suppression (`@`). Validate input instead, for example video IDs with `Provider::valid_video_id()`.
- Templates only escape and output; data is prepared in PHP.

## Building the plugin zip

The release workflow (`.github/workflows/release.yml`) builds a zip on a published GitHub release. To build locally you need the [WP-CLI dist-archive](https://developer.wordpress.org/cli/commands/dist-archive/) command installed. Run `wp dist-archive .` from the project root. The zip is created one folder above it. Files listed in `.distignore` (including this `docs/` folder) are left out.
