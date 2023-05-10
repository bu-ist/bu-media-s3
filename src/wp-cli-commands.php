<?php

namespace BU\Plugins\MediaS3;

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
