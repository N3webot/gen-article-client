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


// Enregistrement des scripts et styles
function enqueue_public_scripts() {
    wp_enqueue_script('generateur-articles-public-script', plugin_dir_url(__FILE__) . 'Jscript.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'enqueue_public_scripts');

function enqueue_public_styles() {
    wp_enqueue_style('generateur-articles-public-style', plugin_dir_url(__FILE__) . 'public-style.css');
}
add_action('wp_enqueue_scripts', 'enqueue_public_styles');



// Création de la page de réglages
add_action('admin_menu', 'create_plugin_settings_page');
function create_plugin_settings_page() {
    add_menu_page(
        'Client Plugin Settings',
        'Client Plugin',
        'manage_options',
        'client-plugin',
        'client_plugin_settings'
    );
}

function client_plugin_settings() {
    require_once plugin_dir_path(__FILE__) . 'interface.php';
    require_once plugin_dir_path(__FILE__) . 'form_request.php';

}



// Gestion de la soumission de formulaire
function handle_settings_form() {

    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }

    check_admin_referer('save_api_key_and_version_nonce', 'save_api_key_and_version_nonce_field');
    update_option('option_api_key', sanitize_text_field($_POST['api_key']));
    update_option('option_version', sanitize_text_field($_POST['version']));
    update_option('option_summary', sanitize_textarea_field($_POST['summary']));
    wp_redirect(admin_url('admin.php?page=client-plugin'));
    exit;
}
add_action('admin_post_save_api_key_and_version', 'handle_settings_form');



// Sauvegarde des articles en brouillon
function create_post_drafts() {

    if (!current_user_can('edit_posts')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }

    $h1_list = get_post_titles_from_request();
    foreach($h1_list as $h1) {
        save_post_as_draft($h1);
    }

    wp_redirect(admin_url('admin.php?page=client-plugin'));
}
add_action('admin_post_blog_architect_generate_posts', 'create_post_drafts');

function get_post_titles_from_request() {
    $post_titles = sanitize_textarea_field($_POST['post_titles']);
    return array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", $post_titles))));
}

function save_post_as_draft($h1) {

    $slug = sanitize_title($h1);

    $post = [
        'post_title' => $h1,
        'post_status' => 'draft',
        'post_type' => 'post',
    ];

    $id = wp_insert_post($post);

    if (is_wp_error($id)) {
        error_log('Erreur lors de l\'insertion du post : ' . $id->get_error_message());
        return false;
    }

    // Créer une méta pour identifier les articles créés par le plugin
    update_post_meta($id, '_is_ba_post', 1);

    return true;
}
