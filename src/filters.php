<?php
/**
 * Media handling for S3 storage and multisite installations.
 *
 * This file contains functions to modify WordPress's default media handling behavior for multisite installations.
 * It changes the upload directory paths to use Amazon S3 instead of the local filesystem, and suppresses the generation
 * of additional image sizes during the upload process. Instead, it pre-generates metadata for these sizes without
 * creating the actual resized images. It also disables the big image size threshold to prevent WordPress from doing any resizing
 * on original media. When a site is deleted, it hooks into the 'wp_delete_site' action to delete the corresponding
 * media library originals and custom crop factors from AWS resources.
 *
 * @link https://developer.wordpress.org/reference/hooks/upload_dir/
 * @link https://developer.wordpress.org/reference/hooks/wp_handle_upload_prefilter/
 *
 * @package BU MediaS3
 */

namespace BU\Plugins\MediaS3;

/**
 * Modifies the upload directory paths for multisite installations to use Amazon S3.
 *
 * This function changes the base URL and directory paths of the upload directory
 * to point to an Amazon S3 bucket instead of the local filesystem. It changes the
 * the default upload directory to 'files' for all sites, accounting for both root network
 * sites and subsites.
 *
 * This function is hooked to the 'upload_dir' filter.
 *
 * @global S3_UPLOADS_BUCKET The name of the S3 bucket to use for uploads; defined in wp-config.php.
 * @param array $upload An array containing the paths and URLs for the upload directory.
 * @return array The modified array with the new paths and URLs.
 */
function s3_multisite_upload_dir( $upload ) {
	$pattern     = '#wp-content/uploads/sites/\d+#';
	$replacement = 'files';

	// If this is a subsite, replace the site-specific subdirectory with 'files'.
	if ( preg_match( $pattern, $upload['baseurl'] ) ) {
		$upload['baseurl'] = preg_replace( $pattern, $replacement, $upload['baseurl'] );
	} else {
		// If this is the main site, replace the root specific uploads directory with 'files'.
		$upload['baseurl'] = str_replace( 'wp-content/uploads', $replacement, $upload['baseurl'] );
	}

	// Change the base URL and directory paths to point to the S3 bucket.
	$upload['basedir'] = str_replace( array( 'http://', 'https://' ), 's3://' . S3_UPLOADS_BUCKET . '/', $upload['baseurl'] );
	$upload['path']    = $upload['basedir'] . $upload['subdir'];
	$upload['url']     = $upload['baseurl'] . $upload['subdir'];

	return $upload;
}
add_filter( 'upload_dir', __NAMESPACE__ . '\s3_multisite_upload_dir' );

// Add a custom filter to indicate that this is an upload request.
add_filter(
	'wp_handle_upload',
	function( $file ) {
		// Set a custom flag to indicate that this is an upload request.
		add_filter('is_upload_request', '__return_true');

		// May still want to set a generate_metadata filter here to add the file size, which seems to be getting dropped.

		// We need to pass along the original prefilter value unaltered; we're not actually changing it, just using it as a hook for the resize filter.
		return $file;
	}
);

// Add a custom filter to suppress resized image generation during the upload process.
add_filter( 'wp_image_editors', function( $editors ) {
	// Check if the custom flag indicating an upload request is set.
	if ( apply_filters('is_upload_request', false) ) {
		// This is an upload request, so we should use the custom image editor that skips saving the image to S3.
		// Include the custom image editor that skips saving the image to S3.
		require_once dirname( __FILE__ ) . '/s3uploads-plugin/class-skip-save-image-editor.php';

		// Add the custom image editor to the list of available editors as the first editor, so that's what WordPress uses.
		array_unshift( $editors, 'BU\Plugins\MediaS3\Skip_Save_Image_Editor' );
	}

	// Return the list of editors.
	return $editors;
} );

// Disable the big image threshold, we don't want WordPress to do any resizing at all.
add_filter( 'big_image_size_threshold', '__return_false' );

// Add a hook to delete AWS resources when a site is deleted.
add_action(
	'wp_delete_site',
	function( $old_site ) {
		// Delete the media library originals.
		// This may need to be wrapped in a queued job, because we can't necessarily
		// predict how long it takes to delete the files.
		delete_full_media_library( $old_site->siteurl );

		// Delete the custom crop factors from DynamoDB.
		delete_dynamodb_sizes( $old_site->siteurl );
	},
	10,
	2
);

/**
 * Add a filter to the wp_handle_replace event, declared by the enable-media-replace plugin.
 * The filter passes the post ID of the file being replaced, and we use this to delete the old rendered media library files.
 * Otherwise, the old scaled versions of the file won't be replaced with updated versions.
 */
add_filter(
	'wp_handle_replace',
	function( $post_details ) {
		// Get the file path from the attachment ID.
		$file = get_attached_file( $post_details['post_id'] );

		// Delete the old rendered media library files for the file being replaced.
		delete_scaled_for_s3_key( $file );
	}
);
