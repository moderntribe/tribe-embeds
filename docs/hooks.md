# Hooks reference

These are the **public** extension points for themes and plugins. Names and arguments are part of the API.

All hooks use the `tribe_embed_` prefix. Hooks that were called `tribe_embeds_*` before 2.1.0 still work under the old name, see [Renamed hooks](#renamed-hooks-legacy-fallback).

- [Provider and host filters](#provider-and-host-filters)
- [Thumbnail data](#thumbnail-data)
- [Markup](#markup)
- [Markup chunks](#markup-chunks)
- [Thumbnail attributes](#thumbnail-attributes)
- [Templates](#templates)
- [Renamed hooks (legacy fallback)](#renamed-hooks-legacy-fallback)
- [Deprecated](#deprecated)

`<slug>` below is the provider slug: `youtube`, `vimeo`, `dailymotion` or `wistia` (the lowercased class name for custom providers).

## Provider and host filters

### `tribe_embed_allowed_provider_hosts_<slug>`

Hosts one provider accepts. A host matches itself and its subdomains: `example.com` accepts `example.com` and `videos.example.com`, but not `notexample.com`. Regular expressions are not supported.

```php
apply_filters( 'tribe_embed_allowed_provider_hosts_' . $slug, $hosts, $provider_class );
```

- `$hosts` (`string[]`): the provider's hosts.
- `$provider_class` (`string`): provider class name.

```php
// Accept a vanity domain for YouTube embeds only.
add_filter( 'tribe_embed_allowed_provider_hosts_youtube', function ( array $hosts ) {
    $hosts[] = 'videos.example.com';
    return $hosts;
} );
```

### `tribe_embed_allowed_provider_hosts`

Same as above, but runs for every provider, after the per-provider filter. Check `$provider_class` before adding a host, otherwise every provider accepts it.

```php
apply_filters( 'tribe_embed_allowed_provider_hosts', $hosts_from_slug_filter, $provider_class );
```

### `tribe_embed_provider_classes`

Replace or extend the list of provider classes.

```php
apply_filters( 'tribe_embed_provider_classes', $provider_classes );
```

- `$provider_classes` (`string[]`): fully qualified class names. Classes must extend `Tribe\Tribe_Embed\Providers\Provider`; they are created with the parsed URL only: `new CustomProvider( $video_url_data )`.

### `tribe_embed_video_provider`

Short-circuit provider resolution with a ready-made provider instance.

```php
apply_filters( 'tribe_embed_video_provider', null, $video_url_data, $block );
```

- `null|Provider`: return an object to use it immediately.
- `$video_url_data` (`array`): parsed URL (`host`, `path`, `query`, `url`, …).
- `$block` (`array`): the `core/embed` block.

```php
add_filter( 'tribe_embed_video_provider', function ( $provider, array $video_url_data, array $block ) {
    if ( $video_url_data['host'] === 'videos.example.com' ) {
        return new CustomProvider( $video_url_data );
    }

    return $provider;
}, 10, 3 );
```

## Thumbnail data

### `tribe_embed_image_sizes_<slug>` / `tribe_embed_image_sizes`

The image size names a provider requests.

```php
apply_filters( 'tribe_embed_image_sizes_' . $slug, $sizes, $provider_class );
apply_filters( 'tribe_embed_image_sizes', $sizes_from_slug_filter, $provider_class );
```

### `tribe_embed_<slug>_video_thumbnail_url` / `_data`

Adjust a provider's thumbnail data before it is used. Vimeo, Wistia and Dailymotion use `…_video_thumbnail_url`; YouTube uses `tribe_embed_youtube_video_thumbnail_data`.

```php
apply_filters( 'tribe_embed_wistia_video_thumbnail_url', $image_data, $video_id );
```

- `$image_data` (`array<string,array{url:string,width:int,height:int}>`): thumbnails keyed by size name.
- `$video_id` (`string`).

### `tribe_embed_thumbnail_data`

Final thumbnail data for a video, whichever provider produced it.

```php
apply_filters( 'tribe_embed_thumbnail_data', $thumbnail_data, $provider, $image_sizes );
```

## Markup

### `tribe_embed_facade_html`

The complete facade HTML before output. Also used for the block editor preview.

```php
apply_filters( 'tribe_embed_facade_html', $facade_html, $provider, $block, $original_html );
```

- `$facade_html` (`string`): resulting HTML.
- `$provider` (`Provider`).
- `$block` (`array`): the `core/embed` block.
- `$original_html` (`string`): the block's original rendered HTML (empty in the editor preview).

## Markup chunks

The output of each [template](templates.md) chunk passes through a filter. All receive `$block` (`array`), `$video_id` (`string`), `$thumbnail_data` (`array`) and `$wrapper_classes` (`string[]`), after the value.

| Filter | Value |
|---|---|
| `tribe_embed_video_wrapper_classes` | wrapper CSS classes (`array`); receives `$block, $video_id, $thumbnail_data` |
| `tribe_embed_video_button_html` | play button HTML |
| `tribe_embed_video_thumb_classes` | thumbnail CSS classes (`array`) |
| `tribe_embed_video_thumb_markup` | thumbnail `<img>` HTML |
| `tribe_embed_video_embed_template` | the `<template>` with the original embed; also receives `$provider` last |
| `tribe_embed_video_embed_inline` | the original embed rendered inside the wrapper (inline providers); also receives `$provider` last |

```php
add_filter( 'tribe_embed_video_wrapper_classes', function ( array $classes, array $block ) {
    $classes[] = 'my-video';
    return $classes;
}, 10, 2 );
```

## Thumbnail attributes

| Filter | Default | Purpose |
|---|---|---|
| `tribe_embed_thumbnail_sizes( $sizes, $max_width, $block, $video_id )` | `(max-width: Wpx) 100vw,Wpx` | The `sizes` attribute. The default assumes a full-width image; return something smaller for narrow layouts. |
| `tribe_embed_thumbnail_loading( $loading, $block, $video_id )` | `lazy` | Return `eager` for an embed near the top of the page. |
| `tribe_embed_thumbnail_fetchpriority( $priority, $block, $video_id )` | `auto` | Return `high` for the likely LCP image (`high`, `low` or `auto`). |

```php
// Treat every embed as above the fold.
add_filter( 'tribe_embed_thumbnail_loading', fn () => 'eager' );
add_filter( 'tribe_embed_thumbnail_fetchpriority', fn () => 'high' );
```

## Templates

### `tribe_embed_template_dirs`

Extra directories searched for templates, after the theme and before the plugin default.

```php
apply_filters( 'tribe_embed_template_dirs', array $dirs, string $name );
```

### `tribe_embed_template_path`

The resolved template file (empty when none was found).

```php
apply_filters( 'tribe_embed_template_path', string $path, string $name );
```

`$name` is the template name relative to the templates folder without extension, e.g. `parts/thumbnail`.

## Renamed hooks (legacy fallback)

Before 2.1.0 these filters were called `tribe_embeds_*`. They **still work under the old name**: callbacks attached to a legacy name run first, with the same arguments, and with `WP_DEBUG` on WordPress logs a deprecation notice naming the replacement. Nothing extra runs when nothing is attached to an old name. Move your callbacks to the new names; the fallback will be removed in a future minor release.

| Legacy name | Current name |
|---|---|
| `tribe_embeds_allowed_provider_hosts`, `…_{slug}` | `tribe_embed_allowed_provider_hosts`, `…_{slug}` |
| `tribe_embeds_image_sizes`, `…_{slug}` | `tribe_embed_image_sizes`, `…_{slug}` |
| `tribe_embeds_provider_classes` | `tribe_embed_provider_classes` |
| `tribe_embeds_video_provider` | `tribe_embed_video_provider` |
| `tribe_embeds_facade_html` | `tribe_embed_facade_html` |
| `tribe_embeds_thumbnail_data` | `tribe_embed_thumbnail_data` |
| `tribe_embeds_video_button_html` | `tribe_embed_video_button_html` |
| `tribe_embeds_video_embed_template` | `tribe_embed_video_embed_template` |
| `tribe_embeds_video_thumb_classes` | `tribe_embed_video_thumb_classes` |
| `tribe_embeds_video_thumb_markup` | `tribe_embed_video_thumb_markup` |
| `tribe_embeds_video_wrapper_classes` | `tribe_embed_video_wrapper_classes` |

The stored API tokens moved from the `tribe_embeds_vimeo_access_token` / `tribe_embeds_wistia_api_key` options to `tribe_embed_vimeo_access_token` / `tribe_embed_wistia_api_key`. Existing values keep being read until the settings are saved again. The `VIMEO_ACCESS_TOKEN` and `WISTIA_API_KEY` constants are unchanged.

## Deprecated

- `tribe_embeds_video_thumb_open_markup` and `tribe_embeds_video_thumb_close_markup` (since 2.1.0): the wrapper is now one template. They still run when hooked, but override [`facade.php`](templates.md) instead.
- `tribe_embeds_video_embed_template` for inline providers: use `tribe_embed_video_embed_inline`.
