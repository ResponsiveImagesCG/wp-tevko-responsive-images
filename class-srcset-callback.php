<?php

class WP_Ricg_Content_Filter {
	private $attachments_loop;
	private $attachments_array;
	
	function __construct( $attachments_loop ) {
		$this->attachments_loop = $attachments_loop;
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['baseurl'] . '/';
		
		while( $attachments_loop->have_posts() ) :
			$attachments_loop->the_post();
			
			// Get the image meta data.
			if ( is_array( $img_meta = wp_get_attachment_metadata( get_the_ID() ) ) ) {
				
				// Get base URL.
				$base_url = $upload_dir . pathinfo( $img_meta['file'], PATHINFO_DIRNAME );
				$base_url = untrailingslashit( $base_url ) . '/';
				
				// Add default/full size image.
				$this->attachments_array[ $upload_dir . $img_meta['file'] ] = get_the_ID();
				
				// Build from sizes array.
				$img_sizes = $img_meta['sizes'];

				foreach( $img_sizes as $img_size ) {
					$this->attachments_array[ $base_url . $img_size['file'] ] = get_the_ID();
				}
			}
		endwhile;

		wp_reset_postdata();
	}
	
	/**
	 * Callback function for tevkori_filter_content_images.
	 *
	 * @since 3.0
	 *
	 * @see tevkori_filter_content_images
	 * @param array $matches Array containing the regular expression matches.
	 */
	public function callback( $matches ) {
		$atts = $matches[1];
		$sizes = $srcset = '';
	
		// Check if srcset attribute is not already present.
		if ( false !== strpos( 'srcset="', $atts ) ) {

			// Get the url of the original image.
			preg_match( '/src="(.+?)(\-([0-9]+)x([0-9]+))?(\.[a-zA-Z]{3,4})"/i', $atts, $url_matches );
		
			$url = $url_matches[1] . $url_matches[5];
		
			// Get the image ID.
			$id = $this->attachments_array[$url];

			if ( $id ) {

				// Use the width and height from the image url.
				if ( $url_matches[3] && $url_matches[4] ) {
					$size = array(
						(int) $url_matches[3],
						(int) $url_matches[4]
					);
				} else {
					$size = 'full';
				}

				// Get the srcset string.
				$srcset_string = tevkori_get_srcset_string( $id, $size );

				if ( $srcset_string ) {
					$srcset = ' ' . $srcset_string;

					// Get the sizes string.
					$sizes_string = tevkori_get_sizes_string( $id, $size );

					if ( $sizes_string && ! preg_match( '/sizes="([^"]+)"/i', $atts ) ) {
						$sizes = ' ' . $sizes_string;
					}
				}
			}
		}
	
		return '<img ' . $atts . $sizes . $srcset . '>';
	}
}