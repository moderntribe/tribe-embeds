# Changelog

All notable changes to this project will be documented in this file.

## v2.1.0 (Nov 17, 2026)

### Added
- [MOOSE-411] Facade markup moved into overridable view templates in `templates/`, split into chunks (wrapper, play button, thumbnail, embed). Override them from a theme at `tribe-embed/`, or add directories with the `tribe_embed_template_dirs` filter. New `tribe_embed_template_path` filter.
- Block editor: embeds are shown as the front-end facade (thumbnail and play button) instead of the live player, through a new REST route `tribe-embed/v1/facade`. Front-end and editor styles are loaded into the editor iframe.
- Filters `tribe_embed_thumbnail_sizes`, `tribe_embed_thumbnail_loading` and `tribe_embed_thumbnail_fetchpriority`, and `tribe_embed_video_embed_inline` for inline providers.
- Deleting the plugin removes stored API tokens and thumbnail transients (multisite aware).
- Documentation moved from the readme into `docs/`; the readme is now a short install guide.

### Changed
- Internal restructuring: objects get their collaborators through their constructors instead of static calls. `Hooks`, `Remote_Client`, `Thumbnail_Cache` and `Credentials` are injected; the built-in providers take the remote client, cache and credentials; `Core` is a plain composition root, so `Core::instance()`, `tribe_embed_core()` and the service getters on `Core` are gone. Custom providers (`tribe_embed_provider_classes`) are unchanged: they still receive the parsed URL only.
- Plugin data is removed through `register_uninstall_hook()` (`Core::uninstall()`) instead of a separate `uninstall.php`.
- Host rules (`ALLOWED_HOSTS`) are plain hostnames that also match subdomains, so `m.youtube.com` and similar URLs are recognised.
- `fetchpriority="auto"` is always output on thumbnails (the browser default); `tribe_embed_thumbnail_fetchpriority` can return `high` or `low`.
- All hooks now use the `tribe_embed_` prefix (previously a mix of `tribe_embed_` and `tribe_embeds_`). The old `tribe_embeds_*` filter names keep working with a deprecation notice, see `docs/hooks.md` for the mapping. The API-token options were renamed the same way (`tribe_embed_vimeo_access_token`, `tribe_embed_wistia_api_key`); existing values are still read until the settings are saved again.
- The caption is rendered on the server instead of by JavaScript (no layout shift).
- Thumbnails: `decoding="async"`, and only sizes with the same aspect ratio as the largest image are put in `srcset`.
- The player origin is preconnected on hover/focus.
- YouTube thumbnails are checked with `HEAD` requests and fixed sizes instead of being downloaded; Dailymotion uses a single API request.
- The public script and style are registered once and enqueued only on pages that render a facade; editor assets load only in the block editor.
- Frontend tooling requires Node 24 (`.nvmrc`, `engines`): `@wordpress/scripts` 37, `cssnano` 9, `postcss-import` 17, `postcss-preset-env` 11. ESLint config moved to the flat `eslint.config.js`.
- `package.json` and the lockfile versions now match the plugin version.

### Deprecated
- `tribe_embeds_video_thumb_open_markup` and `tribe_embeds_video_thumb_close_markup`: the wrapper is a single template now. They still run when hooked; override `facade.php` instead.
- `tribe_embeds_video_embed_template` for inline providers: use `tribe_embed_video_embed_inline`.

### Fixed
- The facade stylesheet was printed with an unregistered handle.
- Thumbnail lookups now have request timeouts, handle failed image and API responses, cache failures for 5 minutes, and use per-provider transient keys.
- Iframe autoplay arguments are built with the URL API (valid for any provider and URL); facades added after page load (client-side navigation, AJAX) are initialised.
- A missing `dist` build no longer causes a fatal error.
- Feeds keep the original embed markup instead of the facade.
- Vimeo channel and group URLs resolve to the right video.

### Accessibility
- Iframe `title`, `allow`, `loading` and `referrerpolicy` attributes are no longer stripped.
- Focus moves to the player after play; the play button has `type="button"` and a label that includes the caption; visible `:focus-visible` ring; reduced-motion support; a no-JS link to the video.

### Security
- Provider host rules match the host and its subdomains only (previously every rule was an unanchored regular expression, so `evilyoutube.com` matched `youtube.com`). Regular expressions in host rules are no longer supported.
- Video IDs are validated per provider, which also fixes a fatal `TypeError` on `?v[]=` YouTube URLs.
- Thumbnail `src`, `srcset`, dimensions and classes are escaped.

## v1.1.1 (Nov 20, 2025)
- Add plugin Settings page
- Change Vimeo API usage. API key and secret are required for proper work

## v1.1.0 (Mar 6, 2025)

- Version bump for public release.

## v1.1 (Mar 6, 2025)

- Add ability to extend the providers list.

## v1.0.5 (Jan 9, 2025)

- [MOOSE-182] Fix fatal errors.

## v1.0.4 (Jan 3, 2025)

- [MOOSE-182] Update icons.

## v1.0.3 (Jun 13, 2024)

- [MOOSE-112] Allow embed wrapper element.

## v1.0.2 (Dec 20, 2023)

- Version bump for public release.

## v1.0.2-rc1 (Dec 20, 2023)

- Version bump for release candidate.

## v1.0.1 (Dec 1, 2023)

- Fix versioning in filename.
