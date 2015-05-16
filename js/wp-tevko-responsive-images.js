"use strict";

(function() {

	/**
	 * Recalculate srcset attribute after an image-update event
	 */
	if ( wp.media ) {
		wp.media.events.on( 'editor:image-update', function( args ) {
			// arguments[0] = { Editor, image, metadata }
			var image = args.image,
				metadata = args.metadata;

			// If the image url has changed, recalculate srcset attributes.
			if ( metadata && metadata.url !== metadata.originalUrl ) {
				// Update the srcset attribute.
				updateSrcset( image, metadata );
				// Update the sizes attribute.
				updateSizes( image, metadata );
			}

		});
	}

	/**
	 * Update the srcet attribute on an image in the editor
	 */
	var updateSrcset = function( image, metadata ) {

		var data = {
			action: 'tevkori_ajax_srcset',
			postID: metadata.attachment_id,
			size: metadata.size
		};

		jQuery.post( ajaxurl, data, function( response ) {
			image.setAttribute( 'srcset', response );
		});
	}

	/**
	 * Update the data-sizes attribute on an image in the editor
	 */
	var updateSizes = function( image, metadata ) {

		var sizes = '(max-width: ' + metadata.width + 'px) 100vw, ' + metadata.width + 'px';

		// Update the sizes attribute of our image.
		image.setAttribute( 'data-sizes', sizes );
	}


})();
