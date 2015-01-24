=== RICG Responsive Images ===
Contributors: tevko, wilto, chriscoyier, joemcgill, Michael McGinnis, ryelle, Brandon Lavigne, nacin , georgestephanis, helen, wordpressdotorg, Bocoup
Tags: Responsive, Images, Responsive Images, SRCSET, Picturefill
Requires at least: 4.1
Tested up to: 4.1
Stable tag: 2.0.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Bringing automatic default responsive images to WordPress.

== Description ==

**If you'd like to contribute to this plugin, please do so on [Github](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images)**

Basically, responsive images allow the browser to choose the best image from a list. This plugin works by including all available image sizes for each image upload. Whenever WordPress outputs the image through the media uploader, or whenever a featured image is generated, those sizes will be included in the image tag via the [srcset](http://css-tricks.com/responsive-images-youre-just-changing-resolutions-use-srcset/) attribute.

**Hardcoding in template files**

 You can output a responsive image anywhere you'd like by using the following syntax:

`<img src="pathToImage" <?php echo tevkori_get_src_sizes( TheIdOfYourImage, theLargestImageSizeNeeded ); ?> />`

ex.)

`<img src="myimg.png" <?php echo tevkori_get_src_sizes( 11, 'tevkoriMedium-img' ); ?> />`

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==
= 2.0.2 =
 * A bugfix correcting a divide by zero error. Some users may have seen this after upgrading to 2.0.1

= 2.0.1 =
 * Only outputs the default WordPress sizes, giving theme developers the option to extend as needed
 * Added support for featured images

= 2.0.0 =
 * Uses [Picturefill 2.2.0 (Beta)](http://scottjehl.github.io/picturefill/)
 * Scripts are output to footer
 * Image sizes adjusted
 * Most importantly, the srcset syntax is being used
 - Works for cropped images!
 - Backwards compatible (images added before plugin install will still be responsive)!
