# BU Media S3

BU Media S3 is a WordPress plugin designed to work with the [Human Made S3 Uploads plugin](https://github.com/humanmade/S3-Uploads) and the BU Protected S3 Object Lambda stack. It provides additional handling for media coming from S3.

## Features

- **S3 Upload Directory**: The plugin filters the `upload_dir` hook and rewrites the values in a way that is compatible with the S3 Uploads plugin and the BU Protected S3 Object Lambda stack, redirecting all media uploads to the S3 bucket. It also changes the default upload location from `wp-content/uploads` to `files`, which is the convention for BU WordPress sites.

- **Prevent Image Scaling**: By default, WordPress generates scaled derivatives for every image size defined for the current site. This plugin sets a custom filter flag during the upload process and uses it to interpose a custom Image Editor object. This approach leverages the technique used by the S3 Uploads plugin, ensuring that the core WordPress image size metadata generation remains intact while skipping the actual resizing. This allows the BU Protected S3 Object Lambda stack to handle image resizing automatically, and preserves the attachment metadata for all defined sizes.

- **Suppress Big Image Threshold Resizing**: WordPress 5.3 introduced a feature that automatically resizes images to "web-ready" dimensions. This plugin suppresses this feature, allowing you to upload images at their full size.

- **DynamoDB Crop Data**: This plugin provides a way to write crop data to DynamoDB. The BU Protected S3 Object Lambda stack can read this crop data and apply it when the image is requested. The site-manager plugin automatically writes crop data to DynamoDB when sites are moved or cloned. Site creation might be a todo.

- **Handle Site File Deletion**: When a site is deleted, this plugin handles the deletion of files from S3 and the corresponding crop data in DynamoDB. It deletes both the original and derivative images from S3.

- **WP-CLI Commands**: DynamoDB crop data can be updated via WP-CLI commands.

  - `wp s3media update-dynamodb-sizes`: This command updates the DynamoDB table with the custom crop factors for your site's images. It takes the output of the `get_custom_image_sizes` function, writes it to a JSON string, and updates the DynamoDB table entry for your site.

  - `wp s3media get-custom-image-sizes`: Reports the custom image sizes for your site.
