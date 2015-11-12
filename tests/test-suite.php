<?php

class RICG_Responsive_Images_Tests extends WP_UnitTestCase {

	protected static $large_id;

	protected static $test_file_name;

	public static function setUpBeforeClass() {
		self::$test_file_name = dirname(__FILE__) . '/data/test-large.png';
		self::$large_id = self::create_upload_object( self::$test_file_name );
	}

	public static function tearDownAfterClass() {
		wp_delete_attachment( self::$large_id );
	}

	public static function create_upload_object( $filename, $parent = 0 ) {
		$contents = file_get_contents($filename);
		$upload = wp_upload_bits(basename($filename), null, $contents);
		$type = '';

		if ( ! empty($upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( $mime )
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title'     => basename( $upload['file'] ),
			'post_content'   => '',
			'post_type'      => 'attachment',
			'post_parent'    => $parent,
			'post_mime_type' => $type,
			'guid'           => $upload[ 'url' ],
		);

		// Save the data.
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;
	}

	/* OUR TESTS */

	/**
	 * @expectedDeprecated tevkori_get_sizes
	 */
	function test_tevkori_get_sizes() {
		// Make an image.
		$id = self::$large_id;

		global $content_width;

		// Test sizes against the default WP sizes.
		$intermediates = array( 'thumbnail', 'medium', 'large' );

		foreach( $intermediates as $int ) {
			$width = get_option( $int . '_size_w' );

			$width = ( $width <= $content_width ) ? $width : $content_width;

			$expected = '(max-width: ' . $width . 'px) 100vw, ' . $width . 'px';
			$sizes = tevkori_get_sizes( $id, $int );

			$this->assertSame( $expected, $sizes );
		}
	}

	/**
	 * @expectedDeprecated tevkori_get_sizes
	 */
	function test_tevkori_get_sizes_with_args() {
		// Make an image.
		$id = self::$large_id;

		$args = array(
			'sizes' => array(
				array(
					'size_value' => '10em',
					'mq_value'   => '60em',
					'mq_name'    => 'min-width'
				),
				array(
					'size_value' => '20em',
					'mq_value'   => '30em',
					'mq_name'    => 'min-width'
				),
				array(
					'size_value' => 'calc(100vm - 30px)'
				),
			)
		);

		$expected = '(min-width: 60em) 10em, (min-width: 30em) 20em, calc(100vm - 30px)';
		$sizes = tevkori_get_sizes( $id, 'medium', $args );

		$this->assertSame( $expected, $sizes );
	}

	/**
	 * @expectedDeprecated tevkori_get_sizes
	 */
	function test_filter_tevkori_get_sizes_string() {
		// Add our test filter.
		add_filter( 'tevkori_image_sizes_args', array( $this, '_test_tevkori_image_sizes_args' ) );

		// Set up our test.
		$id = self::$large_id;
		$sizes = tevkori_get_sizes($id, 'medium');

		// Evaluate that the sizes returned is what we expected.
		$this->assertSame( $sizes, '100vm' );

		remove_filter( 'tevkori_image_sizes_args', array( $this, '_test_tevkori_image_sizes_args' ) );
	}

	/**
	 * A simple test filter for tevkori_get_sizes().
	 */
	function _test_tevkori_image_sizes_args( $args ) {
		$args['sizes'] = "100vm";
		return $args;
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_filter_tevkori_srcset_array() {
		// Add test filter.
		add_filter( 'tevkori_srcset_array', array( $this, '_test_tevkori_srcset_array' ) );

		// Set up our test.
		$id = self::$large_id;
		$sizes = tevkori_get_srcset_array($id, 'medium');

		// Evaluate that the sizes returned is what we expected.
		foreach( $sizes as $width => $source ) {
			$this->assertTrue( $width <= 500 );
		}

		// Remove test filter.
		remove_filter( 'tevkori_srcset_array', array( $this, '_test_tevkori_srcset_array' ) );
	}

	/**
	 * A test filter for tevkori_get_srcset_array() that removes any sources
	 * that are larger than 500px wide.
	 */
	function _test_tevkori_srcset_array( $array ) {
		foreach ( $array as $size => $file ) {
			if ( $size > 500 ) {
				unset( $array[ $size ] );
			}
		}
		return $array;
	}

	/**
	 * @expectedDeprecated tevkori_get_sizes
	 * @expectedDeprecated tevkori_get_sizes_string
	 */
	function test_tevkori_get_sizes_string() {
		// Make an image.
		$id = self::$large_id;

		$sizes = tevkori_get_sizes( $id, 'medium' );
		$sizes_string = tevkori_get_sizes_string( $id, 'medium' );

		$expected = 'sizes="' . $sizes . '"';

		$this->assertSame( $expected, $sizes_string );
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_array() {
		// make an image
		$id = self::$large_id;
		$sizes = tevkori_get_srcset_array( $id, 'medium' );

		$year_month = date('Y/m');
		$image = wp_get_attachment_metadata( $id );

		foreach( $image['sizes'] as $name => $size ) {
			// Whitelist the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, array( 'medium', 'medium_large', 'large' ) ) ) {
				$expected[$size['width']] = 'http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/' . $size['file'] . ' ' . $size['width'] . 'w';
			}
		}

		// Add the full size width at the end.
		$expected[$image['width']] = 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w';

		$this->assertSame( $expected, $sizes );
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_array_random_size_name() {
		// Make an image.
		$id = self::$large_id;
		$sizes = tevkori_get_srcset_array( $id, 'foo' );

		$year_month = date('Y/m');
		$image = wp_get_attachment_metadata( $id );

		foreach( $image['sizes'] as $name => $size ) {
			// Whitelist the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, array( 'medium', 'medium_large', 'large' ) ) ) {
				$expected[$size['width']] = 'http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/' . $size['file'] . ' ' . $size['width'] . 'w';
			}
		}

		// Add the full size width at the end.
		$expected[$image['width']] = 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w';

		$this->assertSame( $expected, $sizes );
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_array_no_date_upoads() {
		// Save the current setting for uploads folders.
		$uploads_use_yearmonth_folders = get_option( 'uploads_use_yearmonth_folders' );

		// Disable date organized uploads.
		update_option( 'uploads_use_yearmonth_folders', 0 );

		// Make an image.
		$id = self::create_upload_object( self::$test_file_name );
		$sizes = tevkori_get_srcset_array( $id, 'medium' );
		$image = wp_get_attachment_metadata( $id );

		foreach( $image['sizes'] as $name => $size ) {
			// Whitelist the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, array( 'medium', 'medium_large', 'large' ) ) ) {
				$expected[$size['width']] = 'http://example.org/wp-content/uploads/' . $size['file'] . ' ' . $size['width'] . 'w';
			}
		}

		// Add the full size width at the end.
		$expected[$image['width']] = 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w';

		$this->assertSame( $expected, $sizes );

		// Leave the uploads option the way you found it.
		update_option( 'uploads_use_yearmonth_folders', $uploads_use_yearmonth_folders );
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_single_srcset() {
		// Make an image.
		$id = self::$large_id;
		/*
		 * In our tests, thumbnails would only return a single srcset candidate,
		 * in which case we don't bother returning a srcset array.
		 */
		$sizes = tevkori_get_srcset( $id, 'thumbnail' );

		$this->assertTrue( 1 === count( tevkori_get_srcset_array( $id, 'thumbnail' ) ) );
		$this->assertFalse( tevkori_get_srcset( $id, 'thumbnail' ) );
	}

	/**
	 * Test for filtering out leftover sizes after an image is edited.
	 * @group 155
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_array_with_edits() {
		// Make an image.
		$id = self::$large_id;

		/*
		 * For this test we're going to mock metadata changes from an edit.
		 * Start by getting the attachment metadata.
		 */
		$meta = wp_get_attachment_metadata( $id );

		// Mimick hash generation method used in wp_save_image().
		$hash = 'e' . time() . rand(100, 999);

		// Replace file paths for full and medium sizes with hashed versions.
		$filename_base = basename( $meta['file'], '.png' );
		$meta['file'] = str_replace( $filename_base, $filename_base . '-' . $hash, $meta['file'] );
		$meta['sizes']['medium']['file'] = str_replace( $filename_base, $filename_base . '-' . $hash, $meta['sizes']['medium']['file'] );

		// Save edited metadata.
		wp_update_attachment_metadata( $id, $meta );

		// Get the edited image and observe that a hash was created.
		$img_url = wp_get_attachment_url( $id );

		// Calculate a srcset array.
		$sizes = tevkori_get_srcset_array( $id, 'medium' );

		// Test to confirm all sources in the array include the same edit hash.
		foreach ( $sizes as $size ) {
			$this->assertTrue( false !== strpos( $size, $hash ) );
		}
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_array_false() {
		// Make an image.
		$id = self::$large_id;
		$sizes = tevkori_get_srcset_array( 99999, 'foo' );

		// For canola.jpg we should return.
		$this->assertFalse( $sizes );
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_array
	 */
	function test_tevkori_get_srcset_array_no_width() {
		// Filter image_downsize() output.
		add_filter( 'image_downsize', array( $this, '_filter_image_downsize' ), 10, 3 );

		// Make our attachment.
		$id = self::create_upload_object( self::$test_file_name );
		$srcset = tevkori_get_srcset_array( $id, 'medium' );

		// The srcset should be false
		$this->assertFalse( $srcset );
		// Remove filter.
		remove_filter( 'image_downsize', array( $this, '_filter_image_downsize' ) );
	}

	/**
	 * Helper funtion to filter image_downsize and return zero values for width and height.
	 */
	public function _filter_image_downsize( $out, $id, $size ) {
		$img_url = wp_get_attachment_url($id);
		return array( $img_url, 0, 0 );
	}

	/**
	 * @expectedDeprecated tevkori_get_srcset_string
	 */
	function test_tevkori_get_srcset_string() {
		// Make an image.
		$id = self::$large_id;
		$sizes = tevkori_get_srcset_string( $id, 'full-size' );

		$sizes = tevkori_get_srcset_string( $id, 'full' );
		$image = wp_get_attachment_metadata( $id );
		$year_month = date('Y/m');

		$srcset = '';

		foreach( $image['sizes'] as $name => $size ) {
			// Whitelist the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, array( 'medium', 'medium_large', 'large' ) ) ) {
				$srcset .= 'http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/' . $size['file'] . ' ' . $size['width'] . 'w, ';
			}
		}
		// Add the full size width at the end.
		$srcset .= 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w';

		$expected = sprintf( 'srcset="%s"', $srcset );

		$this->assertSame( $expected, $sizes );
	}

	/**
	 * @group 159
	 */
	function test_tevkori_filter_attachment_image_attributes() {
		// Make an image.
		$id = self::$large_id;

		// Get attachment post data.
		$attachment = get_post( $id );
		$image = wp_get_attachment_image_src( $id, 'medium' );
		list( $src, $width, $height ) = $image;

		// Create dummy attributes array.
		$attr = array(
			'src'    => $src,
			'width'  => $width,
			'height' => $height,
		);

		// Apply filter.
		$resp_attr = tevkori_filter_attachment_image_attributes( $attr, $attachment, 'medium' );

		// Test output.
		$this->assertTrue( isset( $resp_attr['srcset'] ) );
		$this->assertTrue( isset( $resp_attr['sizes'] ) );
	}

	/**
	 * @group 159
	 */
	function test_tevkori_filter_attachment_image_attributes_thumbnails() {
		// Make an image.
		$id = self::$large_id;

		// Get attachment post data.
		$attachment = get_post( $id );
		$image = wp_get_attachment_image_src( $id, 'thumbnail' );
		list( $src, $width, $height ) = $image;

		// Create dummy attributes array.
		$attr = array(
			'src'    => $src,
			'width'  => $width,
			'height' => $height,
		);

		// Apply filter.
		$resp_attr = tevkori_filter_attachment_image_attributes( $attr, $attachment, 'thumbnail' );

		// Test output.
		$this->assertFalse( isset( $resp_attr['srcset'] ) );
		$this->assertFalse( isset( $resp_attr['sizes'] ) );
	}

	/**
	 * @group 170
	 * @expectedDeprecated tevkori_get_srcset_string
	 * @expectedDeprecated tevkori_get_sizes_string
	 * @expectedDeprecated tevkori_filter_content_images
	 */
	function test_tevkori_filter_content_images() {
		// Make an image.
		$id = self::$large_id;

		$srcset = tevkori_get_srcset_string( $id, 'medium' );
		$sizes = tevkori_get_sizes_string( $id, 'medium' );

		// Function used to build HTML for the editor.
		$img = get_image_tag( $id, '', '', '', 'medium' );
		$img_no_size = str_replace( 'size-', '', $img );
		$img_no_size_id = str_replace( 'wp-image-', 'id-', $img_no_size );

		// Manually add srcset and sizes to the markup from get_image_tag().
		$respimg = preg_replace('|<img ([^>]+) />|', '<img $1 ' . $srcset . ' ' . $sizes . ' />', $img );
		$respimg_no_size = preg_replace('|<img ([^>]+) />|', '<img $1 ' . $srcset . ' ' . $sizes . ' />', $img_no_size );

		$content = '<p>Welcome to WordPress!  This post contains important information.  After you read it, you can make it private to hide it from visitors but still have the information handy for future reference.</p>
			<p>First things first:</p>

			%1$s

			<ul>
			<li><a href="http://wordpress.org" title="Subscribe to the WordPress mailing list for Release Notifications">Subscribe to the WordPress mailing list for release notifications</a></li>
			</ul>

			%2$s

			<p>As a subscriber, you will receive an email every time an update is available (and only then).  This will make it easier to keep your site up to date, and secure from evildoers.<br />
			When a new version is released, <a href="http://wordpress.org" title="If you are already logged in, this will take you directly to the Dashboard">log in to the Dashboard</a> and follow the instructions.<br />
			Upgrading is a couple of clicks!</p>

			%3$s

			<p>Then you can start enjoying the WordPress experience:</p>
			<ul>
			<li>Edit your personal information at <a href="http://wordpress.org" title="Edit settings like your password, your display name and your contact information">Users &#8250; Your Profile</a></li>
			<li>Start publishing at <a href="http://wordpress.org" title="Create a new post">Posts &#8250; Add New</a> and at <a href="http://wordpress.org" title="Create a new page">Pages &#8250; Add New</a></li>
			<li>Browse and install plugins at <a href="http://wordpress.org" title="Browse and install plugins at the official WordPress repository directly from your Dashboard">Plugins &#8250; Add New</a></li>
			<li>Browse and install themes at <a href="http://wordpress.org" title="Browse and install themes at the official WordPress repository directly from your Dashboard">Appearance &#8250; Add New Themes</a></li>
			<li>Modify and prettify your website&#8217;s links at <a href="http://wordpress.org" title="For example, select a link structure like: http://example.com/1999/12/post-name">Settings &#8250; Permalinks</a></li>
			<li>Import content from another system or WordPress site at <a href="http://wordpress.org" title="WordPress comes with importers for the most common publishing systems">Tools &#8250; Import</a></li>
			<li>Find answers to your questions at the <a href="http://wordpress.orgs" title="The official WordPress documentation, maintained by the WordPress community">WordPress Codex</a></li>
			</ul>';

		$content_unfiltered = sprintf( $content, $img, $img_no_size, $img_no_size_id );
		$content_filtered = sprintf( $content, $respimg, $respimg_no_size, $img_no_size_id );

		$this->assertSame( $content_filtered, tevkori_filter_content_images( $content_unfiltered ) );
	}

	/**
	 * @group 170
	 * @expectedDeprecated tevkori_filter_content_images
	 */
	function test_tevkori_filter_content_images_with_preexisting_srcset() {
		// Make an image.
		$id = self::$large_id;

		// Generate HTML and add a dummy srcset attribute.
		$image_html = get_image_tag( $id, '', '', '', 'medium' );
		$image_html = preg_replace('|<img ([^>]+) />|', '<img $1 ' . 'srcset="image2x.jpg 2x" />', $image_html );

		// The content filter should return the image unchanged.
		$this->assertSame( $image_html, tevkori_filter_content_images( $image_html ) );
	}

	function test_wp_calculate_image_srcset_animated_gifs() {
	// Mock meta for an animated gif.
	$image_meta = array(
		'width' => 1200,
		'height' => 600,
		'file' => 'animated.gif',
		'sizes' => array(
			'thumbnail' => array(
				'file' => 'animated-150x150.gif',
				'width' => 150,
				'height' => 150,
				'mime-type' => 'image/gif'
			),
			'medium' => array(
				'file' => 'animated-300x150.gif',
				'width' => 300,
				'height' => 150,
				'mime-type' => 'image/gif'
			),
			'large' => array(
				'file' => 'animated-1024x512.gif',
				'width' => 1024,
				'height' => 512,
				'mime-type' => 'image/gif'
			),
		)
	);

	$full_src  = 'http://example.org/wp-content/uploads/' . $image_meta['file'];
	$large_src = 'http://example.org/wp-content/uploads/' . $image_meta['sizes']['large']['file'];

	// Test with soft resized size array.
	$size_array = array(900, 450);

	// Full size GIFs should not return a srcset.
	$this->assertFalse( wp_calculate_image_srcset( $full_src, $size_array, $image_meta ) );
	// Intermediate sized GIFs should not include the full size in the srcset.
	$this->assertFalse( strpos( wp_calculate_image_srcset( $large_src, $size_array, $image_meta ), $full_src ) );
}

}
