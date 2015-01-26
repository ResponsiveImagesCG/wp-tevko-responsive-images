"use strict";

(function() {

  /**
   * Recalculate srcset attribute after an image-update event
   */
  wp.media.events.on( 'editor:image-update', function( args ) {
    // arguments[0] = { Editor, image, metadata }
	  var image = args.image,
			metadata = args.metadata,
			srcsetGroup = [],
			srcset = '';

    // if the image url has changed, recalculate srcset attributes
    if ( metadata && metadata.url !== metadata.originalUrl ) {
      // we need to get the postdata for the image because
      // the sizes array isn't passed into the editor
      var imagePostData = new wp.media.model.PostImage( metadata ),
        sizes = imagePostData.attachment.attributes.sizes;

      // grab all the sizes that match our target ratio and add them to our srcset array
      _.each(sizes, function(size){
        var softHeight = Math.round( size.width * metadata.height / metadata.width );

        if (softHeight === size.height) {
          srcsetGroup.push(size.url + ' ' + size.width + 'w');
        }
      });

      // convert the srcsetGroup array to our srcset value
      srcset = srcsetGroup.join(', ');

      // update the srcset attribute of our image
      image.setAttribute( 'srcset', srcset );
    }

  });

})();
