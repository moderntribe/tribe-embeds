<?php
/**
 * Original embed markup rendered directly inside the facade wrapper.
 * Used by providers where Provider::uses_inline_embed() is true.
 *
 * Override by copying to `{theme}/tribe-embed/parts/embed-inline.php`.
 *
 * @var array $args {
 *     @type string $video_id   Provider video ID.
 *     @type string $embed_html Sanitized original block HTML (already escaped).
 *     @type array  $block      The core/embed block array.
 * }
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo $args['embed_html'];
