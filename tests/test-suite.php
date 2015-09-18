<?php

class SampleTest extends WP_UnitTestCase {

	function tearDown() {
		// Remove all uploads.
		$this->remove_added_uploads();
		parent::tearDown();
	}

	/**
	 * Helper function that creates an attachment in the DB.
	 * Copied from Tests_Post_Attachments Class in the WP Core test suite.
	 */
	private function _make_attachment( $upload, $parent_post_id = 0 ) {

		$type = '';
		if ( !empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent_post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $this->ids[] = $id;

	}

	/**
	 * Helper function to create an attachment from a file
	 *
	 * @uses _make_attachment
	 *
	 * @param 	string 			Optional. A path to a file. Default: DIR_TESTDATA.'/images/canola.JPG'.
	 * @return 	int|bool 		An attachment ID or false.
	 */
	private function _test_img( $file = null ) {

		$filename = $file ? $file : ( dirname(__FILE__) . '/data/test-large.png' );
		$contents = file_get_contents($filename);

		$upload = wp_upload_bits(basename($filename), null, $contents);
		$this->assertTrue( empty($upload['error']) );

		$id = $this->_make_attachment($upload);

		return $id;
	}

	/* OUR TESTS */

	function test_tevkori_get_sizes() {
		// make an image
		$id = $this->_test_img();

		global $content_width;

		// test sizes against the default WP sizes
		$intermediates = array('thumbnail', 'medium', 'large');

		foreach( $intermediates as $int ) {
			$width = get_option( $int . '_size_w' );

			// the sizes width gets constrained to $content_width by default
			if ( $content_width > 0 ) {
				$width = ( $width > $content_width ) ? $content_width : $width;
			}

			$expected = '(max-width: ' . $width . 'px) 100vw, ' . $width . 'px';
			$sizes = tevkori_get_sizes( $id, $int );

			$this->assertSame($expected, $sizes);
		}
	}

	function test_tevkori_get_sizes_with_args() {
		// make an image
		$id = $this->_test_img();

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

		$expected = '(min-width: 60em) 10em, (min-width: 30em) 20em, calc(100vm - 30px)';
		$sizes = tevkori_get_sizes( $id, 'medium', $args );

		$this->assertSame($expected, $sizes);
	}

	function test_filter_tevkori_get_sizes_string() {
		// Add our test filter.
		add_filter( 'tevkori_image_sizes_args', array( $this, '_test_tevkori_image_sizes_args' ) );

		// Set up our test.
		$id = $this->_test_img();
		$sizes = tevkori_get_sizes($id, 'medium');

		// Evaluate that the sizes returned is what we expected.
		$this->assertSame( $sizes, '100vm');

		remove_filter( 'tevkori_image_sizes_args', array( $this, '_test_tevkori_image_sizes_args' ) );
	}

	/**
	 * A simple test filter for tevkori_get_sizes().
	 */
	function _test_tevkori_image_sizes_args( $args ) {
		$args['sizes'] = "100vm";
		return $args;
	}

	function test_filter_tevkori_srcset_array() {
		// Add test filter
		add_filter( 'tevkori_srcset_array', array( $this, '_test_tevkori_srcset_array' ) );

		// Set up our test.
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array($id, 'medium');

		// Evaluate that the sizes returned is what we expected.
		foreach( $sizes as $width => $source ) {
			$this->assertTrue( $width <= 500 );
		}

		// Remove test filter
		remove_filter( 'tevkori_srcset_array', array( $this, '_test_tevkori_srcset_array' ) );
	}

	/**
	 * A test filter for tevkori_get_srcset_array() that removes any sources
	 * that are larger that 500px wide.
	 */
	function _test_tevkori_srcset_array( $array ) {
		foreach ( $array as $size => $file ) {
			if ( $size > 500 ) {
				unset( $array[$size] );
			}
		}
		return $array;
	}

	function test_tevkori_get_sizes_string() {
		// make an image
		$id = $this->_test_img();

		$sizes = tevkori_get_sizes($id, 'medium');
		$sizes_string = tevkori_get_sizes_string( $id, 'medium' );

		$expected = 'sizes="' . $sizes . '"';

		$this->assertSame( $expected, $sizes_string);
	}

	function test_tevkori_get_srcset_array() {
		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array( $id, 'medium' );

		$year_month = date('Y/m');
		$image = wp_get_attachment_metadata( $id );

		$expected = array(
			$image['sizes']['medium']['width'] => 'http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/'
				. $image['sizes']['medium']['file'] . ' ' . $image['sizes']['medium']['width'] . 'w',
			$image['sizes']['large']['width'] => 'http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/'
				. $image['sizes']['large']['file'] . ' ' . $image['sizes']['large']['width'] . 'w',
			$image['width'] => 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w'
		);

		$this->assertSame( $expected, $sizes );
	}

	function test_tevkori_get_srcset_array_no_date_upoads() {
		// Save the current setting for uploads folders
		$uploads_use_yearmonth_folders = get_option( 'uploads_use_yearmonth_folders' );

		// Disable date organized uploads
		update_option( 'uploads_use_yearmonth_folders', 0 );

		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array( $id, 'medium' );

		$image = wp_get_attachment_metadata( $id );

		$expected = array(
			$image['sizes']['medium']['width'] => 'http://example.org/wp-content/uploads/' . $image['sizes']['medium']['file'] . ' ' . $image['sizes']['medium']['width'] . 'w',
			$image['sizes']['large']['width'] => 'http://example.org/wp-content/uploads/' . $image['sizes']['large']['file'] . ' ' . $image['sizes']['large']['width'] . 'w',
			$image['width'] => 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w'
		);

		$this->assertSame( $expected, $sizes );

		// Leave the uploads option the way you found it.
		update_option( 'uploads_use_yearmonth_folders', $uploads_use_yearmonth_folders );
	}

	function test_tevkori_get_srcset_array_single_srcset() {
		// make an image
		$id = $this->_test_img();
		// In our tests, thumbnails would only return a single srcset candidate,
		// in which case we don't bother returning a srcset array.
		$sizes = tevkori_get_srcset_array( $id, 'thumbnail' );

		$this->assertFalse( $sizes );
	}

	/**
	 * Test for filtering out leftover sizes after an image is edited.
	 * @group 155
	 */
	function test_tevkori_get_srcset_array_with_edits() {
		// Make an image.
		$id = $this->_test_img();

		// For this test we're going to mock metadata changes from an edit.
		// Start by getting the attachment metadata.
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

	function test_tevkori_get_srcset_array_false() {
		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array( 99999, 'foo' );

		// For canola.jpg we should return
		$this->assertFalse( $sizes );
	}

	function test_tevkori_get_srcset_array_no_width() {
		// Filter image_downsize() output.
		add_filter( 'image_downsize', array( $this, '_test_tevkori_get_srcset_array_no_width_filter' ) );

		// Make our attachement.
		$id = $this->_test_img();
		$srcset = tevkori_get_srcset_array( $id, 'medium' );

		// The srcset should be false
		$this->assertFalse( $srcset );

		// Remove filter.
		remove_filter( 'image_downsize', array( $this, '_test_tevkori_get_srcset_array_no_width_filter' ) );
	}

	/**
	 * Helper funtion to filter image_downsize and return zero values for width and height.
	 */
	public function _test_tevkori_get_srcset_array_no_width_filter() {
		return array( 'http://example.org/foo.jpg', 0, 0, false );
	}

	function test_tevkori_get_srcset_string() {
		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_string( $id, 'full-size' );

		$image = wp_get_attachment_metadata( $id );
		$year_month = date('Y/m');

		$expected = 'srcset="';
		$expected .= 'http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/'
			. $image['sizes']['medium']['file'] . ' ' . $image['sizes']['medium']['width'] . 'w, ';
		$expected .='http://example.org/wp-content/uploads/' . $year_month = date('Y/m') . '/'
			. $image['sizes']['large']['file'] . ' ' . $image['sizes']['large']['width'] . 'w, ';
		$expected .= 'http://example.org/wp-content/uploads/' . $image['file'] . ' ' . $image['width'] .'w"';

		$this->assertSame( $expected, $sizes );
	}

	/**
	 * @group 159
	 */
	function test_tevkori_filter_attachment_image_attributes() {
		// Make image.
		$id = $this->_test_img();

		// Get attachment post data.
		$attachment = get_post( $id );
		$image = wp_get_attachment_image_src( $id, 'medium' );
		list($src, $width, $height) = $image;

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
		// Make image.
		$id = $this->_test_img();

		// Get attachment post data.
		$attachment = get_post( $id );
		$image = wp_get_attachment_image_src( $id, 'thumbnail' );
		list($src, $width, $height) = $image;

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
	 */
	function test_tevkori_filter_content_images() {
		// Make image.
		$id = $this->_test_img();

		$srcset = tevkori_get_srcset_string( $id, 'medium' );
		$sizes = tevkori_get_sizes_string( $id, 'medium' );

		// Function used to build HTML for the editor.
		$img = get_image_tag( $id, '', '', '', 'medium' );

		// Manually add srcset and sizes to the markup from get_image_tag();
		$respimg = preg_replace('|<img ([^>]+) />|', '<img $1 ' . $srcset . ' ' . $sizes . ' />', $img);

		$content = '<p>Welcome to WordPress!  This post contains important information.  After you read it, you can make it private to hide it from visitors but still have the information handy for future reference.</p>
			<p>First things first:</p>
			<ul>
			<li><a href="http://wordpress.org" title="Subscribe to the WordPress mailing list for Release Notifications">Subscribe to the WordPress mailing list for release notifications</a></li>
			</ul>

			%1$s

			<p>As a subscriber, you will receive an email every time an update is available (and only then).  This will make it easier to keep your site up to date, and secure from evildoers.<br />
			When a new version is released, <a href="http://wordpress.org" title="If you are already logged in, this will take you directly to the Dashboard">log in to the Dashboard</a> and follow the instructions.<br />
			Upgrading is a couple of clicks!</p>
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

		$content_unfiltered = sprintf( $content, $img );
		$content_filtered = sprintf( $content, $respimg );

		$this->assertSame( $content_filtered, tevkori_filter_content_images( $content_unfiltered ) );
	}
}
