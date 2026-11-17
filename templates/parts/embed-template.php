<?php
/**
 * Original embed markup stored in a <template>, cloned into the page when the thumbnail is clicked.
 * Used by providers where Provider::uses_inline_embed() is false.
 *
 * Override by copying to `{theme}/tribe-embed/parts/embed-template.php`.
 * The element must remain the next sibling of the facade figure (see facade.php).
 *
 * @var array $args {
 *     @type string $video_id   Provider video ID.
 *     @type string $embed_html Sanitized original block HTML (already escaped).
 *     @type array  $block      The core/embed block array.
 * }
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<template id="tribe-embed-embed-html-<?php echo esc_attr( $args['video_id'] ); ?>">
	<?php echo $args['embed_html']; ?>
</template>
