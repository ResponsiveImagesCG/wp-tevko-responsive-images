(function( document, config, image, script, firstScript ){
	// Adapted from picturefill 2.3.1
	if ( 'sizes' in image && 'currentSrc' in image && 'srcset' in image ) {
		return;
	}

	script = document.createElement( 'script' );
	firstScript = document.getElementsByTagName( 'script' )[0];

	script.async = script.src = config.picturefillSource;
	firstScript.parentNode.insertBefore( script, firstScript );

})( document, _wpResponsiveImagesSettings, new Image() );