<?php
/**
 * Image Editor subclass of the S3_Uploads plugin Image_Editor_Imagick class.
 *
 * This custom image editor is used to skip saving the image to S3.
 *
 * @package BU MediaS3
 */

namespace BU\Plugins\MediaS3;

use S3_Uploads\Image_Editor_Imagick;
use WP_Error;

class Skip_Save_Image_Editor extends Image_Editor_Imagick {

	/**
	 * Override the _save method to skip saving the image to S3.
	 *
	 * @param Imagick $image
	 * @param ?string $filename
	 * @param ?string $mime_type
	 * @return WP_Error|array{path: string, file: string, width: int, height: int, mime-type: string}
	 */
	protected function _save( $image, $filename = null, $mime_type = null ) {
		/**
		 * @var ?string $filename
		 * @var string $extension
		 * @var string $mime_type
		 */
		list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

		// What we really need is to get the filename here, then return it without saving the image.
		if ( ! $filename ) {
			$filename = parent::generate_filename( null, null, $extension );
		}

		$response = [
			'path'      => $filename,
			'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
			'width'     => $this->size['width'] ?? 0,
			'height'    => $this->size['height'] ?? 0,
			'mime-type' => $mime_type,
		];

		return $response;
	}
}
