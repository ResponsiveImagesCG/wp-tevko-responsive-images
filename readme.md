RICG-responsive-images
---

Bringing automatic default responsive images to wordpress.

This plugin works by including all available image sizes for each image upload. Whenever wordpress outputs the image through the media uploader, or whenever a featured image is generated, those sizes will be included in the image tag via the [srcset](http://css-tricks.com/responsive-images-youre-just-changing-resolutions-use-srcset/) attribute. 

##Hardcoding in template files

 You can output a responsive image anywhere you'd like by using the following syntax:

``<img src="pathToImage" <?php echo tevkori_get_src_sizes( TheIdOfYourImage, theLargestImageSizeNeeded ); ?> />``

ex.)

```<img src="myimg.png" <?php echo tevkori_get_src_sizes( 11, 'tevkoriMedium-img' ); ?> />```

##Version

2.0.1

##Changelog 

- Only outputs the default wordpress sizes, giving theme developers the option to extend as needed
- Added support for featured images

**2.0.0**
 - Uses [Picturefill 2.2.0 (Beta)](http://scottjehl.github.io/picturefill/)
 - Scripts are output to footer
 - Image sizes adjusted
 - Most importantly, the srcset syntax is being used
 - The structure of the plugin is significantly different. The plugin now works by extending the default wordpress image tag functionality to include the srcset attribute.
 - Works for cropped images!
 - Backwards compatible (images added before plugin install will still be responsive)!
