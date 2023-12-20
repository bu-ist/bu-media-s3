<?php
/**
 * Functions associated with managing assets in S3.
 *
 * @package BU MediaS3
 */

namespace BU\Plugins\MediaS3;

use Aws\S3\S3Client;

/**
 * Get the S3 client.
 *
 * Relies on credentials being defined elsewhere.
 *
 * @since 0.0.1
 *
 * @return S3Client The S3 client.
 */
function new_s3_client() {
	// Create the S3 client.
	$client = new S3Client(
		array(
			'version'     => 'latest',
			'region'      => S3_UPLOADS_REGION,
			'credentials' => array(
				'key'    => S3_UPLOADS_KEY, // Defined outside of the repo.
				'secret' => S3_UPLOADS_SECRET,
			),
		)
	);
	return $client;
}

/**
 * Delete the entire media library from S3.
 *
 * Deletes all of the original and rendered media library files from S3 for a given site url.
 *
 * @since 0.0.1
 *
 * @param string $siteurl The siteurl as reported by get_blog_details().
 *
 * @return bool True if successful, false if not.
 */
function delete_full_media_library( $siteurl ) {
	// Create the S3 client, and get the bucket name and site key.
	$s3_client = new_s3_client();
	$bucket    = str_replace( '/original_media', '', S3_UPLOADS_BUCKET );
	$site_key  = str_replace( array( 'http://', 'https://' ), '', $siteurl );

	// Delete the media library originals. This may need to be wrapped in a queued job,
	// because we can't necessarily predict how long it takes to delete the files.
	try {
		// Delete all of the original media library files.
		$s3_client->deleteMatchingObjects( $bucket, "original_media/{$site_key}" );

		// Delete all of the rendered media library files.
		$s3_client->deleteMatchingObjects( $bucket, "rendered_media/{$site_key}" );

	} catch ( AwsException $e ) {
		// Handle the exception.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $e->getMessage() );
		// Return false if unsuccessful.
		return false;
	}

	// Return true if successful.
	return true;

}

/**
 * Delete the rendered media library files from S3.
 *
 * Deletes all of the rendered media library files from S3 for a given site url.
 *
 * @since 0.0.1
 *
 * @param string $siteurl The siteurl as reported by get_blog_details().
 *
 * @return bool True if successful, false if not.
 */
function delete_rendered_files( $siteurl ) {
	// Create the S3 client, and get the bucket name and site key.
	$s3_client = new_s3_client();
	$bucket    = str_replace( '/original_media', '', S3_UPLOADS_BUCKET );
	$site_key  = str_replace( array( 'http://', 'https://' ), '', $siteurl );

	// Delete all of the rendered media library files.
	try {
		// Delete all of the rendered media library files.
		$s3_client->deleteMatchingObjects( $bucket, "rendered_media/{$site_key}" );

	} catch ( AwsException $e ) {
		// Handle the exception.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $e->getMessage() );
		// Return false if unsuccessful.
		return false;
	}

	// Return true if successful.
	return true;
}

/**
 * Check if an object exists in the S3 bucket.
 *
 * @param string $key The key of the object to check.
 * @return bool True if the object exists, false if not.
 */
function s3_object_exists( $key ) {
	$s3_client = new_s3_client();

	$bucket = str_replace( '/original_media', '', S3_UPLOADS_BUCKET );

	$result = $s3_client->doesObjectExist( $bucket, "original_media/{$key}" );

	return $result;
}
