<?php
/**
 * Image Editor subclass of the S3_Uploads plugin Image_Editor_Imagick class.
 *
 * This custom image editor is used to skip saving the image to S3. It works by subclassing the
 * S3_Uploads plugin's Image_Editor_Imagick class and overriding the _save method to skip saving the image to S3.
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
		// Detects real content vs. auto-named sub-size; the make_subsize() core function
		// is the only caller we've identified that passes a null filename.
		// Image edits always pass an explicit filename, and so do an upload's conditional
		// conversions like rotation, when one of those is active.

		$explicit_filename = ! empty( $filename );

		/**
		 * @var ?string $filename
		 * @var string $extension
		 * @var string $mime_type
		 */
		list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

		// Crucially, we need to determine the filename so we can return it correctly in the response.
		if ( ! $filename ) {
			$filename = parent::generate_filename( null, null, $extension );
		}

		// If we have an explicit filename, we infer this is real content and should be saved.
		if ( $explicit_filename ) {
			// Return the original value to indicate that the image was saved.
			return parent::_save( $image, $filename, $mime_type );
		}

		// We don't want to save the image to S3, so we don't call the parent::_save method.

		// Return the metadata for the image.
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
