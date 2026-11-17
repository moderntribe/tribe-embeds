# Block editor preview

In the editor, supported video URLs are shown as the same facade visitors see (thumbnail and play button, non-interactive) instead of the live player.

- The facade is rendered by the REST route `GET /wp-json/tribe-embed/v1/facade?url=…` (requires the `edit_posts` capability). It uses the same PHP and [templates](templates.md) as the front end, so theme overrides and the [`tribe_embed_facade_html`](hooks.md#markup) filter apply.
- Click the block to select it. The caption is edited in place.
- **Edit embed** in the block toolbar switches to the core embed controls (change URL, etc.). The block returns to the facade when it loses selection.
- URLs that are not supported videos use the normal core preview.
- Front-end styles and `editor.css` are loaded into the editor content iframe, so the preview looks like the front end.
