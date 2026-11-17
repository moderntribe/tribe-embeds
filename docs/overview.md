# Overview

## What it does

The plugin replaces the video player in the `core/embed` block with a *facade*: a thumbnail with a play button. The real player is only loaded when the visitor clicks. This improves initial page load, and YouTube embeds load from the no-cookie domain (`youtube-nocookie.com`).

## How it works

The plugin filters the rendered `core/embed` block (`render_block`):

1. The block URL is parsed and matched to a provider (see [Providers](providers.md)).
2. The provider returns several thumbnail sizes, which are cached.
3. The facade is rendered from the [templates](templates.md): a wrapper, a play button and a thumbnail with `srcset`, `sizes`, `width` and `height`. Only sizes with the same aspect ratio as the largest image go into `srcset`.
4. The original embed markup is kept in a `<template>` element after the facade (or inside it, for providers that need it). On click, `assets/js/embed.js` swaps the facade for the real player and adds autoplay.

The block's caption is rendered on the server under the facade, and the play button's accessible name includes it.

## Behavior to know about

- URLs that are not a supported video, or have no thumbnail, are left exactly as WordPress renders them.
- Feeds keep the original embed markup (a feed reader cannot run the play script).
- Without JavaScript the facade shows a "Watch video" link to the video URL.
- The script and stylesheet load only on pages that contain a facade.
- Facades added after page load (client-side navigation, AJAX) are picked up automatically.

## Limitations

Only the [supported providers](providers.md) are handled, and the URL must match one of their formats.
