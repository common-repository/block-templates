<?php
/**
 * Plugin Name: Gutenberg Templates
 * Plugin URI: https://wordpress.org/plugins/block-templates/
 * Description: Design templates using Gutenberg for pages, posts and custom types. Templates will ultimately be able to use custom fields for dynamic content.
 * Author: Konstantinos Galanakis
 * Author URI: https://github.com/kmgalanakis
 * Version: 1.0.1
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Gutenberg_Templates
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GBT_VERSION', '1.0.1' );

define( 'GBT_PATH', dirname( __FILE__ ) );

define( 'GBT_URL', plugin_dir_url( __FILE__ ) );

require_once GBT_PATH . '/application/bootstrap.php';
