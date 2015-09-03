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
 * Version:           2.4.0
 * Author:            The RICG
 * Author URI:        http://responsiveimages.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Don't load the plugin directly
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

// List includes
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
	wp_enqueue_script( 'picturefill', plugins_url( 'js/picturefill.min.js', __FILE__ ), array(), '2.3.1', true );
}
add_action( 'wp_enqueue_scripts', 'tevkori_get_picturefill' );

/**
 * Return a source size attribute for an image from an array of values.
 *
 * @since 2.2.0
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'thumbnail'.
 * @param array  $args {
 *     Optional. Arguments to retrieve posts.
 *
 *     @type array|string $sizes An array or string containing of size information.
 * }
 * @return string|bool A valid source size value for use in a 'sizes' attribute or false.
 */
function tevkori_get_sizes( $id, $size = 'thumbnail', $args = null ) {

	// See which image is being returned and bail if none is found.
	if ( ! $img = image_downsize( $id, $size ) ) {
		return false;
	}

	// Get the image width.
	$img_width = $img[1] . 'px';

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
	* Filter arguments used to create sizes attribute.
	*
	* @since 2.4.0
	*
	* @param array   $args  An array of arguments used to create a sizes attribute.
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
 * @param string $size Optional. Name of image size. Default value: 'thumbnail'.
 * @param array  $args {
 *     Optional. Arguments to retrieve posts.
 *
 *     @type array|string $sizes An array or string containing of size information.
 * }
 * @return string|bool A valid source size list as a 'sizes' attribute or false.
 */
function tevkori_get_sizes_string( $id, $size = 'thumbnail', $args = null ) {
	$sizes = tevkori_get_sizes( $id, $size, $args );
	return $sizes ? 'sizes="' . $sizes . '"' : false;
}

/**
 * Get an array of image sources candidates for use in a 'srcset' attribute.
 *
 * @param int    $id   Image attachment ID.
 * @param string $size Optional. Name of image size. Default value: 'thumbnail'.
 * @return array|bool  An array of of srcset values or false.
 */
function tevkori_get_srcset_array( $id, $size = 'thumbnail' ) {
	$arr = array();

	// See which image is being returned and bail if none is found.
	if ( ! $img = wp_get_attachment_image_src( $id, $size ) ) {
		return false;
	}

	// Break image data into url, width, and height.
	list( $img_url, $img_width, $img_height ) = $img;

	// If we have no width to work with, we should bail (see issue #118).
	if ( 0 == $img_width ) {
		return false;
	}

	// Get the image meta data and bail if none is found.
	if ( ! is_array( $img_meta = wp_get_attachment_metadata( $id ) ) ) {
		return false;
	}

	// Build an array with image sizes.
	$img_sizes = $img_meta['sizes'];

	// Add full size to the img_sizes array.
	$img_sizes['full'] = array(
		'width'  => $img_meta['width'],
		'height' => $img_meta['height'],
		'file'   => $img_meta['file']
	);

	if ( strrpos( $img_meta['file'], '/' ) !== false ) {
		$img_sizes['full']['file'] = substr( $img_meta['file'], strrpos( $img_meta['file'], '/' ) + 1 );
	}

	// Get the image base url.
	$img_base_url = substr( $img_url, 0, strrpos( $img_url, '/' ) + 1 );

	// Calculate the image aspect ratio.
	$img_ratio = $img_height / $img_width;

	// Images that have been edited in WordPres after being uploaded will
	// contain a unique hash. We look for that hash and use it later to filter
	// out images that are left overs from previous renditions.
	$img_edited = preg_match( '/-e[0-9]{13}/', $img_url, $img_edit_hash );

	// Loop through available images and only use images that are resized
	// versions of the same rendition.
	foreach ( $img_sizes as $img ) {

		// Filter out images that are leftovers from previous renditions.
		if ( $img_edited && ! strpos( $img['file'], $img_edit_hash[0] ) ) {
			continue;
		}

		// Calculate the new image ratio.
		$img_ratio_compare = $img['height'] / $img['width'];

		// If the new ratio differs by less than 0.01, use it.
		if ( abs( $img_ratio - $img_ratio_compare ) < 0.01 ) {
			$arr[ $img['width'] ] = $img_base_url . $img['file'] . ' ' . $img['width'] .'w';
		}
	}

	if ( count( $arr ) <= 1 ) {
		return false;
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
 * @param string $size Optional. Name of image size. Default value: 'thumbnail'.
 * @return string|bool A 'srcset' value string or false.
 */
function tevkori_get_srcset( $id, $size = 'thumbnail' ) {
	$srcset_array = tevkori_get_srcset_array( $id, $size );

	if ( empty( $srcset_array ) ) {
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
 * @param string $size Optional. Name of image size. Default value: 'thumbnail'.
 * @return string|bool A full 'srcset' string or false.
 */
function tevkori_get_srcset_string( $id, $size = 'thumbnail' ) {
	$srcset_value = tevkori_get_srcset( $id, $size );

	if ( empty( $srcset_value ) ) {
		return false;
	}

	return 'srcset="' . $srcset_value . '"';
}

/**
 * Create a 'srcset' attribute.
 *
 * @deprecated 2.1.0
 * @deprecated Use tevkori_get_srcset_string instead.
 * @see tevkori_get_srcset_string
 *
 * @param int $id Image attachment ID.
 * @return string|bool A full 'srcset' string or false.
 */
function tevkori_get_src_sizes( $id, $size = 'thumbnail' ) {
	return tevkori_get_srcset_string( $id, $size );
}

/**
 * Filter for the_content to add sizes and srcset attributes to images.
 *
 * @since 3.0
 *
 * @param string $content The raw post content to be filtered.
 */
function tevkori_filter_content_images( $content ) {
	return preg_replace_callback(
		'/<img\s([^>]+)>/i',
		
		// Don't use an anonymous callback function because it isn't supported by PHP 5.2.
		'tevkori_filter_content_images_callback',
		$content
	);
}
add_filter( 'the_content', 'tevkori_filter_content_images', 5, 1 );

/**
 * Callback function for tevkori_filter_content_images.
 *
 * @since 3.0
 *
 * @see tevkori_filter_content_images
 * @param array $matches Array containing the regular expression matches.
 */
function tevkori_filter_content_images_callback( $matches ) {
	$atts = $matches[1];
	$sizes = $srcset = '';
	
	// Check if srcset attribute is not already present.
	if ( ! preg_match( '/srcset="([^"]+)"/i', $atts ) ) {

		// Get the url of the original image.
        preg_match( '/src="(.+?)(\-([0-9]+)x([0-9]+))?(\.\w{3,4})"/i', $atts, $url_matches );
		
		$url = $url_matches[1] . $url_matches[5];
		
		// Get the image ID.
		$id = attachment_url_to_postid( $url );

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

/**
 * Filter to add srcset and sizes attributes to post thumbnails and gallery images.
 *
 * @see wp_get_attachment_image_attributes
 * @return array Attributes for image.
 */
function tevkori_filter_attachment_image_attributes( $attr, $attachment, $size ) {
	if ( ! isset( $attr['srcset'] ) ) {
		$srcset = tevkori_get_srcset( $attachment->ID, $size );

		// Set the srcset attribute if one was returned.
		if ( $srcset ) {
			$attr['srcset'] = $srcset;

			if ( ! isset( $attr['sizes'] ) ) {
				$sizes = tevkori_get_sizes( $attachment->ID, $size );

				// Set the sizes attribute if sizes were returned.
				if ( $sizes ) {
					$attr['sizes'] = $sizes;
				}
			}
		}
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'tevkori_filter_attachment_image_attributes', 0, 3 );

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
