<?php
/**
 * @link              https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images
 * @since             2.0.0
 * @package           http://www.smashingmagazine.com/2015/02/24/ricg-responsive-images-for-wordpress/
 *
 * @wordpress-plugin
 * Plugin Name:       RICG Responsive Images
 * Plugin URI:        https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images
 * Description:       Bringing automatic default responsive images to WordPress
 * Version:           3.1.1
 * Author:            The RICG
 * Author URI:        http://responsiveimages.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Don't load the plugin directly.
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

/*
 * Include the advanced image compression files.
 * See readme.md for more information.
 */
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

// Load the deprecated core functions.
require_once( plugin_dir_path( __FILE__ ) . 'wp-tevko-deprecated-functions.php' );

/*
 * Load copies of our core functions if the plugin is installed on a version of WordPress
 * previous to 4.4, when the functions were added to core.
 */
if ( ! function_exists( 'wp_get_attachment_image_srcset' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'wp-tevko-core-functions.php' );
}

// Enqueue bundled version of the Picturefill library.
function tevkori_get_picturefill() {
	wp_enqueue_script( 'picturefill', plugins_url( 'js/picturefill.min.js', __FILE__ ), array(), '3.0.1', true );
}
add_action( 'wp_enqueue_scripts', 'tevkori_get_picturefill' );

/**
 * Filter to add 'srcset' and 'sizes' attributes to post thumbnails and gallery images.
 * The filter is added to the hook in wp-tevko-core-functions.php because
 * it is only needed on a version of WordPress previous to 4.4.
 *
 * @since 2.3.0
 * @see 'wp_get_attachment_image_attributes'
 *
 * @return array Attributes for image.
 */
function tevkori_filter_attachment_image_attributes( $attr, $attachment, $size ) {
	// Set 'srcset' and 'sizes' if not already present and both were returned.
	if ( empty( $attr['srcset'] ) ) {
		$srcset = wp_get_attachment_image_srcset( $attachment->ID, $size );
		$sizes  = wp_get_attachment_image_sizes( $attachment->ID, $size );

		if ( $srcset && $sizes ) {
			$attr['srcset'] = $srcset;

			if ( empty( $attr['sizes'] ) ) {
				$attr['sizes'] = $sizes;
			}
		}
	}

	return $attr;
}
