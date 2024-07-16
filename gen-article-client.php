<?php
/*
Plugin Name: Générateur d'article - Plugin client
Plugin URI: https://www.n3web.fr
Description: Appel generateur article client
Version: 1.0.0
Author: N3web
Author URI: https://www.n3web.fr
License: GPLv2 or later
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Autoload classes.
spl_autoload_register( function ( $class ) {
    if ( 0 === strpos( $class, 'Gen_Article_Client_' ) ) {
        $class_name = strtolower( str_replace( '_', '-', $class ) );
        $file = plugin_dir_path( __FILE__ ) . 'includes/class-' . $class_name . '.php';
        if ( file_exists( $file ) ) {
            require $file;
        }
    }
} );

// Initialize the plugin.
function gen_article_client_init() {
    $plugin = new Gen_Article_Client();
}
add_action( 'plugins_loaded', 'gen_article_client_init' );