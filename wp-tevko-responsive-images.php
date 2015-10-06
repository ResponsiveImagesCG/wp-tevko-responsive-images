<?php
/**
 * @link              https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images
 * @since             2.0.0
 * @package           http://www.smashingmagazine.com/2015/02/24/ricg-responsive-images-for-wordpress/
 *
 * @wordpress-plugin
 * Plugin Name:       RICG Responsive Images
 * Plugin URI:        http://www.smashingmagazine.com/2015/02/24/ricg-responsive-images-for-wordpress/
 * Description:       Bringing automatic default responsive images to wordpress
 * Version:           2.5.2
 * Author:            The RICG
 * Author URI:        http://responsiveimages.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Don't load the plugin directly.
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

// List includes.
if ( class_exists( 'Imagick' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'class-respimg.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'class-wp-image-editor-respimg.php' );

	/**
	 * Filter to add php-respimg as an image editor.
	 *
	 * @since 2.3.0
	 *
	 * @return array Editors.
	 **/
	function tevkori_wp_image_editors( $editors ) {
		if ( current_theme_supports( 'advanced-image-compression' ) ) {
			array_unshift( $editors, 'WP_Image_Editor_Respimg' );
		}

		return $editors;
	}
	add_filter( 'wp_image_editors', 'tevkori_wp_image_editors' );
}

/**
 * Enqueue bundled version of the Picturefill library.
 */
function tevkori_get_picturefill() {
	wp_enqueue_script( 'picturefill', plugins_url( 'js/picturefill.min.js', __FILE__ ), array(), '3.0.1', true );
}
add_action( 'wp_enqueue_scripts', 'tevkori_get_picturefill' );

/**
 * Return a source size attribute for an image from an array of values.
 *
 * @since 2.2.0
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'medium'.
 * @param array  $args {
 *     Optional. Arguments to retrieve posts.
 *
 *     @type array|string $sizes An array or string containing of size information.
 *     @type int          $width A single width value used in the default `sizes` string.
 * }
 * @param array  $img  Optional. The array returned by 'image_get_intermediate_size'.
 * @return string|bool A valid source size value for use in a 'sizes' attribute or false.
 */
function tevkori_get_sizes( $id, $size = 'medium', $args = null, $img = false ) {
	// Try to get the image width from `$args` first.
	if ( is_array( $args ) && ! empty( $args['width'] ) ) {
		$img_width = (int) $args['width'];
	} else {
		$image = ( $img ) ? $img : image_get_intermediate_size( $id, $size );
		list( $img_width, $img_height ) = image_constrain_size_for_editor( $image['width'], $image['height'], $size );
	}

	// Bail early if `$image_width` isn't set.
	if ( ! $img_width ) {
		return false;
	}

	// Set the image width in pixels.
	$img_width = $img_width . 'px';

	// Set up our default values.
	$defaults = array(
		'sizes' => array(
			array(
				'size_value' => '100vw',
				'mq_value'   => $img_width,
				'mq_name'    => 'max-width'
			),
			array(
				'size_value' => $img_width
			),
		)
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	* Filter arguments used to create 'sizes' attribute.
	*
	* @since 2.4.0
	*
	* @param array   $args  An array of arguments used to create a 'sizes' attribute.
	* @param int     $id    Post ID of the original image.
	* @param string  $size  Name of the image size being used.
	*/
	$args = apply_filters( 'tevkori_image_sizes_args', $args, $id, $size );

	// If sizes is passed as a string, just use the string.
	if ( is_string( $args['sizes'] ) ) {
		$size_list = $args['sizes'];

	// Otherwise, breakdown the array and build a sizes string.
	} elseif ( is_array( $args['sizes'] ) ) {

		$size_list = '';

		foreach ( $args['sizes'] as $size ) {

			// Use 100vw as the size value unless something else is specified.
			$size_value = ( $size['size_value'] ) ? $size['size_value'] : '100vw';

			// If a media length is specified, build the media query.
			if ( ! empty( $size['mq_value'] ) ) {

				$media_length = $size['mq_value'];

				// Use max-width as the media condition unless min-width is specified.
				$media_condition = ( ! empty( $size['mq_name'] ) ) ? $size['mq_name'] : 'max-width';

				// If a media_length was set, create the media query.
				$media_query = '(' . $media_condition . ": " . $media_length . ') ';

			} else {

				// If not meda length was set, $media_query is blank.
				$media_query = '';
			}

			// Add to the source size list string.
			$size_list .= $media_query . $size_value . ', ';
		}

		// Remove the trailing comma and space from the end of the string.
		$size_list = substr( $size_list, 0, -2 );
	}

	// If $size_list is defined set the string, otherwise set false.
	return ( $size_list ) ? $size_list : false;
}

/**
 * Return a source size list for an image from an array of values.
 *
 * @since 2.2.0
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'medium'.
 * @param array  $args {
 *     Optional. Arguments to retrieve posts.
 *
 *     @type array|string $sizes An array or string containing of size information.
 *     @type int          $width A single width value used in the default `sizes` string.
 * }
 * @param array  $img  Optional. The array returned by 'image_get_intermediate_size'.
 * @return string|bool A valid source size list as a 'sizes' attribute or false.
 */
function tevkori_get_sizes_string( $id, $size = 'medium', $args = null, $img = false ) {
	$sizes = tevkori_get_sizes( $id, $size, $args, $img );

	return $sizes ? 'sizes="' . $sizes . '"' : false;
}

/**
 * Get an array of image sources candidates for use in a 'srcset' attribute.
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'medium'.
 * @param array  $img  Optional. The array returned by 'image_get_intermediate_size'.
 * @param array  $meta Optional. The array returned by 'wp_get_attachment_metadata'.
 * @return array|bool  An array of `srcset` values or false.
 */
function tevkori_get_srcset_array( $id, $size = 'medium', $img = false, $meta = false ) {
	$arr = array();

	// Get the intermediate size.
	$image = ( $img ) ? $img : image_get_intermediate_size( $id, $size );
	// Get the post meta.
	if ( ! is_array( $img_meta = ( $meta ) ? $meta : wp_get_attachment_metadata( $id ) ) ) {
		return false;
	}

	// Extract the height and width from the intermediate or the full size.
	$img_width = ( $image ) ? $image['width'] : $img_meta['width'];
	$img_height = ( $image ) ? $image['height'] : $img_meta['height'];

	// Bail early if the width isn't greater that zero.
	if ( ! $img_width > 0 ) {
		return false;
	}

	// Use the url from the intermediate size or build the url from the metadata.
	if ( ! empty( $image['url'] ) ) {
		$img_url = $image['url'];
	} else {
		$uploads_dir = wp_upload_dir();
		$img_file = ( $image ) ? path_join( dirname( $img_meta['file'] ) , $image['file'] ) : $img_meta['file'];
		$img_url = $uploads_dir['baseurl'] . '/' . $img_file;
	}

	$img_sizes = $img_meta['sizes'];

	// Add full size to the img_sizes array.
	$img_sizes['full'] = array(
		'width'  => $img_meta['width'],
		'height' => $img_meta['height'],
		'file'   => wp_basename( $img_meta['file'] )
	);

	// Calculate the image aspect ratio.
	$img_ratio = $img_height / $img_width;

	/*
	 * Images that have been edited in WordPress after being uploaded will
	 * contain a unique hash. Look for that hash and use it later to filter
	 * out images that are leftovers from previous versions.
	 */
	$img_edited = preg_match( '/-e[0-9]{13}/', $img_url, $img_edit_hash );

	/*
	 * Loop through available images and only use images that are resized
	 * versions of the same rendition.
	 */
	foreach ( $img_sizes as $img_size ) {

		// Filter out images that are leftovers from previous renditions.
		if ( $img_edited && ! strpos( $img_size['file'], $img_edit_hash[0] ) ) {
			continue;
		}

		// Calculate the new image ratio.
		$img_ratio_compare = $img_size['height'] / $img_size['width'];

		// If the new ratio differs by less than 0.01, use it.
		if ( abs( $img_ratio - $img_ratio_compare ) < 0.01 ) {
			$arr[ $img_size['width'] ] = path_join( dirname( $img_url ), $img_size['file'] ) . ' ' . $img_size['width'] .'w';
		}
	}

	/**
	 * Filter the output of tevkori_get_srcset_array().
	 *
	 * @since 2.4.0
	 *
	 * @param array        $arr   An array of image sources.
	 * @param int          $id    Attachment ID for image.
	 * @param array|string $size  Size of image, either array or string.
	 */
	return apply_filters( 'tevkori_srcset_array', $arr, $id, $size );
}

/**
 * Get the value for the 'srcset' attribute.
 *
 * @since 2.3.0
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'medium'.
 * @param array  $img  Optional. The array returned by 'image_get_intermediate_size'.
 * @param array  $meta Optional. The array returned by 'wp_get_attachment_metadata'.
 * @return string|bool A 'srcset' value string or false.
 */
function tevkori_get_srcset( $id, $size = 'medium', $img = false, $meta = false ) {
	$srcset_array = tevkori_get_srcset_array( $id, $size, $img, $meta );

	if ( count( $srcset_array ) <= 1 ) {
		return false;
	}

	return implode( ', ', $srcset_array );
}

/**
 * Create a 'srcset' attribute.
 *
 * @since 2.1.0
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'medium'.
 * @param array  $img  Optional. The array returned by 'image_get_intermediate_size'.
 * @param array  $meta Optional. The array returned by 'wp_get_attachment_metadata'.
 * @return string|bool A full 'srcset' string or false.
 */
function tevkori_get_srcset_string( $id, $size = 'medium', $img = false, $meta = false ) {
	$srcset_value = tevkori_get_srcset( $id, $size, $img, $meta );

	if ( empty( $srcset_value ) ) {
		return false;
	}

	return 'srcset="' . $srcset_value . '"';
}

/**
 * Filters images in post content to add 'srcset' and 'sizes'.
 *
 * @since 2.5.0
 *
 * @param string $content The raw post content to be filtered.
 * @return string Converted content with 'srcset' and 'sizes' added to images.
 */
function tevkori_filter_content_images( $content ) {
	if ( ! preg_match_all( '/<img [^>]+ wp-image-([0-9]+)[^>]+>/i', $content, $matches ) ) {
		return $content;
	}

	$images = $matches[0];

	$attachment_ids = array_unique( $matches[1] );

	if ( 0 < count( $attachment_ids ) ) {
		/*
		 * Warm object caches for use with wp_get_attachment_metadata.
		 *
		 * To avoid making a database call for each image, a single query
		 * warms the object cache with the meta information for all images.
		 */
		_prime_post_caches( $attachment_ids, false, true );
	}

	foreach( $images as $image ) {
		$content = str_replace( $image, tevkori_img_add_srcset_and_sizes( $image ), $content );
	}

	return $content;
}
add_filter( 'the_content', 'tevkori_filter_content_images', 5, 1 );

/**
 * Add srcset and sizes to an 'img' element.
 *
 * @since 2.6.0
 *
 * @param string $image An HTML 'img' element to be filtered.
 * @return string Converted 'img' element with 'srcset' and 'sizes' added.
 */
function tevkori_img_add_srcset_and_sizes( $image ) {
	// Return early if a 'srcset' attribute already exists.
	if ( false !== strpos( $image, ' srcset="' ) ) {
		/*
		 * Backward compatibility.
		 *
		 * Prior to version 2.5 a 'srcset' and 'data-sizes' attribute
		 * were added to the image while inserting the image in the content.
		 * We replace the 'data-sizes' attribute by a 'sizes' attribute.
		 */
		$image = str_replace( ' data-sizes="', ' sizes="', $image );

		return $image;
	}

	// Parse id, size, width, and height from the 'img' element.
	$id = preg_match( '/wp-image-([0-9]+)/i', $image, $match_id ) ? (int) $match_id[1] : false;
	$size = preg_match( '/size-([^\s|"]+)/i', $image, $match_size ) ? $match_size[1] : false;
	$width = preg_match( '/ width="([0-9]+)"/', $image, $match_width ) ? (int) $match_width[1] : false;
	$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height ) ? (int) $match_height[1] : false;

	if ( $id && false === $size ) {
		$size = array(
			$width,
			$height,
		);
	}

	/*
	 * If attempts to parse the size value failed, attempt to use the image
	 * metadata to match the 'src' against the available sizes for an attachment.
	 */
	$meta = false;
	
	if ( ! $size && ! empty( $id ) && is_array( $meta = wp_get_attachment_metadata( $id ) ) ) {
		// Parse the image 'src' value from the 'img' element.
		$src = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : false;

		// Return early if the 'src' value is empty.
		if ( ! $src ) {
			return $image;
		}

		/*
		 * First, see if the file is the full size image. If not, we loop through
		 * the intermediate sizes until we find a file that matches.
		 */
		$image_filename = wp_basename( $src );

		if ( $image_filename === basename( $meta['file'] ) ) {
			$size = 'full';
		} else {
			foreach( $meta['sizes'] as $image_size => $image_size_data ) {
				if ( $image_filename === $image_size_data['file'] ) {
					$size = $image_size;
					break;
				}
			}
		}

	}

	// If we have an ID and size, try for 'srcset' and 'sizes' and update the markup.
	if ( $id && $size ) {
		// Get the intermediate size.
		$img = image_get_intermediate_size( $id, $size );
		
		if ( $img ) {
			$srcset = tevkori_get_srcset( $id, $size, $img, $meta );

			/**
			 * Pass the 'height' and 'width' to 'wp_get_attachment_image_sizes()' to avoid
			 * recalculating the image size.
			 */
			$args = array(
				'height' => $height,
				'width'  => $width,
			);
			$sizes = tevkori_get_sizes( $id, $size, $args, $img );

			// Format the srcset and sizes string and escape attributes.
			$srcset_and_sizes = sprintf( ' srcset="%s" sizes="%s"', esc_attr( $srcset ), esc_attr( $sizes) );

			// Add srcset and sizes attributes to the image markup.
			$image = preg_replace( '/<img ([^>]+)[\s?][\/?]>/', '<img $1' . $srcset_and_sizes . ' />', $image );
		}
	};

	return $image;
}

/**
 * Filter to add 'srcset' and 'sizes' attributes to post thumbnails and gallery images.
 *
 * @see wp_get_attachment_image_attributes
 * @return array Attributes for image.
 */
function tevkori_filter_attachment_image_attributes( $attr, $attachment, $size ) {
	if ( ! isset( $attr['srcset'] ) ) {
		$id = $attachment->ID;
		
		// Get the intermediate size.
		$img = image_get_intermediate_size( $id, $size );
		
		if ( $img ) {
			$srcset = tevkori_get_srcset( $id, $size, $img );

			// Set the 'srcset' attribute if one was returned.
			if ( $srcset ) {
				$attr['srcset'] = $srcset;

				if ( ! isset( $attr['sizes'] ) ) {
					$sizes = tevkori_get_sizes( $id, $size, $img );

					// Set the 'sizes' attribute if sizes were returned.
					if ( $sizes ) {
						$attr['sizes'] = $sizes;
					}
				}
			}
		}
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'tevkori_filter_attachment_image_attributes', 0, 3 );
