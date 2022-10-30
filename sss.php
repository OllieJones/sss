<?php
/**
 * Plugin Name: Super Sonic Search
 * Version: 0.1.0
 * Plugin URI: https://github.com/OllieJones/sss
 * Description: Delight your audience! Use Sonic Search in your WordPress website.
 * Author: Oliver Jones
 * Author URI: https://github.com/OllieJones/sss
 * Requires at least: 5.9
 * Requires PHP: 5.6
 * Network: true

 * Tested up to: 6.1
 *
 * Text Domain: sss
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Oliver Jones
 * @since 1.0.0
 */

namespace OllieJones;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '/lib/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-super-sonic-search.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-super-sonic-search-settings.php';

/**
 * Returns the main instance of Super_Sonic_Search to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Super_Sonic_Search
 */
function super_sonic_search() {
	$instance = Super_Sonic_Search::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Super_Sonic_Search_Settings::instance( $instance );
	}

	return $instance;
}

super_sonic_search();
