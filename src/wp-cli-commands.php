<?php

namespace BU\Plugins\MediaS3;

function get_custom_image_sizes() {
	$image_sizes = wp_get_registered_image_subsizes();
	echo json_encode( $image_sizes );
}
\WP_CLI::add_command( 's3media get-custom-image-sizes', __NAMESPACE__ . '\\get_custom_image_sizes' );
