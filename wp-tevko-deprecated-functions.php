<?php
/**
 * Mark a filter hook as deprecated and inform when it has been used.
 *
 * @since 3.1.0
 * @access private
 *
 * @param string $filter      The filter hook that was used.
 * @param string $version     The version that deprecated the filter hook.
 * @param string $replacement Optional. The filter hook that should have been used. Default null.
 */
function _tevkori_deprecated_filter( $filter, $version, $replacement = null ) {
	if ( WP_DEBUG ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( 'Filter hook %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $filter, $version, $replacement ) );
		} else {
			trigger_error( sprintf( 'Filter hook %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $filter, $version ) );
		}
	}
}

/**
 * Returns an array of image sources for a 'srcset' attribute.
 *
 * @since 2.1.0
 * @deprecated 3.0.0 Use 'wp_get_attachment_image_srcset()'
 *
 * @see wp_get_attachment_image_srcset()
 *
 * @param int          $id   Image attachment ID.
 * @param array|string $size Image size. Accepts any valid image size, or an array of width and height
 *                           values in pixels (in that order). Default 'medium'.
 * @return array|bool An array of 'srcset' values or false.
 */
function tevkori_get_srcset_array( $id, $size = 'medium' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_get_attachment_image_srcset()' );

	$srcset = wp_get_attachment_image_srcset( $id, $size );

	// Transform the 'srcset' value string to a pre-core style array.
	if ( ! $srcset ) {
		return false;
	}

	$sources = explode( ', ', $srcset );
	$arr = array();

	foreach ( $sources as $source ) {
		$split = explode( ' ', $source );
		$width = rtrim( $split[1], "w" );
		$arr[ $width ] = $source;
	}

	/**
	 * Filter the output of 'tevkori_get_srcset_array()'.
	 *
	 * @since 2.4.0
	 * @deprecated 3.0.0 Use 'wp_calculate_image_srcset'
	 *
	 * @see wp_calculate_image_srcset
	 *
	 * @param array        $arr   An array of image sources.
	 * @param int          $id    Attachment ID for image.
	 * @param array|string $size  Image size. Image size or an array of width and height
	 *                            values in pixels (in that order).
	 */
	if ( has_filter( 'tevkori_srcset_array' ) ) {
		_tevkori_deprecated_filter( 'tevkori_srcset_array', '3.0.0', 'wp_calculate_image_srcset' );
	}
	return apply_filters( 'tevkori_srcset_array', $arr, $id, $size );
}

/**
 * Returns the value for a 'srcset' attribute.
 *
 * @since 2.3.0
 * @deprecated 3.0.0 Use 'wp_get_attachment_image_srcset()'
 *
 * @see wp_get_attachment_image_srcset()
 *
 * @param int          $id   Image attachment ID.
 * @param array|string $size Image size. Accepts any valid image size, or an array of width and height
 *                           values in pixels (in that order). Default 'medium'.
 * @return string|bool A 'srcset' value string or false.
 */
function tevkori_get_srcset( $id, $size = 'medium' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_get_attachment_image_srcset()' );

	if ( has_filter( 'tevkori_srcset_array' ) ) {
		$srcset_array = tevkori_get_srcset_array( $id, $size );

		return $scrset_array ? implode( ', ', $srcset_array ) : false;
	} else {
		return wp_get_attachment_image_srcset( $id, $size );
	}
}

/**
 * Returns a 'srcset' attribute.
 *
 * @since 2.1.0
 * @deprecated 3.0.0 Use 'wp_get_attachment_image_srcset()'
 *
 * @see wp_get_attachment_image_srcset()
 *
 * @param int          $id   Image attachment ID.
 * @param array|string $size Image size. Accepts any valid image size, or an array of width and height
 *                           values in pixels (in that order). Default 'medium'.
 * @return string|bool A full 'srcset' string or false.
 */
function tevkori_get_srcset_string( $id, $size = 'medium' ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_get_attachment_image_srcset()' );

	if ( has_filter( 'tevkori_srcset_array' ) ) {
		$srcset_value = tevkori_get_srcset( $id, $size );

		return $srcset_value ? 'srcset="' . $srcset_value . '"' : false;
	} else {
		$srcset_value = wp_get_attachment_image_srcset( $id, $size );

		return $srcset_value ? 'srcset="' . $srcset_value . '"' : false;
	}
}

/**
 * Returns the value for a 'sizes' attribute.
 *
 * @since 2.2.0
 * @deprecated 3.0.0 Use 'wp_get_attachment_image_sizes()'
 *
 * @see wp_get_attachment_image_sizes()
 *
 * @param int          $id   Image attachment ID.
 * @param array|string $size Image size. Accepts any valid image size, or an array of width and height
 *                           values in pixels (in that order). Default 'medium'.
 * @param array        $args {
 *     Optional. Arguments to retrieve posts.
 *
 *     @type array|string $sizes An array or string containing of size information.
 *     @type int          $width A single width value used in the default 'sizes' string.
 * }
 * @return string|bool A valid source size value for use in a 'sizes' attribute or false.
 */
function tevkori_get_sizes( $id, $size = 'medium', $args = null ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_get_attachment_image_sizes()' );

	/*
	 * If this function is passed with an $args parameter, we need to parse the
	 * $args parameter as we were doing previously, and then save it to a static
	 * variable using '_tevkori_sizes_has_args()' which can be accessed from the
	 * '_tevkori_image_sizes_args_shim()' filter in 'wp_calculate_image_sizes()'
	 */
	if ( $args ) {
		// Try to get the image width from '$args' before calling 'image_downsize()'.
		if ( is_array( $args ) && ! empty( $args['width'] ) ) {
			$img_width = (int) $args['width'];
		} else {
			$img_width = ( $img = image_downsize( $id, $size ) ) ? $img[1] : false;
		}

		// Bail early if '$img_width' isn't set.
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

		// Set our static sizes args.
		_tevkori_sizes_has_args( $args );

		$sizes = wp_get_attachment_image_sizes( $id, $size );

		// Unset our static sizes args.
		_tevkori_sizes_has_args( false );

		return $sizes;
	} else {
		return wp_get_attachment_image_sizes( $id, $size );
	}
}

/**
 * Private function to cache '$args' param from 'tevkori_get_sizes()'.
 *
 * @since 3.1.0
 * @access private
 *
 * @param array|bool $args Optional. The parsed value of the '$args' param from
 *                         'tevkori_get_sizes()' or 'false' to unset the saved value.
 * @return array|bool The cached '$args' value or false is none is set.
 */
function _tevkori_sizes_has_args( $args = null ) {
	static $return_args = false;

	if ( false === $args ) {
		$return_args = false;
	} elseif ( is_array( $args ) ) {
		$return_args = $args;
	}

	return $return_args;
}

/**
 * Provides backward compatibility of the 'tevkori_image_sizes_args' filter
 * and the '$args' parameter of 'tevkori_get_sizes()'.
 *
 * @since 3.1.0
 * @access private
 *
 * @see wp_calculate_image_srcset
 *
 * @return string A source size value for use in a 'sizes' attribute.
 */
function _tevkori_image_sizes_args_shim( $sizes, $size, $image_src, $image_meta, $id ) {
	// Check for '$args' that were passed to 'tevkori_get_sizes()';
	$args = _tevkori_sizes_has_args();

	// Bail early if no '$args' were passed or the old filter wasn't used.
	if ( ! $args && ! has_filter( 'tevkori_image_sizes_args' ) ) {
		return $sizes;
	}

	// If no '$args' are present, we'll build the default '$args'.
	if ( ! $args ) {
		// Recreate default '$args'.
		if ( is_array( $size ) ) {
			$img_width = (int) $size[0];
		} else {
			$img = image_downsize( $id, $size );
			$img_width = $img[1];
		}

		$args = array(
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
	}

	/**
	* Filter arguments used to create the 'sizes' attribute value.
	*
	* @since 2.4.0
	* @deprecated 3.0.0 Use 'wp_calculate_image_sizes'
	*
	* @see wp_calculate_image_sizes
	*
	* @param array        $args An array of arguments used to create a 'sizes' attribute.
	* @param int          $id   Post ID of the original image.
	* @param array|string $size Image size. Image size or an array of width and height
	*                           values in pixels (in that order).
	*/
	if ( has_filter( 'tevkori_image_sizes_args' ) ) {
		_tevkori_deprecated_filter( 'tevkori_image_sizes_args', '3.0.0', 'wp_calculate_image_sizes' );
	}
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

				// If a media length was set, create the media query.
				$media_query = '(' . $media_condition . ": " . $media_length . ') ';
			} else {
				// If no media length was set, '$media_query' is blank.
				$media_query = '';
			}
			// Add to the source size list string.
			$size_list .= $media_query . $size_value . ', ';
		}
		// Remove the trailing comma and space from the end of the string.
		$size_list = substr( $size_list, 0, -2 );
	}

	// If '$size_list' is defined return the string, otherwise return '$sizes'.
	return ( $size_list ) ? $size_list : $sizes;
}
add_filter( 'wp_calculate_image_sizes', '_tevkori_image_sizes_args_shim', 1, 5 );

/**
 * Returns a 'sizes' attribute.
 *
 * @since 2.2.0
 * @deprecated 3.0.0 Use 'wp_get_attachment_image_sizes()'
 *
 * @see wp_get_attachment_image_sizes()
 *
 * @param int          $id   Image attachment ID.
 * @param array|string $size Image size. Accepts any valid image size, or an array of width and height
 *                           values in pixels (in that order). Default 'medium'.
 * @param array        $args {
 *     Optional. Arguments to retrieve posts.
 *
 *     @type array|string $sizes An array or string containing of size information.
 *     @type int          $width A single width value used in the default 'sizes' string.
 * }
 * @return string|bool A valid source size list as a 'sizes' attribute or false.
 */
function tevkori_get_sizes_string( $id, $size = 'medium', $args = null ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_get_attachment_image_sizes()' );

	if ( $args ) {
		$sizes = tevkori_get_sizes( $id, $size, $args );
	} else {
		$sizes = wp_get_attachment_image_sizes( $id, $size );
	}

	return $sizes ? 'sizes="' . esc_attr( $sizes ) . '"' : false;
}

/**
 * Filter to add 'srcset' and 'sizes' attributes to images in the post content.
 *
 * @since 2.5.0
 * @deprecated 3.0.0 Use 'wp_make_content_images_responsive()'
 *
 * @see wp_make_content_images_responsive()
 *
 * @param string $content The raw post content to be filtered.
 * @return string Converted content with 'srcset' and 'sizes' added to images.
 */
function tevkori_filter_content_images( $content ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'wp_make_content_images_responsive()' );
	return wp_make_content_images_responsive( $content );
}

/**
 * Filter to add 'srcset' and 'sizes' attributes to post thumbnails and gallery images.
 *
 * @since 2.3.0
 *
 * @see wp_get_attachment_image_attributes
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

function wp_get_attachment_image_sizes_filter_shim( $sizes, $size, $image_src, $image_meta, $attachment_id ) {
	if ( has_filter( 'wp_get_attachment_image_sizes' ) ) {
		/**
		 * Filter the output of 'wp_get_attachment_image_sizes()'.
		 *
		 * @since 3.0.0
		 * @deprecated 3.1.0 Use 'wp_calculate_image_sizes'
		 *
		 * @see wp_calculate_image_sizes
		 *
		 * @param string       $sizes         A source size value for use in a 'sizes' attribute.
		 * @param array|string $size          Image size. Image size name, or an array of width and height
		 *                                    values in pixels (in that order).
		 * @param array        $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
		 * @param int          $attachment_id Image attachment ID of the original image.
		 * @param string       $image_src     Optional. The URL to the image file.
		 */
		_tevkori_deprecated_filter( 'wp_get_attachment_image_sizes', '3.1.0', 'wp_calculate_image_sizes' );
		return apply_filters( 'wp_get_attachment_image_sizes', $sizes, $size, $image_meta, $attachment_id, $image_src );
	} else {
		return $sizes;
	}
}
add_filter( 'wp_calculate_image_sizes', 'wp_get_attachment_image_sizes_filter_shim', 10, 5 );
