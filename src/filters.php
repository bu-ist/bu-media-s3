<?php
/**
 * Adds filters to prevent WordPress from generating image sizes during the upload process.
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

		// Preemptively add the sizes to the attachment metadata.
		add_filter(
			'wp_generate_attachment_metadata',
			function( $metadata, $attachment_id ) {
				// Get the registered image sizes.
				$sizes = wp_get_registered_image_subsizes();

				// Get the pathinfo for the original file.
				$pathinfo = pathinfo( $metadata['file'] );

				// Get the mime type for the original file.
				$mime_type = get_post_mime_type( $attachment_id );

				// Recalculate the sizes that would have been generated and add them to the metadata.
				foreach ( $sizes as $size => $size_data ) {
					// Calcualte the new filename by adding the size to the original filename using the WordPress convention.
					$new_filename = $pathinfo['filename'] . '-' . $size_data['width'] . 'x' . $size_data['height'] . '.' . $pathinfo['extension'];

					// Add the new size to the metadata.
					$metadata['sizes'][ $size ] = array(
						'file'      => $new_filename,
						'width'     => $size_data['width'],
						'height'    => $size_data['height'],
						'mime-type' => $mime_type,
					);
				}
				return $metadata;
			},
			10,
			2
		);

		// We need to pass along the original prefilter value unaltered; we're not actually changing it, just using it as a hook for the resize filter.
		return $file;
	}
);

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
