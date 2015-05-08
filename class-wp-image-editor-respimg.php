<?php

	/**
	 * WordPress Respimg Imagick Image Editor
	 *
	 * @package wp-respimg
	 * @version 0.0.1
	 */

	require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
	require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

	/**
	 * WordPress Image Editor Class for Image Manipulation through Imagick PHP Module with php-respimg
	 *
	 * @package wp-respimg
	 * @uses WP_Image_Editor_Imagick Extends class
	 */
	class WP_Image_Editor_Respimg extends WP_Image_Editor_Imagick {

		/**
		 * Loads image from $this->file into new Respimg Object.
		 *
		 * @access protected
		 *
		 * @return boolean|WP_Error True if loaded; WP_Error on failure.
		 */
		public function load() {
			if ( $this->image instanceof Respimg )
				return true;

			if ( ! is_file( $this->file ) && ! preg_match( '|^https?://|', $this->file ) )
				return new WP_Error( 'error_loading_image', __('File doesn&#8217;t exist?'), $this->file );

			/** This filter is documented in wp-includes/class-wp-image-editor-imagick.php */
			// Even though Imagick uses less PHP memory than GD, set higher limit for users that have low PHP.ini limits
			@ini_set( 'memory_limit', apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );

			try {
				$this->image = new Respimg( $this->file );

				if( ! $this->image->valid() )
					return new WP_Error( 'invalid_image', __('File is not an image.'), $this->file);

				// Select the first frame to handle animated images properly
				if ( is_callable( array( $this->image, 'setIteratorIndex' ) ) )
					$this->image->setIteratorIndex(0);

				$this->mime_type = $this->get_mime_type( $this->image->getImageFormat() );
			}
			catch ( Exception $e ) {
				return new WP_Error( 'invalid_image', $e->getMessage(), $this->file );
			}

			$updated_size = $this->update_size();
			if ( is_wp_error( $updated_size ) ) {
				return $updated_size;
			}

			return $this->set_quality();
		}

		/**
		 * Resizes current image.
		 *
		 * At minimum, either a height or width must be provided.
		 * If one of the two is set to null, the resize will
		 * maintain aspect ratio according to the provided dimension.
		 *
		 * @access public
		 *
		 * @param  int|null $max_w Image width.
		 * @param  int|null $max_h Image height.
		 * @param  boolean  $crop
		 * @return boolean|WP_Error
		 */
		public function resize( $max_w, $max_h, $crop = false ) {
			if ( ( $this->size['width'] == $max_w ) && ( $this->size['height'] == $max_h ) )
				return true;

			$dims = image_resize_dimensions( $this->size['width'], $this->size['height'], $max_w, $max_h, $crop );
			if ( ! $dims )
				return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
			list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

			if ( $crop ) {
				return $this->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h );
			}

			try {
				if ($this->mime_type === 'image/gif') {
					$this->image->scaleImage( $dst_w, $dst_h );
				} else {
					$this->image->smartResize( $dst_w, $dst_h, false );
				}
			}
			catch ( Exception $e ) {
				return new WP_Error( 'image_resize_error', $e->getMessage() );
			}

			return $this->update_size( $dst_w, $dst_h );
		}

		/**
		 * Resize multiple images from a single source.
		 *
		 * @access public
		 *
		 * @param array $sizes {
		 *     An array of image size arrays. Default sizes are 'small', 'medium', 'large'.
		 *
		 *     Either a height or width must be provided.
		 *     If one of the two is set to null, the resize will
		 *     maintain aspect ratio according to the provided dimension.
		 *
		 *     @type array $size {
		 *         @type int  ['width']  Optional. Image width.
		 *         @type int  ['height'] Optional. Image height.
		 *         @type bool $crop   Optional. Whether to crop the image. Default false.
		 *     }
		 * }
		 * @return array An array of resized images' metadata by size.
		 */
		public function multi_resize( $sizes ) {
			$metadata = array();
			$orig_size = $this->size;
			$orig_image = clone $this->image;

			foreach ( $sizes as $size => $size_data ) {
				if ( ! $this->image )
					$this->image = clone $orig_image;

				if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) ) {
					continue;
				}

				if ( ! isset( $size_data['width'] ) ) {
					$size_data['width'] = null;
				}
				if ( ! isset( $size_data['height'] ) ) {
					$size_data['height'] = null;
				}

				if ( ! isset( $size_data['crop'] ) ) {
					$size_data['crop'] = false;
				}

				$resize_result = $this->resize( $size_data['width'], $size_data['height'], $size_data['crop'] );
				$duplicate = ( ( $orig_size['width'] == $size_data['width'] ) && ( $orig_size['height'] == $size_data['height'] ) );

				if ( ! is_wp_error( $resize_result ) && ! $duplicate ) {
					$resized = $this->_save( $this->image );

					$this->image->clear();
					$this->image->destroy();
					$this->image = null;

					if ( ! is_wp_error( $resized ) && $resized ) {
						unset( $resized['path'] );
						$metadata[$size] = $resized;
					}
				}

				$this->size = $orig_size;
			}

			$this->image = $orig_image;

			return $metadata;
		}

		/**
		 * Crops Image.
		 *
		 * @access public
		 *
		 * @param int $src_x The start x position to crop from.
		 * @param int $src_y The start y position to crop from.
		 * @param int $src_w The width to crop.
		 * @param int $src_h The height to crop.
		 * @param int $dst_w Optional. The destination width.
		 * @param int $dst_h Optional. The destination height.
		 * @param boolean $src_abs Optional. If the source crop points are absolute.
		 * @return boolean|WP_Error
		 */
		public function crop( $src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false ) {
			if ( $src_abs ) {
				$src_w -= $src_x;
				$src_h -= $src_y;
			}

			try {
				$this->image->cropImage( $src_w, $src_h, $src_x, $src_y );
				$this->image->setImagePage( $src_w, $src_h, 0, 0);

				if ( $dst_w || $dst_h ) {
					// If destination width/height isn't specified, use same as
					// width/height from source.
					if ( ! $dst_w )
						$dst_w = $src_w;
					if ( ! $dst_h )
						$dst_h = $src_h;

					if ($this->mime_type === 'image/gif') {
						$this->image->scaleImage( $dst_w, $dst_h );
					} else {
						$this->image->smartResize( $dst_w, $dst_h, false );
					}
					return $this->update_size();
				}
			}
			catch ( Exception $e ) {
				return new WP_Error( 'image_crop_error', $e->getMessage() );
			}
			return $this->update_size();
		}

	}
