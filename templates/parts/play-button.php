<?php
/**
 * Play button.
 *
 * Override by copying to `{theme}/tribe-embed/parts/play-button.php`.
 * Keep the `play-button` class: the click handler in assets/js/embed.js binds to it.
 *
 * @var array $args {
 *     @type string   $label           Accessible name (includes the caption when there is one).
 *     @type string[] $wrapper_classes CSS classes of the wrapper.
 *     @type string   $video_id        Provider video ID.
 *     @type array    $block           The core/embed block array.
 *     @type array    $thumbnail_data  Thumbnail data keyed by size.
 * }
 */

?>
<button
	type="button"
	class="play-button"
	aria-label="<?php echo esc_attr( $args['label'] ); ?>"
></button>
