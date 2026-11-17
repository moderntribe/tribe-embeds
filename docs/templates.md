# Templates

The facade markup lives in the plugin's `templates/` folder, split into chunks:

| Template | Purpose |
|---|---|
| `facade.php` | Wrapper `<figure>`; composes the parts below, plus the caption and no-JS link |
| `parts/play-button.php` | Play button |
| `parts/thumbnail.php` | Thumbnail `<img>` with `srcset` / `sizes` |
| `parts/embed-template.php` | `<template>` holding the original embed (most providers) |
| `parts/embed-inline.php` | Original embed rendered inside the wrapper (inline providers) |

Each template documents the variables it receives (as `$args`) in its header.

## Overriding templates

Copy a template into a `tribe-embed/` folder in your (child) theme, keeping the same relative path:

```
your-theme/tribe-embed/facade.php
your-theme/tribe-embed/parts/thumbnail.php
```

Lookup order, first match wins:

1. child theme, then parent theme: `{theme}/tribe-embed/{name}.php`
2. directories added with the [`tribe_embed_template_dirs`](hooks.md#templates) filter
3. the plugin's own `templates/` folder

To ship overrides from another plugin:

```php
add_filter( 'tribe_embed_template_dirs', function ( array $dirs ) {
    $dirs[] = __DIR__ . '/tribe-embed-templates';
    return $dirs;
} );
```

[`tribe_embed_template_path`](hooks.md#templates) can replace the final resolved file.

## Rules for overrides

- Keep the `.tribe-embed`, `.tribe-embed__thumbnail` and `.play-button` classes, and keep the `<template>` as the next sibling of the figure: `assets/js/embed.js` depends on them.
- Templates only escape and output. The data (classes, `srcset`, sanitized embed HTML, labels) is prepared in PHP, so an override never has to re-implement logic.
- Whitespace containing a newline or tab between tags is removed from the output, so content filters such as `wpautop` don't add stray `<br>` tags.
- The output of each chunk still passes through its [filter](hooks.md#markup-chunks), after the template is rendered.

## Styling

Front-end styles are in `assets/css/style.pcss`. The play button is customised with CSS custom properties defined in `assets/css/variables.pcss` (size, colors, opacity, transition, icon size, focus ring), which you can override from your theme.
