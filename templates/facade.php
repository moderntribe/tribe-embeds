<?php
/**
 * Facade wrapper: the figure holding the play button, thumbnail and (inline providers) the embed.
 *
 * Override by copying to `{theme}/tribe-embed/facade.php`.
 * The JS (assets/js/embed.js) relies on `.tribe-embed`, `.tribe-embed__thumbnail`, `.play-button`
 * and on the `<template>` (`$args['after']`) being the next sibling of the figure.
 *
 * @var array $args {
 *     @type string[] $wrapper_classes CSS classes for the figure.
 *     @type string   $video_id        Provider video ID.
 *     @type array    $block           The core/embed block array.
 *     @type array    $thumbnail_data  Thumbnail data keyed by size.
 *     @type string   $inner           Pre-rendered, trusted HTML for inside the wrapper.
 *     @type string   $after           Pre-rendered, trusted HTML placed after the figure.
 *     @type string   $caption_html    Sanitized caption HTML of the original block
 *                                     (empty for inline providers or when there is no caption).
 *     @type string   $video_url       The URL of the video, used for the no-JS fallback link.
 * }
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<figure
	class="<?php echo esc_attr( implode( ' ', $args['wrapper_classes'] ) ); ?>"
	data-id="<?php echo esc_attr( $args['video_id'] ); ?>"
>
	<div class="tribe-embed__inner">
		<?php echo $args['inner']; ?>
	</div>

	<?php if ( $args['caption_html'] !== '' ) : ?>
		<figcaption class="wp-element-caption"><?php echo $args['caption_html']; ?></figcaption>
	<?php endif; ?>

	<?php if ( $args['video_url'] !== '' ) : ?>
		<noscript>
			<a href="<?php echo esc_url( $args['video_url'] ); ?>"><?php esc_html_e( 'Watch video', 'tribe-embeds' ); ?></a>
		</noscript>
	<?php endif; ?>
</figure>
<?php echo $args['after']; ?>
