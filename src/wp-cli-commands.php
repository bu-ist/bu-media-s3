<?php
/**
 * WP-CLI commands for working with protected S3 media.
 *
 * The command is called 's3media' instead of 'media-s3' just because I don't want to have to type an extra hyphen right now.
 *
 * @package BU MediaS3
 */

namespace BU\Plugins\MediaS3;

/**
 * Takes the output of get_custom_image_sizes() and updates the DynamoDB table.
 *
 * Custom crop gravities are stored in the same DynamoDB table as the BU Access Control rules.
 * This function takes the output of get_custom_image_sizes(), writes it to a JSON string
 * and updates the DynamoDB table entry for this site.
 *
 * @return void
 */
function update_dynamodb_sizes_cmd() {
	$result = update_dynamodb_sizes();

	\WP_CLI::success( 'Updated DynamoDB table with custom crop factors.' );
}
\WP_CLI::add_command( 's3media update-dynamodb-sizes', __NAMESPACE__ . '\\update_dynamodb_sizes_cmd' );

/**
 * Report on the custom crop factors for a site.
 *
 * @since 0.0.1
 *
 * @return void
 */
function get_custom_image_sizes() {
	$image_sizes = wp_get_registered_image_subsizes();

	$image_size_table = array();

	foreach ( $image_sizes as $name => $size ) {
		$size_key    = $size['width'] . 'x' . $size['height'];
		$crop_type   = gettype( $size['crop'] );
		$crop_string = 'array' === $crop_type ? implode( ' ', $size['crop'] ) : wp_json_encode( $size['crop'], true );

		$image_size_table[] = array(
			'name'     => $name,
			'width'    => $size['width'],
			'height'   => $size['height'],
			'crop'     => $crop_string,
			'size-key' => $size_key,
		);
	}

	\WP_CLI\Utils\format_items( 'table', $image_size_table, array( 'name', 'width', 'height', 'crop', 'size-key' ) );
}
\WP_CLI::add_command( 's3media get-custom-image-sizes', __NAMESPACE__ . '\\get_custom_image_sizes' );
