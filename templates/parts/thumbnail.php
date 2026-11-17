<?php
/**
 * Thumbnail image.
 *
 * Override by copying to `{theme}/tribe-embed/parts/thumbnail.php`.
 * Keep the `tribe-embed__thumbnail` class (passed in `$args['classes']`): the click handler in assets/js/embed.js binds to it.
 *
 * @var array $args {
 *     @type string   $url           Largest thumbnail URL.
 *     @type int      $width         Largest thumbnail width.
 *     @type int      $height        Largest thumbnail height.
 *     @type string[] $classes       CSS classes for the image.
 *     @type string   $srcset        Ready-to-use srcset value.
 *     @type string   $sizes         Ready-to-use sizes value.
 *     @type string   $loading       `lazy` or `eager`.
 *     @type string   $fetchpriority `high`, `low` or `auto` (the browser decides).
 *     @type string   $video_id      Provider video ID.
 *     @type array    $block         The core/embed block array.
 * }
 */

?>
<img
	loading="<?php echo esc_attr( $args['loading'] ); ?>"
	decoding="async"
	fetchpriority="<?php echo esc_attr( $args['fetchpriority'] ); ?>"
	width="<?php echo esc_attr( (string) $args['width'] ); ?>"
	height="<?php echo esc_attr( (string) $args['height'] ); ?>"
	class="<?php echo esc_attr( implode( ' ', $args['classes'] ) ); ?>"
	alt=""
	src="<?php echo esc_url( $args['url'] ); ?>"
	srcset="<?php echo esc_attr( $args['srcset'] ); ?>"
	sizes="<?php echo esc_attr( $args['sizes'] ); ?>"
/>
