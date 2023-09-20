<?php
/**
 * Functions associated with managing custom size records in dynamodb.
 *
 * @package BU MediaS3
 */

namespace BU\Plugins\MediaS3;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Exception\AwsException;

/**
 * Get the DynamoDB client.
 *
 * Relies on credentials being defined elsewhere.
 *
 * @since 0.0.1
 *
 * @return DynamoDbClient The DynamoDB client.
 */
function new_dynamodb_client() {
	$client = new DynamoDbClient(
		array(
			'region'      => 'us-east-1',
			'version'     => 'latest',
			'credentials' => array(
				'key'    => S3_UPLOADS_KEY, // Defined outside of the repo.
				'secret' => S3_UPLOADS_SECRET,
			),
		)
	);
	return $client;
}

/**
 * Update the DynamoDB table with the custom crop factors for a site.
 *
 * Takes the output of wp_get_registered_image_subsizes(), encodes it to JSON, and writes it to the DynamoDB table.
 * The DynamoDB table name is set as a global and is also used by bu-access-control.
 *
 * @since 0.0.1
 *
 * @return array The result of the DynamoDB putItem operation.
 */
function update_dynamodb_sizes() {

	$client = new_dynamodb_client();
	// Get the site and the group name slug for the primary key.
	// First get the siteurl without the protocol.
	$site_key = str_replace( array( 'http://', 'https://' ), '', get_site_url() );

	// Write the group to the DynamoDB table.
	try {
		$result = $client->putItem(
			array(
				'TableName' => ACCESS_RULES_TABLE, // Defined in the config file.
				'Item'      => array(
					'SiteAndGroupKey' => array( 'S' => "SIZES#{$site_key}" ),
					'sizes'           => array( 'S' => wp_json_encode( wp_get_registered_image_subsizes() ) ),
				),
			)
		);
		return $result;
	} catch ( DynamoDbException $e ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $e->getMessage() );
		return $e;
	} catch ( AwsException $e ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $e->getMessage() );
		return $e;
	}

	// Final return in case of error.
	return false;

}

/**
 * Delete the custom crop factors for a site from the DynamoDB table.
 *
 * @param string $siteurl The siteurl as reported by get_site_url().
 * @return array|bool The result of the DynamoDB deleteItem operation.
 */
function delete_dynamodb_sizes( $siteurl ) {
	$client = new_dynamodb_client();

	// Calculate the site slug for the primary key.
	$site_key = str_replace( array( 'http://', 'https://' ), '', $siteurl );

	// Delete the sizes record from the DynamoDB table.
	try {
		$result = $client->deleteItem(
			array(
				'TableName' => ACCESS_RULES_TABLE, // Defined in the config file.
				'Key'       => array(
					'SiteAndGroupKey' => array( 'S' => "SIZES#{$site_key}" ),
				),
			)
		);
		return $result;
	} catch ( DynamoDbException $e ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $e->getMessage() );
		return $e;
	} catch ( AwsException $e ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $e->getMessage() );
		return $e;
	}

	// Final return in case of error.
	return false;
}
