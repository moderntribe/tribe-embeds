# Tribe Embed

Replaces the video player in the `core/embed` block with a lightweight facade (a thumbnail and play button), and loads the real player only when the visitor clicks. Supports YouTube, Vimeo, Dailymotion and Wistia.

## Requirements

- WordPress 6.3 or newer
- PHP 8.0 or newer

## Installation

1. Download the latest plugin zip from the [GitHub releases](https://github.com/moderntribe/tribe-embed/releases).
2. In WordPress go to **Plugins → Add New → Upload Plugin**, upload the zip and activate it.
3. Vimeo and Wistia need an API token: add it under **Settings → Tribe Embeds**, or define `VIMEO_ACCESS_TOKEN` / `WISTIA_API_KEY` in `wp-config.php`. See [Providers & credentials](docs/providers.md).

## Documentation

- [Overview](docs/overview.md): what it does and how it works
- [Providers & credentials](docs/providers.md)
- [Templates](docs/templates.md): override the markup from a theme or plugin
- [Block editor preview](docs/editor-preview.md)
- [Hooks reference](docs/hooks.md): filters, renamed and deprecated hooks
- [Development](docs/development.md): local environment, build, releasing

See the [changelog](CHANGELOG.md) for version history.
