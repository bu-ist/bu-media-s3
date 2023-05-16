<?php
/**
 * Functions associated with S3 assets.
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
 * Note: this is using get_custom_image_sizes(), but there is also a function
 * called wp_get_registered_image_subsizes(). and I don't super understand the differences.
 *
 * @return void
 */
function update_dynamodb_sizes_cmd() {
	$client = new_dynamodb_client();

	$result = update_dynamodb_sizes( $client, get_custom_image_sizes() );

	\WP_CLI::success( 'Updated DynamoDB table with custom crop factors.' );
}
\WP_CLI::add_command( 's3media update-dynamodb-sizes', __NAMESPACE__ . '\\update_dynamodb_sizes_cmd' );

/**
 * Report on the custom crop factors for a site.
 *
 * Note: this uses wp_get_registered_image_subsizes() just because that what I was looking at when I wrote this.
 * I think wp_get_registered_image_subsizes() may be a bit more comprehensive in that it includes the default
 * crop factors from WordPress itself, but I haven't yet tracked down that detail.
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
