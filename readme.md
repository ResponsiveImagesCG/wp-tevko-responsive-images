RICG-responsive-images
---

![Build Status](https://travis-ci.org/ResponsiveImagesCG/wp-tevko-responsive-images.svg)

Bringing automatic default responsive images to WordPress.

This plugin works by including all available image sizes for each image upload. Whenever WordPress outputs the image through the media uploader, or whenever a featured image is generated, those sizes will be included in the image tag via the [srcset](http://css-tricks.com/responsive-images-youre-just-changing-resolutions-use-srcset/) attribute.

##Contribution Guidelines

Please submit pull requests to our dev branch. If your contribution requires such, please aim to include appropriate tests with your pr as well.

##Documentation

###For General Users

No configuration is needed! Just install the plugin and enjoy automatic responsive images!

###For Theme Developers

This plugin includes several functions that can be used by theme and plugin developers in templates.

####tevkori_get_sizes( $id, $size, $args )

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

####tevkori_get_sizes_string( $id, $size, $args)

Returns A full 'sizes' attribute. The parameters include the ID of the image, the default size of the image, and an array or string containing of size information. The ID parameter is required.

***Usage Example***

```
<img src="myimg.png" <?php echo tevkori_get_sizes_string( 11, 'medium' ); ?> >
```

---
####tevkori_get_srcset_array( $id, $size )

Returns an array of image source candidates for use in a 'srcset' attribute. The parameters include the ID of the image, the default size of the image, and An array of of srcset values. The ID parameter is required. [Link](https://github.com/ResponsiveImagesCG/wp-tevko-responsive-images/blob/master/wp-tevko-responsive-images.php#L132)

***Usage Example***

```

$srcset = tevkori_get_srcset_array( 11, 'medium' );

foreach( $srcset as $source ) {
 if ( false === strpos(' 900w', $source) ) {
  $srcset[] = $source;
 }
}

<img src="myimg.png" srcset="<?php implode( ', ', $srcset ); ?>" >
```

---

####tevkori_get_srcset_string( $id, $size )

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

##Version

2.2.1

##Changelog

- JS patch for wordpress

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
