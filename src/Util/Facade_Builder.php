<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

/**
 * Builds HTML facade <img> for video embeds.
 */
final class Facade_Builder {

	/**
	 * Build facade image HTML.
	 *
	 * @param array<string,mixed> $thumb
	 * @param array<string,mixed> $block
	 * @param string $video_id
	 */
	public function build( array $thumb, array $block, string $video_id ): string {
		$wrapper_classes = $this->get_wrapper_classes( $block, $video_id, $thumb );

		$content  = $this->open_markup_figure_element( $block, $video_id, $thumb, $wrapper_classes );
		$content .= $this->add_video_play_button( $block, $video_id, $thumb, $wrapper_classes );
		$content .= $this->add_video_thumbnail_markup( $block, $video_id, $thumb, $wrapper_classes );
		$content .= $this->close_markup_figure_element( $block, $video_id, $thumb, $wrapper_classes );
		$content .= $this->add_original_embed_template( $block, $video_id, $thumb, $wrapper_classes );

		return $content;
	}

	/**
	 * Get block wrapper classes
	 *
	 * @param array  $block
	 * @param string $video_id
	 * @param array  $thumbnail_data
	 */
	public function get_wrapper_classes( array $block, string $video_id, array $thumbnail_data ): array {
		$wrapper_classes = [
			'wp-block-image',
			'tribe-embed',
			'is--' . $block['attrs']['providerNameSlug'],
		];

		// if we have classNames on the embed block.
		if ( ! empty( $block['attrs']['className'] ) ) {
			// explode the className string into array.
			$class_names = explode( ' ', $block['attrs']['className'] );

			// merge the class names into the figures classes array.
			$wrapper_classes = array_merge( $wrapper_classes, $class_names );
		}

		// if the embed block has an alignment.
		if ( ! empty( $block['attrs']['align'] ) ) {
			// add the alignment class to the figure classes.
			$wrapper_classes[] = 'align' . $block['attrs']['align'];
		}

		// allow the classes to be filtered.
		return apply_filters( 'tribe_embeds_video_wrapper_classes', $wrapper_classes, $block, $video_id, $thumbnail_data );
	}

	/**
	 * Adds the play button div to the markup.
	 *
	 * @param array  $block
	 * @param string $video_id
	 * @param array  $thumbnail_data
	 * @param array  $wrapper_classes
	 */
	public function add_video_play_button( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): string {
		$button = sprintf( '<button class="play-button" aria-label="%s"></button>', esc_html__( 'Play Video', 'tribe' ) );

		return apply_filters( 'tribe_embeds_video_button_html', $button, $block, $video_id, $thumbnail_data, $wrapper_classes );
	}

	/**
	 * Adds the video thumbnail markup output.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function add_video_thumbnail_markup( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): string {

		$max_res_image = end( $thumbnail_data );
		$srcset        = [];
		$sizes         = [ '(max-width: ' . $max_res_image['width'] . 'px) 100vw', $max_res_image['width'] . 'px' ];

		foreach ( $thumbnail_data as $data ) {
			$srcset[] = $data['url'] . ' ' . $data['width'] . 'w';
		}

		$image_tag = sprintf(
			'<img loading="lazy" width="%s" height="%s" class="tribe-embed__thumbnail" alt="" src="%s" srcset="%s" sizes="%s" />',
			$max_res_image['width'],
			$max_res_image['height'],
			$max_res_image['url'],
			implode( ',', $srcset ),
			implode( ',', $sizes )
		);

		return apply_filters( 'tribe_embeds_video_thumb_markup', $image_tag, $block, $video_id, $thumbnail_data, $wrapper_classes );
	}

	/**
	 * Adds the closing figure element to the thumbnail markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function close_markup_figure_element( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): string {
		$html = '</div></figure>';

		return apply_filters( 'tribe_embeds_video_thumb_close_markup', $html, $block, $video_id, $thumbnail_data, $wrapper_classes );
	}

	/**
	 * Adds the opening figure element to the thumbnail markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function open_markup_figure_element( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): string {
		$html = sprintf(
			'<figure class="%s" data-id="%s"><div class="tribe-embed__inner">',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( $video_id )
		);

		return apply_filters( 'tribe_embeds_video_thumb_open_markup', $html, $block, $video_id, $thumbnail_data, $wrapper_classes );
	}

	/**
	 * Creates a escaping function to allowed certain HTML for embed content.
	 * Needed for when echoing the innerblock HTML.
	 */
	public function allowed_innerblock_html(): array {
		/**
		 * Return the allowed html
		 * These are the elements in the rendered embed block for supported videos.
		 * This also includes everything you can add to an embed caption.
		 * Therefore, we need to allow these to keep the same structure.
		 */
		return [
			'iframe'     => [
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
			],
			'figure'     => [
				'class' => true,
			],
			'figcaption' => [
				'class' => true,
			],
			'div'        => [
				'class' => true,
			],
			'a'          => [
				'class'     => true,
				'href'      => true,
				'data-type' => true,
			],
			'strong'     => [],
			'em'         => [],
			'sub'        => [],
			'sup'        => [],
			's'          => [],
			'kbd'        => [],
			'img'        => [
				'class' => true,
				'style' => true,
				'src'   => true,
				'alt'   => true,
			],
			'code'       => [],
			'mark'       => [
				'style' => true,
				'class' => true,
			],
		];
	}

	/**
	 * Adds the original block markup to the template element.
	 * This is used when the item is cloned when the thumbnail is clicked.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function add_original_embed_template( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): string {
		$html = sprintf( '<template id="tribe-embed-embed-html-%s">%s</template>', esc_attr( $video_id ), wp_kses( $block['innerHTML'], $this->allowed_innerblock_html() ) );

		return apply_filters( 'tribe_embeds_video_embed_template', $html, $block, $video_id, $thumbnail_data, $wrapper_classes );
	}

}
