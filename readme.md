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

To enable, place the following code in your `functions.php` file -
```
function custom_theme_setup() {
	add_theme_support( 'advanced-image-compression' );
}
add_action( 'after_setup_theme', 'custom_theme_setup' );
```

***Known issues:***
* Some people have encountered memory limits when uploading large files with the advanced image compression settings enabled (see [#150](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/issues/150)).


---

#### tevkori_get_sizes( $id, $size, $args )

Returns a valid source size value for use in a 'sizes' attribute. The parameters include the ID of the image, the default size of the image, and an array or string containing of size information. The ID parameter is required. [Link](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/blob/master/wp-tevko-responsive-images.php#L28)

***Usage Example***

```
<img src="myimg.png" sizes="<?php echo tevkori_get_sizes( 11, 'medium' ); ?>" >
```

By default, the sizes attribute will be declared as 100% of the viewport width when the viewport width is smaller than the width of the image, or to the width of the image itself when the viewport is larger than the image. In other words, this:

`(max-width: {{image-width}}) 100vw, {{image-width}}`

You can override those defaults by passing your own size values as set of arrays to the `$args` parameter.

*Example:*

```
$args = array(
  'sizes' => array(
    array(
      'size_value' 	=> '10em',
      'mq_value'		=> '60em',
      'mq_name'			=> 'min-width'
    ),
    array(
      'size_value' 	=> '20em',
      'mq_value'		=> '30em',
      'mq_name'			=> 'min-width'
    ),
    array(
      'size_value'	=> 'calc(100vm - 30px)'
    ),
  )
);

$sizes = tevkori_get_sizes( $id, 'medium', $args );
```

Which would output a sizes value of:
`(min-width: 60em) 10em, (min-width: 30em) 20em, calc(100vm - 30px)`

---

#### tevkori_get_sizes_string( $id, $size, $args)

Returns A full 'sizes' attribute. The parameters include the ID of the image, the default size of the image, and an array or string containing of size information. The ID parameter is required.

***Usage Example***

```
<img src="myimg.png" <?php echo tevkori_get_sizes_string( 11, 'medium' ); ?> >
```

---
#### tevkori_get_srcset_array( $id, $size )

Returns an array of image source candidates for use in a 'srcset' attribute. The parameters include the ID of the image, the default size of the image, and An array of of srcset values. The ID parameter is required. [Link](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/blob/master/wp-tevko-responsive-images.php#L132)

***Usage Example***

```
$sources = tevkori_get_srcset_array( 11, 'medium' );

// Optionally remove a specific source from the srcset list.
foreach( $sources as $key => $source ) {
	if ( strpos( $source, '300w' ) ) {
		unset( $s[$key] );
	}
}

<img src="myimg.png" srcset="<?php implode( ', ', $sources ); ?>" >
```

---

#### tevkori_get_srcset_string( $id, $size )

Returns A full 'srcset' attribute. The parameters include the ID of the image and its default size. The ID parameter is required. [Link](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/blob/master/wp-tevko-responsive-images.php#L196)

***Usage Example***

```
<img src="myimg.png" <?php echo tevkori_get_srcset_string( 11, 'medium' ); ?> >
```

**Dependencies**

The only external dependency included in this plugin is [Picturefill](http://scottjehl.github.io/picturefill/) - v2.3.0. If you would like to remove Picturefill (see notes about [browser support](http://scottjehl.github.io/picturefill/#support)), add the following to your functions.php file:

    function mytheme_dequeue_scripts() {
      wp_dequeue_script('picturefill');
    }

    add_action('wp_enqueue_scripts', 'mytheme_dequeue_scripts');

We use a hook because if you attempt to dequeue a script before it's enqueued, wp_dequeue_script has no effect. (If it's still being loaded, you may need to specify a [priority](http://codex.wordpress.org/Function_Reference/add_action).)

## Version

2.5.0

## Changelog

- Responsify all post images by adding `srcset` and `sizes` through a display filter.
- Improve method used to build paths in `tevkori_get_srcset_array()`
- Adds linthub config files
- Returns single source arrays in `tevkori_get_srcset_array()`
- Add tests for PHP7 to our Travis matrix
- Add test coverage for `tevkori_filter_attachment_image_attributes()` 

**2.4.0**

- Added filter for tevkori_get_sizes, with tests
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
- Make output of tevkori_get_srcset_array filterable

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
- Refactoring plugin JS, including a switch to ajax for updating the srcset value when the image is changed in the editor
- Now using wp_get_attachment_image_attributes filter for post thumbnails
- Readme and other general code typo fixes
- Gallery images will now contain a srcset attribute

**2.2.1**

- JS patch for WordPress

**2.2.0**

- The mandatory sizes attribute is now included on all images
- Updated to Picturefill v2.3.0
- Extensive documentation included in readme
- Integrated testing with Travis CLI
- Check if wp.media exists before running JS
- Account for rounding variance when matching ascpect ratios

**2.1.1**

- Adding in wp-tevko-responsive-images.js after file not found to be in wordpress repository
- Adjusts the aspect ratio check in tevkori_get_srcset_array() to account for rounding variance

**2.1.0**

- **This version introduces a breaking change** - there are now two functions. One returns an array of srcset values, and the other returns a string with the ``srcset=".."`` html needed to generate the responsive image. To retrieve the srcset array, use ``tevkori_get_srcset_array( $id, $size )``

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
