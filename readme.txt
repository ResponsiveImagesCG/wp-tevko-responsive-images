=== RICG Responsive Images ===
Contributors: tevko, wilto, joemcgill, jaspermdegroot, chriscoyier, Michael McGinnis, ryelle, drrobotnik, nacin , georgestephanis, helen, wordpressdotorg, Bocoup
Donate link: https://app.etapestry.com/hosted/BoweryResidentsCommittee/OnlineDonation.html
Tags: Responsive, Images, Responsive Images, SRCSET, Picturefill
Requires at least: 4.1
Tested up to: 4.3
Stable tag: 2.4.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Bringing automatic default responsive images to WordPress.

== Description ==

Bringing automatic default responsive images to WordPress.

This plugin works by including all available image sizes for each image upload. Whenever WordPress outputs the image through the media uploader, or whenever a featured image is generated, those sizes will be included in the image tag via the srcset attribute.

**Full documentation and contributor guidelines can be found on [Github](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images)**

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. If you'd like to enable the advanced image compression feature, Please see the instructions at https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/tree/dev#advanced-image-compression

== Changelog ==

= 2.5.0 =
* Responsify all post images by adding `srcset` and `sizes` through a display filter.
* Improve method used to build paths in `tevkori_get_srcset_array()`
* Adds linthub config files
* Returns single source arrays in `tevkori_get_srcset_array()`
* Add tests for PHP7 to our Travis matrix
* Add test coverage for `tevkori_filter_attachment_image_attributes()` 

= 2.4.0 =
* Added filter for tevkori_get_sizes, with tests
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
* Make output of tevkori_get_srcset_array filterable

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
* Refactoring plugin JS, including a switch to ajax for updating the srcset value when the image is changed in the editor
* Now using wp_get_attachment_image_attributes filter for post thumbnails
* Readme and other general code typo fixes
* Gallery images will now contain a srcset attribute

= 2.2.1 =
* Patch fixing missing javascript error

= 2.2.0 =
* The mandatory sizes attribute is now included on all images
* Updated to Picturefill v2.3.0
* Extensive documentation included in readme
* Integrated testing with Travis CLI
* Check if wp.media exists before running JS
* Account for rounding variance when matching ascpect ratios

= 2.1.1 =
* Adding in wp-tevko-responsive-images.js after file not found to be in WordPress repository
* Adjusts the aspect ratio check in tevkori_get_srcset_array() to account for rounding variance

= 2.1.0 =
* **This version introduces a breaking change** - there are now two functions. One returns an array of srcset values, and the other returns a string with the `srcset=".."` html needed to generate the responsive image. To retrieve the srcset array, us `tevkori_get_srcset_array( $id, $size )`
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
