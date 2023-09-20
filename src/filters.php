<?php
/**
 * Adds filters to prevent WordPress from generating image sizes during the upload process.
 *
 * @package BU MediaS3
 */

namespace BU\Plugins\MediaS3;

// Conditionally adds a filter only during the upload process, this filter adds a second filter that removes all the image sizes.
add_filter(
	'wp_handle_upload_prefilter',
	function( $file ) {
		// This filters the image sizes that are generated during the upload process, removing all of them by returning an empty array.
		add_filter(
			'image_resize_dimensions',
			function( $orig_w, $orig_h ) {
				return array();
			},
			10,
			6
		);

		// We need to pass along the original prefilter value unaltered; we're not actually changing it, just using it as a hook for the resize filter.
		return $file;
	}
);

// Add a hook to delete the custom crop factors from the DynamoDB table when a site is deleted.
add_action(
	'wp_delete_site',
	function( $old_site ) {
		delete_dynamodb_sizes( $old_site->siteurl );
	},
	10,
	2
);