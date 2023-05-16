<?php

namespace BU\Plugins\MediaS3;

/**
 * Takes the output of get_custom_image_sizes() and updates the DynamoDB table.
 *
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
