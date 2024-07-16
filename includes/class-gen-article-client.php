<?php

class Gen_Article_Client {
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'class-gen-article-client-admin.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-gen-article-client-post-handler.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-gen-article-client-api.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-gen-article-client-render.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-gen-article-client-scripts.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new Gen_Article_Client_Admin();
        add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( 'Gen_Article_Client_Scripts', 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( 'Gen_Article_Client_Scripts', 'enqueue_scripts' ) );
        add_action( 'admin_post_save_api_key_and_version', array( $plugin_admin, 'save_api_key_and_version' ) );
        add_action( 'admin_post_blog_architect_generate_posts', array( $plugin_admin, 'generate_posts' ) );
        add_action( 'admin_post_blog_architect_handle_bulk_actions', array( $plugin_admin, 'handle_bulk_actions' ) );
    }
}
