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

	function test_tevkori_get_srcset_array() {
		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array( $id, 'medium' );

		$year_month = date('Y/m');
		$expected = array(
			'http://example.org/wp-content/uploads/' . $year_month . '/test-large-300x225.png 300w',
			'http://example.org/wp-content/uploads/' . $year_month . '/test-large-1024x768.png 1024w',
			'http://example.org/wp-content/uploads/' . $year_month . '/test-large.png 1600w'
		);

		$this->assertSame( $expected, $sizes );
	}


	function test_tevkori_get_srcset_array_thumb() {
		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array( $id, 'thumbnail' );

		$year_month = date('Y/m');
		$expected = array(
			'http://example.org/wp-content/uploads/' . $year_month . '/test-large-150x150.png 150w'
		);

		$this->assertSame( $expected, $sizes );
	}

	function test_tevkori_get_srcset_array_false() {		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_array( 99999, 'foo' );

		// For canola.jpg we should return
		$this->assertFalse( $sizes );
	}

	function test_tevkori_get_srcset_string() {
		// make an image
		$id = $this->_test_img();
		$sizes = tevkori_get_srcset_string( $id, 'full-size' );

		$year_month = date('Y/m');
		$expected = 'srcset="' . 'http://example.org/wp-content/uploads/' . $year_month . '/test-large-300x225.png 300w, ' .
		'http://example.org/wp-content/uploads/' . $year_month . '/test-large-1024x768.png 1024w, ' .
		'http://example.org/wp-content/uploads/' . $year_month . '/test-large.png 1600w"';

		$this->assertSame( $expected, $sizes );
	}

}
