RICG-responsive-images
---

[![Build Status](https://travis-ci.org/ResponsiveImagesCG/wp-tevko-responsive-images.svg?branch=dev)](https://travis-ci.org/ResponsiveImagesCG/wp-tevko-responsive-images)

Bringing automatic default responsive images to WordPress.

This plugin works by including all available image sizes for each image upload. Whenever WordPress outputs the image through the media uploader, or whenever a featured image is generated, those sizes will be included in the image tag via the [srcset](http://css-tricks.com/responsive-images-youre-just-changing-resolutions-use-srcset/) attribute.

## Contribution Guidelines

Please submit pull requests to our dev branch. If your contribution requires such, please aim to include appropriate tests with your pr as well.

## Documentation

### For General Users

No configuration is needed! Just install the plugin and enjoy automatic responsive images!

### For Theme Developers

This plugin includes several functions that can be used by theme and plugin developers in templates.

### Advanced Image Compression

Advanced image compression is an experimental image editor that makes use of ImageMagick's compression setting to deliver deliver higher quality images at a smaller file sizes. As such, **ImageMagick is required for this feature to work**. To learn more about the actual compression settings being used, read Dave Newton's [excellent writeup at Smashing Magazine](http://www.smashingmagazine.com/2015/06/efficient-image-resizing-with-imagemagick/).

To enable, place the following code in your `functions.php` file

```
function custom_theme_setup() {
	add_theme_support( 'advanced-image-compression' );
}
add_action( 'after_setup_theme', 'custom_theme_setup' );
```

***Known issues:***
* Some people have encountered memory limits when uploading large files with the advanced image compression settings enabled (see [#150](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/issues/150)).

---

### Function/Hook Reference

#### Function wp_get_attachment_image_srcset

`wp_get_attachment_image_srcset( $attachment_id, $size = 'medium', $image_meta = null )`

Retrieves the value for an image attachment's `srcset` attribute.

**Return:**
(string|bool) A `srcset` value string or false.

##### Parameters

**$attachment_id** (int)
Image attachment ID.

**$size** (array|string)
(Optional) Image size. Accepts any valid image size ('thumbnail', 'medium', etc.), or an array of width and height values in pixels (in that order). Default 'medium'.

**$image_meta** (array)
(Optional) The image meta data as returned by `wp_get_attachment_metadata()`. Default null.

##### Uses

`wp_calculate_image_srcset()`

##### Usage Example

```
<?php
$srcset_value = wp_get_attachment_image_srcset( 11, 'medium' );
$srcset = $srcset_value ? ' srcset="' . esc_attr( $srcset_value ) . '"' : '';
?>

<img src="myimg.png"<?php echo $srcset; ?> sizes="{{custom sizes value}}">
```

##### Note

By default, the maximum width of images that are included in the `srcset` is 1600 pixels. You can override this default by adding a filter to `max_srcset_image_width`.

---

#### Function wp_calculate_image_srcset

`wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id = 0 )`

A helper function to calculate the image sources to include in a `srcset` attribute.

**Return:** (string|bool)
The `srcset` attribute value. False on error or when only one source exists.

##### Parameters

**$size_array** (array)
Array of width and height values in pixels (in that order).

**$image_src** (string)
The `src` of the image.

**$image_meta** (array)
The image meta data as returned by `wp_get_attachment_metadata()`.

**$attachment_id** (int)
(Optional) Image attachment ID. Default 0.

##### Used by

`wp_get_attachment_image_srcset()`

##### Usage Example

```
<?php
$image_meta = wp_get_attachment_metadata( 11 );
$image = wp_get_attachment_image_src( 11, 'medium' );
if ( $image ) {
	$image_src = $image[0];
	$size_array = array(
		absint( $image[1] ),
		absint( $image[2] )
	);
}
$srcset_value = wp_calculate_image_srcset( $size_array, $image_src, $image_meta );
$srcset = $srcset_value ? ' srcset="' . esc_attr( $srcset_value ) . '"' : '';
?>

<img src="myimg.png"<?php echo $srcset; ?> sizes="{{custom sizes value}}">
```

##### Note

By default, the maximum width of images that are included in the `srcset` is 1600 pixels. You can override this default by adding a filter to `max_srcset_image_width`.

---

#### Hook max_srcset_image_width

`apply_filters( 'max_srcset_image_width', 1600, $size_array )`

Filter the maximum image width to be included in a `srcset` attribute.

##### Parameters

**$max_width** (int)
The maximum image width to be included in the `srcset`. Default '1600'.

**$size_array** (array)
Array of width and height values in pixels (in that order).

##### Used by

`wp_calculate_image_srcset()`

---

#### Hook wp_calculate_image_srcset

`apply_filters( 'wp_calculate_image_srcset', $sources, $attachment_id, $size_array, $image_meta )`

Filter an image's `srcset` sources.

##### Parameters

**$sources** (array)
One or more arrays of source data to include in the `srcset`.

```
$width (array) {
	$url (string)			The URL of an image source.
	$descriptor (string)	The descriptor type used in the image candidate string,
							either 'w' or 'x'.
	$value (int)			The source width, if paired with a 'w' descriptor or a
							pixel density value if paired with an 'x' descriptor.
}
```

**$attachment_id** (int)
Image attachment ID or 0.

**$size_array** (array)
Array of width and height values in pixels (in that order).

**$image_meta** (array)
The image meta data as returned by `wp_get_attachment_metadata()`.

##### Used by

`wp_calculate_image_srcset()`

---

#### Function wp_get_attachment_image_sizes

`wp_get_attachment_image_sizes( $attachment_id, $size = 'medium', $image_meta = null )`

Retrieves the value for an image attachment's `sizes` attribute.

**Return:** (string|bool)
A valid source size value for use in a `sizes` attribute or false.

##### Parameters

**$attachment_id** (int)
Image attachment ID.

**$size** (array|string)
(Optional) Image size. Accepts any valid image size name ('thumbnail', 'medium', etc.), or an array of width and height values in pixels (in that order). Default 'medium'.

**$image_meta** (array)
(Optional) The image meta data as returned by `wp_get_attachment_metadata()`. Default null.

##### Uses

`wp_calculate_image_sizes()`

##### Usage Example

```
<?php
$sizes_value = wp_get_attachment_image_sizes( 11, 'medium' );
$sizes = $sizes_value ? ' sizes="' . esc_attr( $sizes_value ) . '"' : '';
?>

<img src="myimg.png"<?php echo $sizes; ?> srcset="{{custom srcset value}}">
```

##### Note

By default, the sizes attribute will be declared as 100% of the viewport width when the viewport width is smaller than the width of the image, or to the width of the image itself when the viewport is larger than the image:

`(max-width: {{image-width}}) 100vw, {{image-width}}`

You can override this default by adding a filter to `wp_calculate_image_sizes`.

---

#### Function wp_calculate_image_sizes

`wp_calculate_image_sizes( $size, $image_src, $image_meta, $attachment_id = 0 )`

Creates the `sizes` attribute value for an image.

**Return:** (string|bool)
A valid source size value for use in a `sizes` attribute or false.

##### Parameters

**$size** (array|string)
Image size. Accepts any valid image size name ('thumbnail', 'medium', etc.), or an array of width and height values in pixels (in that order).

**$image_src** (string)
(Optional) The URL to the image file. Default null.

**$image_meta** (array)
(Optional) The image meta data as returned by `wp_get_attachment_metadata()`. Default null.

**$attachment_id** (int)
(Optional) Image attachment ID. Default 0.

Either `$image_meta` or `$attachment_id` is needed when using the image size name as argument for `$size`.

##### Used by

`wp_get_attachment_image_sizes()`

##### Usage Example

```
<?php
$sizes_value = wp_calculate_image_sizes( 'medium', $image_src = null, $image_meta = null, 11 );
$sizes = $sizes_value ? ' sizes="' . esc_attr( $sizes_value ) . '"' : '';
?>

<img src="myimg.png"<?php echo $sizes; ?> srcset="{{custom srcset value}}">
```

##### Note

By default, the sizes attribute will be declared as 100% of the viewport width when the viewport width is smaller than the width of the image, or to the width of the image itself when the viewport is larger than the image:

`(max-width: {{image-width}}) 100vw, {{image-width}}`

You can override this default by adding a filter to `wp_calculate_image_sizes`.

---

#### Hook wp_calculate_image_sizes

`apply_filters( 'wp_calculate_image_sizes', $sizes, $size, $image_src, $image_meta, $attachment_id )`

Filter the output of `wp_calculate_image_sizes()`.

##### Parameters

**$sizes** (string)
A source size value for use in a `sizes` attribute.

**$size** (array|string)
Requested size. Image size name or array of width and height values in pixels (in that order).

**$image_src** (string|null)
The URL to the image file or null.

**$image_meta** (array|null)
The image meta data as returned by `wp_get_attachment_metadata()` or null.

**$attachment_id** (int)
Image attachment ID of the original image or 0.

##### Used by

`wp_calculate_image_sizes()`

---

### Backward Compatibility

The following filters are used for backward compatibility. If the described case is not applicable you may want to remove the filter from its hook.

#### data-sizes

Prior to version 2.5 a `srcset` and `data-sizes` attribute were added to the image while inserting the image in the content and we used a content filter to replace `data-sizes` by `sizes`. As from 2.5 both `srcset` and `sizes` are added to images using a content filter, but images that already have a `srcset` attribute are skipped. For this reason we still replace `data-sizes` by `sizes`.
If you did not use the plugin before version 2.5 or if you have removed `data-sizes` from your content you can remove the filter:

```
remove_filter( 'the_content', 'tevkori_replace_data_sizes' );
```

#### tevkori_get_sizes() $args param and filter

The deprecated function `tevkori_get_sizes()` had an `$args` param and a `tevkori_image_sizes_args` filter. To make those still work we added a shim. If you do not use `tevkori_get_sizes()` in your templates, or at least not pass an argument to the `$args` param, and if you don't use the deprecated `tevkori_image_sizes_args` filter hook, you can remove the filter:

```
remove_filter( 'wp_calculate_image_sizes', '_tevkori_image_sizes_args_shim', 1, 5 );
```

#### wp_get_attachment_image_sizes filter

In version 3.0 we introduced a new filter: `wp_get_attachment_image_sizes`. In version 3.1 this has been replaced by `wp_calculate_image_sizes`. If you don't use the `wp_get_attachment_image_sizes` filter you can remove the filter that has been added for backward compatibility:

```
remove_filter( 'wp_calculate_image_sizes', 'wp_get_attachment_image_sizes_filter_shim', 10, 5 );
```

---

### Dependencies

The only external dependency included in this plugin is [Picturefill](http://scottjehl.github.io/picturefill/) - v3.0.1. If you would like to remove Picturefill (see notes about [browser support](http://scottjehl.github.io/picturefill/#support)), add the following to your functions.php file:

    function mytheme_dequeue_scripts() {
      wp_dequeue_script('picturefill');
    }

    add_action('wp_enqueue_scripts', 'mytheme_dequeue_scripts');

We use a hook because if you attempt to dequeue a script before it's enqueued, wp_dequeue_script has no effect. (If it's still being loaded, you may need to specify a [priority](http://codex.wordpress.org/Function_Reference/add_action).)

## Version

3.0.0

## Changelog

**3.0.0**

- Deprecates all core functions that will be merged into WordPress core in 4.4.
- Adds compatibility shims for sites using the plugin's internal functions and hooks.
- Adds a new display filter callback which can be use as general utility function for adding srcset and sizes attributes.
- Fixes a bug when `wp_get_attachment_metadata()` failed to return an array.
- Update our tests to be compatible with WordPress 4.4
- Upgrade to Picturefill 3.0.1
- Clean up inline docs.

**2.5.2**

- Numerous performance and usability improvements
- Pass height and width to `tevkori_get_sizes()`
- Improved regex in display filter
- Avoid calling `wp_get_attachment_image_src()` in srcset functions
- Improved coding standards
- Removed second regular expression in content filter
- Improved cache warning function
- Change default `$size` value for all functions to 'medium'

**2.5.1**

- Query all images in single request before replacing
- Minor fix to prevent a potential undefined variable notice
- Remove third fallback query from the display filter

**2.5.0**

- Responsify all post images by adding `srcset` and `sizes` through a display filter.
- Improve method used to build paths in `tevkori_get_srcset_array()`
- Added Linthub config files
- Returns single source arrays in `tevkori_get_srcset_array()`
- Add tests for PHP7 to our Travis matrix
- Add test coverage for `tevkori_filter_attachment_image_attributes()`

**2.4.0**

- Added filter for `tevkori_get_sizes`, with tests
- Added Composer support
- Compare aspect ratio in relative values, not absolute values
- Cleanup of code style and comments added
- Added PHP 5.2 to our Travis test matrix
- Fixed unit test loading
- Preventing duplicates in srcset array
- Updated docs for advanced image compression
- Formatting cleanup in readme.md
- Bump plugin 'Tested up to:' value to 4.3
- Remove extra line from readme.txt
- Added changelog items from 2.3.1 to the readme.txt file
- Added 'sudo: false' to travis.ci to use new TravisCI infrastructure
- Removing the srcset and sizes attributes if there is only one source present for the image
- Use edited image hash to filter out originals from edited images
- Make output of `tevkori_get_srcset_array` filterable

**2.3.1**

- First char no longer stripped from file name if there's no slash
- Adding test for when uploads directory not organized by date
- Don't calculate a srcset when the image data returns no width
- Add test for image_downsize returning 0 as a width

**2.3.0**

- Improved performance of `get_srcset_array`
- Added advanced image compression option (available by adding hook to functions.php)
- Duplicate entires now filtered out from srcset array
- Upgrade Picturefill to 2.3.1
- Refactoring plugin JavaScript, including a switch to ajax for updating the srcset value when the image is changed in the editor
- Now using `wp_get_attachment_image_attributes` filter for post thumbnails
- Readme and other general code typo fixes
- Gallery images will now contain a srcset attribute

**2.2.1**

- JavaScript patch for WordPress

**2.2.0**

- The mandatory sizes attribute is now included on all images
- Updated to Picturefill v2.3.0
- Extensive documentation included in readme
- Integrated testing with Travis CLI
- Check if wp.media exists before running JavaScript
- Account for rounding variance when matching ascpect ratios

**2.1.1**

- Adding in wp-tevko-responsive-images.js after file not found to be in WordPress repository
- Adjusts the aspect ratio check in `tevkori_get_srcset_array()` to account for rounding variance

**2.1.0**

- **This version introduces a breaking change**: There are now two functions. One returns an array of srcset values, and the other returns a string with the `srcset=".."` html needed to generate the responsive image. To retrieve the srcset array, use `tevkori_get_srcset_array( $id, $size )`
- When the image size is changed in the post editor, the srcset values will adjust to match the change.

**2.0.2**

- A bugfix correcting a divide by zero error. Some users may have seen this after upgrading to 2.0.1

**2.0.1**

- Only outputs the default WordPress sizes, giving theme developers the option to extend as needed
- Added support for featured images

**2.0.0**

 - Uses [Picturefill 2.2.0 (Beta)](http://scottjehl.github.io/picturefill/)
 - Scripts are output to footer
 - Image sizes adjusted
 - Most importantly, the srcset syntax is being used
 - The structure of the plugin is significantly different. The plugin now works by extending the default WordPress image tag functionality to include the srcset attribute.
 - Works for cropped images!
 - Backwards compatible (images added before plugin install will still be responsive)!
