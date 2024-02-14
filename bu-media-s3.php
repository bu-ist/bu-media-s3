<?php
/**
 * Plugin Name: bu-media-s3
 * Plugin URI: https://github.com/bu-ist/bu-media-s3
 * Description: A plugin for integrating S3 with WordPress
 * Version: 0.1
 * Author: Boston University
 * Author URI: https://developer.bu.edu/
 *
 * @package BU MediaS3
 **/


require_once dirname( __FILE__ ) . '/src/dynamodb-crop.php';
require_once dirname( __FILE__ ) . '/src/s3-assets.php';
require_once dirname( __FILE__ ) . '/src/filters.php';

// Load the WP-CLI commands if we're running in a CLI environment.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/src/wp-cli-commands.php';
}
