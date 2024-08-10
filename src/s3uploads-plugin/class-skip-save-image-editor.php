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
		// Skip saving the image
		return [
			'path'      => $filename ?: '',
			'file'      => wp_basename( $filename ?: '' ),
			'width'     => $this->size['width'] ?? 0,
			'height'    => $this->size['height'] ?? 0,
			'mime-type' => $mime_type,
		];
	}
}
