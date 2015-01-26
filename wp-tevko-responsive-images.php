<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
 * @link              https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images
 * @since             2.0.0
 * @package           http://css-tricks.com/hassle-free-responsive-images-for-wordpress/
 *
 * @wordpress-plugin
 * Plugin Name:       WP Tevko Responsive Images
 * Plugin URI:        http://css-tricks.com/hassle-free-responsive-images-for-wordpress/
 * Description:       Bringing automatic default responsive images to wordpress
 * Version:           2.0.2
 * Author:            Tim Evko
 * Author URI:        http://timevko.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// First we queue the polyfill
function tevkori_get_picturefill() {
	wp_enqueue_script( 'picturefill', plugins_url( 'js/picturefill.js', __FILE__ ), array(), '2.2.0', true );
}
add_action( 'wp_enqueue_scripts', 'tevkori_get_picturefill' );

// Return an image with srcset and sizes attributes
function tevkori_get_src_sizes( $id, $size, $args = array() ) {

	$default_args = array(
		'sizes' => array(),
		'maintain_aspect_ratio' => true,
		'excluded_sizes' => array(),
		'included_sizes' => array()
	);

	$vars = wp_parse_args( $args, $default_args );

	$srcset_arr = array();
	$sizes_arr  = array();

	// See which image is being returned and bail if none is found
	if ( ! $image = wp_get_attachment_image_src( $id, $size ) ) {
		return false;
	};

	// Break image data into url, width, and height
	list( $img_url, $img_width, $img_height ) = $image;

	// Image meta
	$image_meta = wp_get_attachment_metadata( $id );

	// Default sizes
	$default_sizes = $image_meta['sizes'];

	// Add full size to the default_sizes array
	$default_sizes['full'] = array(
		'width' 	=> $image_meta['width'],
		'height'	=> $image_meta['height'],
		'file'		=> $image_meta['file']
	);

	// Go through each image size and unset the size if conditions are met.
	foreach ( $default_sizes as $key => $image_size ) {

		// Condition 1: Remove size if it is an excluded size.
		if ( ! empty( $vars['excluded_sizes'] ) ) {

			if ( in_array( $key, $vars['excluded_sizes'] ) ) {
				unset( $default_sizes[ $key ] );
			}

		}

		// Condition 2: Remove size if it is NOT an included size.
		if ( ! empty( $vars['included_sizes'] ) ) {

			if ( ! in_array( $key, $vars['included_sizes'] ) ) {

				unset( $default_sizes[ $key ] );
			}

		}

		// Condition 3: Remove any hard-crops, if maintain_aspect_ratio is true.
		if ( TRUE == $vars['maintain_aspect_ratio'] ) {

			// Calculate the height we would expect if this is a soft crop given the size width
			$soft_height = (int) round( $image_size['width'] * $img_height / $img_width );

			if ( $image_size['height'] !== $soft_height ) {
				unset( $default_sizes[ $key ] );
			}

		}
	}

	// No sizes? Checkout early
	if( ! $default_sizes )
	return false;

	// Loop through each size we know should exist
	foreach( $default_sizes as $key => $size ) {

		// Reference the size directly by it's pixel dimension
		$image_src = wp_get_attachment_image_src( $id, $key );
		$srcset_arr[] = $image_src[0] . ' ' . $size['width'] .'w';
	}

	// Set up srcset attribute
	$srcset_attr = '';
	if ( !empty( $srcset_arr ) ) {
		$srcset_attr = 'srcset="' . implode( ', ', $srcset_arr ) . '"';
	}

	// Set up sizes attribute
	if ( ! empty( $vars['sizes'] ) ) {

		foreach ( $vars['sizes'] as $key => $size_string ) {

			// If the width isn't set, go on to the next one.
			if ( ! isset( $size_string['width'] ) ) {
				continue;
			}

			// Reset to empty from previous loop
			$media_query = '';

			// Set media query; extra space is better here,
			// if there were no media query there would be an extra space.
			if ( isset( $size_string['media_query'] ) ) {
				$media_query = $size_string['media_query'] . ' ';
			}

			// Set width
			$width = $size_string['width'];

			// Add to array of sizes.
			$sizes_arr[] = $media_query . $width;
		}

	}

	$sizes_attr = '';
	if ( ! empty( $sizes_arr ) ) {
		$sizes_attr = 'sizes="' . implode( ', ', $sizes_arr ) . '"';
	}

	$src_sizes = '';

	// If $srcset isn't blank, add it to the output.
	if ( '' != $srcset_attr ) {
		$src_sizes = $srcset_attr;
	}

	// If $sizes isn't blank and the output isn't blank, add $sizes.
	if ( '' != $sizes_attr && $src_sizes != '' ) {
		$src_sizes .= ' ' . $sizes_attr;
	}

	return $src_sizes;
}

// Extend image tag to include srcset attribute
function tevkori_extend_image_tag( $html, $id, $caption, $title, $align, $url, $size, $alt ) {
	add_filter( 'editor_max_image_size', 'tevkori_editor_image_size' );
	$src_sizes = tevkori_apply_content_src_filter( $id, $size );
	remove_filter( 'editor_max_image_size', 'tevkori_editor_image_size' );
	$html = preg_replace( '/(src\s*=\s*"(.+?)")/', '$1' . ' ' . $src_sizes, $html );
	return $html;
}
add_filter( 'image_send_to_editor', 'tevkori_extend_image_tag', 0, 8 );

// Filter post_thumbnail_html to add srcset attributes to post_thumbnails
function tevkori_filter_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	// If the HTML is empty, short circuit
	if ( '' === $html ) {
		return;
	}

	$src_sizes = tevkori_apply_post_thumbnail_src_filter( $post_thumbnail_id, $size );
	$html = preg_replace( '/(src\s*=\s*"(.+?)")/', '$1' . ' ' . $src_sizes, $html );
	return $html;
}
add_filter( 'post_thumbnail_html', 'tevkori_filter_post_thumbnail_html', 0, 5);

/**
 * Disable the editor size constraint applied for images in TinyMCE.
 *
 * @param  array $max_image_size An array with the width as the first element, and the height as the second element.
 * @return array A width & height array so large it shouldn't constrain reasonable images.
 */
function tevkori_editor_image_size( $max_image_size ){
	return array( 99999, 99999 );
}

function tevkori_load_admin_scripts( $hook ) {
	if ($hook == 'post.php' || $hook == 'post-new.php') {
		wp_enqueue_script( 'wp-tevko-responsive-images', plugin_dir_url( __FILE__ ) . 'js/wp-tevko-responsive-images.js', array('wp-backbone'), '2.0.0', true );
	}
}
add_action( 'admin_enqueue_scripts', 'tevkori_load_admin_scripts' );

// Apply filters for content tevkori_get_src_sizes
function tevkori_apply_content_src_filter( $id, $size, $args = array() ) {

	$src_sizes = tevkori_get_src_sizes( $id, $size, $args );

	$src_sizes = apply_filters( 'tevkori_get_content_src_sizes_filter', $src_sizes, $id, $size, $args );

	return $src_sizes;
}

// Apply filters for post thumbnail tevkori_get_src_sizes
function tevkori_apply_post_thumbnail_src_filter( $id, $size, $args = array() ) {

	$src_sizes = tevkori_get_src_sizes( $id, $size, $args );

	$src_sizes = apply_filters( 'tevkori_get_post_thumbnail_src_sizes_filter', $src_sizes, $id, $size, $args );

	return $src_sizes;
}
