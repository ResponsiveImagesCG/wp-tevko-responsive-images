=== RICG Responsive Images ===
Contributors: tevko, wilto, joemcgill, jaspermdegroot, chriscoyier, Michael McGinnis, ryelle, drrobotnik, nacin, georgestephanis, helen, wordpressdotorg, Bocoup
Donate link: https://app.etapestry.com/hosted/BoweryResidentsCommittee/OnlineDonation.html
Tags: Responsive, Images, Responsive Images, SRCSET, Picturefill
Requires at least: 4.0
Tested up to: 4.4
Stable tag: 3.1.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Bringing automatic default responsive images to WordPress.

== Description ==

Bringing automatic default responsive images to WordPress.

This plugin works by including all available image sizes for each image upload. Whenever WordPress outputs the image through the media uploader, or whenever a featured image is generated, those sizes will be included in the image tag via the srcset attribute.

**Important notes**

* Version 3.1.0 includes important changes that make this plugin compatible with WordPress version 4.4. Upgrading is highly recommended.

* As of version 2.5.0, the plugin adds `srcset` and `sizes` attributes to images on the front end instead of adding them to the image markup saved in posts.

**Full documentation and contributor guidelines can be found on [Github](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images)**

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. If you'd like to enable the advanced image compression feature, Please see the instructions at https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/tree/dev#advanced-image-compression

== Changelog ==

= 3.1.1 =
* Fixes a bug where the srcset of images in imported content was missing or broken.
* Reduces the accepted aspect ratio difference for images to be included in the srcset.
* Fixes a bug where `img` tags without ending slash don't get responsive images.
* Deprecates the helper function `tevkori_get_media_embedded_in_content()` which is no longer used.
* Makes sure that the setup of default themes doesn't break the tests.
* Adds more examples to the Hook Reference in readme.md.
* Corrections and improvements to inline documentation.

= 3.1.0 =
* Adds special handling of GIFs in srcset attributes to preserve animation.
* Makes internal srcset/sizes functions more consistent.
* Fixes a bug where functions hooked into `tevkori_image_sizes_args` were not firing.
* Fixes a bug where custom sizes attributes added via the post editor were being overwritten.
* Deprecates hook `wp_get_attachment_image_sizes`.
* Fixes a bug where `the_post_thumbnail()` would fail to add srcset/sizes attributes.
* Several improvements to internal inline documentation.
* Major improvements to function/hook documentation in readme.md after 3.0.0 changes.

= 3.0.0 =
* Deprecates all core functions that will be merged into WordPress core in 4.4.
* Adds compatibility shims for sites using the plugin's internal functions and hooks.
* Adds a new display filter callback which can be use as general utility function for adding srcset and sizes attributes.
* Fixes a bug when `wp_get_attachment_metadata()` failed to return an array.
* Update our tests to be compatible with WordPress 4.4
* Upgrade to Picturefill 3.0.1
* Clean up inline docs.

= 2.5.2 =
* Numerous performance and usability improvements
* Pass height and width to `tevkori_get_sizes()`
* Improved regex in display filter
* Avoid calling `wp_get_attachment_image_src()` in srcset functions
* Improved coding standards
* Removed second regular expression in content filter
* Improved cache warning function
* Change default `$size` value for all functions to 'medium'

= 2.5.1 =
* Query all images in single request before replacing
* Minor fix to prevent a potential undefined variable notice
* Remove third fallback query from the display filter

= 2.5.0 =
* Responsify all post images by adding `srcset` and `sizes` through a display filter.
* Improve method used to build paths in `tevkori_get_srcset_array()`
* Added Linthub config files
* Returns single source arrays in `tevkori_get_srcset_array()`
* Add tests for PHP7 to our Travis matrix
* Add test coverage for `tevkori_filter_attachment_image_attributes()`

= 2.4.0 =
* Added filter for `tevkori_get_sizes`, with tests
* Added Composer support
* Compare aspect ratio in relative values, not absolute values
* Cleanup of code style and comments added
* Added PHP 5.2 to our Travis test matrix
* Fixed unit test loading
* Preventing duplicates in srcset array
* Updated docs for advanced image compression
* Formatting cleanup in readme.md
* Bump plugin 'Tested up to:' value to 4.3
* Remove extra line from readme.txt
* Added changelog items from 2.3.1 to the readme.txt file
* Added 'sudo: false' to travis.ci to use new TravisCI infrastructure
* Removing the srcset and sizes attributes if there is only one source present for the image
* Use edited image hash to filter out originals from edited images
* Make output of `tevkori_get_srcset_array` filterable

= 2.3.1 =
* First char no longer stripped from file name if there's no slash
* Adding test for when uploads directory not organized by date
* Don't calculate a srcset when the image data returns no width
* Add test for image_downsize returning 0 as a width

= 2.3.0 =
* Improved performance of get_srcset_array
* Added advanced image compression option (available by adding hook to functions.php)
* Duplicate entires now filtered out from srcset array
* Upgrade Picturefill to 2.3.1
* Refactoring plugin JavaScript, including a switch to ajax for updating the srcset value when the image is changed in the editor
* Now using `wp_get_attachment_image_attributes` filter for post thumbnails
* Readme and other general code typo fixes
* Gallery images will now contain a srcset attribute

= 2.2.1 =
* Patch fixing missing JavaScript error

= 2.2.0 =
* The mandatory sizes attribute is now included on all images
* Updated to Picturefill v2.3.0
* Extensive documentation included in readme
* Integrated testing with Travis CLI
* Check if wp.media exists before running JavaScript
* Account for rounding variance when matching ascpect ratios

= 2.1.1 =
* Adding in wp-tevko-responsive-images.js after file not found to be in WordPress repository
* Adjusts the aspect ratio check in `tevkori_get_srcset_array()` to account for rounding variance

= 2.1.0 =
* **This version introduces a breaking change**: There are now two functions. One returns an array of srcset values, and the other returns a string with the `srcset=".."` html needed to generate the responsive image. To retrieve the srcset array, us `tevkori_get_srcset_array( $id, $size )`
* When the image size is changed in the post editor, the srcset values will adjust to match the change.

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
* Works for cropped images!
* Backwards compatible (images added before plugin install will still be responsive)!
