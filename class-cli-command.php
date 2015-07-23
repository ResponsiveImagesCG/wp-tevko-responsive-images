<?php

/**
 * Responsive Images CLI commands
 */
class ResponsiveImagesCLI extends WP_CLI_Command {
	/**
	 * Convert all existing <img> tags to use srcset.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more post IDs to convert
	 *
	 * [--dry-run]
	 * : Don't save anything, just return stats about potential changes
	 *
	 * @subcommand add-srcset
	 */
	function add_srcset( $args, $assoc_args ) {
		global $wpdb;
		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run' );
		$posts = array_map( 'intval', $args );
		$posts = array_filter( $posts );

		if ( ! class_exists( 'DOMDocument' ) ) {
			WP_CLI::error( 'This functionality requires DOMDocument.' );
		}

		if ( ! $posts ){
			$post_types = $this->get_enabled_post_types();
			$post_statuses = $this->get_skipped_post_status();

			$posts = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ($post_types) AND post_status NOT IN ($post_statuses);" );
		}

		$count = 0;
		foreach ( $posts as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			$original_content = $post->post_content;

			// Save a revision right before updating (if necessary, `wp_save_post_revision`
			// won't save if the post == the last revision)
			if ( ! $dry_run ) {
				wp_save_post_revision( $post_id );
			}

			// Parse the content for any local <img>s.
			$updated_content = $original_content;

			$doc = new DOMDocument();
			@$doc->loadHTML( $updated_content );
			$images = $doc->getElementsByTagName('img');

			$old_imgs = $new_imgs = array();
			foreach ( $images as $img ) {
				if ( $img->hasAttribute( 'srcset' ) ) {
					continue;
				}
				$old = 'src="' . $img->getAttribute('src') .'"';
				$new = false;

				$attachment_id = false;
				$size = 'medium';
				if ( $img->hasAttribute('class') ) {
					$class = $img->getAttribute('class');
					if ( preg_match( '/wp-image-(\d*)/i', $class, $matches ) ){
						$attachment_id = intval( $matches[1] );
					}
					if ( preg_match( '/size-(\S*)/i', $class, $matches ) ){
						$size = $matches[1];
					}
				}
				if ( $attachment_id && $size ) {
					$srcset = tevkori_get_srcset_array( $attachment_id, $size );
					if ( ! is_array( $srcset ) ) {
						continue;
					}

					$srcset = implode( ', ', $srcset );
					$new = $old . ' srcset="' . $srcset . '"';

					if ( $img->hasAttribute('width') ) {
						$data = wp_get_attachment_image_src( $attachment_id, $size );
						$new .= ' width="' . $data[1] . '"';
					}

					if ( $img->hasAttribute('height') ) {
						$data = wp_get_attachment_image_src( $attachment_id, $size );
						$new .= ' height="' . $data[2] . '"';
					}
				}

				// Make sure we have a new image before adding to our replacement array
				if ( $new ) {
					$old_imgs[] = $old;
					$new_imgs[] = $new;
				}
			}

			$updated_content = str_replace( $old_imgs, $new_imgs, $original_content );

			// Save if we've changed anything
			if ( $updated_content != $original_content ) {
				$result = false;

				if ( ! $dry_run ) {
					// Something is unsetting publish and title??
					$result = wp_update_post( array(
						'ID' => $post_id,
						'post_content' => $updated_content,
					) );
					if ( $result ) {
						$count++;
					}
				} else {
					$count++;
				}
				WP_CLI::line( sprintf( '%s %s [%s] was updated, srcset was added to %s images.', $post->post_type, $post->post_title, $post_id, count( $new_imgs ) ) );
			}
		}

		if ( ! $dry_run ) {
			WP_CLI::success( sprintf( "%s posts were updated.", $count ) );
		} else {
			WP_CLI::success( sprintf( "%s posts can be updated. Run again without --dry-run to update them.", $count ) );
		}
	}

	/**
	 * Return user-facing post types which should be filtered.
	 */
	protected function get_enabled_post_types(){
		$post_types = get_post_types(array(
			'public' => true,
		));
		if ( ! is_array( $post_types ) ) {
			return '';
		}

		// Don't process attachments
		unset( $post_types['attachment'] );

		$post_types = apply_filters( 'respimg_cli_enabled_post_types', $post_types );

		return "'" . implode( "', '", $post_types ) . "'";
	}

	/**
	 * Return the post statuses we should skip
	 */
	protected function get_skipped_post_status(){
		$status = apply_filters( 'respimg_cli_skipped_status', array( 'auto-draft', 'inherit', 'trash' ) );

		return "'" . implode( "', '", $status ) . "'";
	}
}

WP_CLI::add_command( 'respimg', 'ResponsiveImagesCLI' );
