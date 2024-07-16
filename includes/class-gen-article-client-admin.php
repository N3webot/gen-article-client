<?php

class Gen_Article_Client_Admin {
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Générateur d\'article',
            'Générateur d\'article',
            'manage_options',
            'gen-article-client',
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-admin-generic',
            80
        );
    }

    public function display_plugin_admin_page() {
        Gen_Article_Client_Render::render_template('admin-page');
    }

    public function save_api_key_and_version() {
        check_admin_referer('save_api_key_and_version_nonce', 'save_api_key_and_version_nonce_field');
        if (isset($_POST['api_key'])) {
            update_option('option_api_key', sanitize_text_field($_POST['api_key']));
        }
        if (isset($_POST['version'])) {
            update_option('option_version', sanitize_text_field($_POST['version']));
        }
        if (isset($_POST['summary'])) {
            update_option('option_summary', sanitize_textarea_field($_POST['summary']));
        }
        wp_redirect(admin_url('admin.php?page=gen-article-client'));
        exit;
    }

    public function generate_posts() {
        check_admin_referer('blog_architect_generate_posts_action', 'blog_architect_generate_posts_nonce');
        if (isset($_POST['post_titles'])) {
            $post_titles = explode("\n", sanitize_textarea_field($_POST['post_titles']));
            $post_handler = new Gen_Article_Client_Post_Handler();
            foreach ($post_titles as $title) {
                $post_handler->create_draft_post($title);
            }
        }
        wp_redirect(admin_url('admin.php?page=gen-article-client'));
        exit;
    }

    /**
     * Gère les différentes fonctionnalités disponibles dans les selects
     * Notamment la gestion des catégories
     *
     * @return void
     */
    public function handle_bulk_actions() {
        check_admin_referer('blog_architect_bulk_actions_nonce', 'blog_architect_bulk_actions_nonce_field');
    
        try {
            if (isset($_POST['bulk_action'])) {
                $action = sanitize_text_field($_POST['bulk_action']);
                $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
    
                if (!empty($post_ids)) {
                    switch ($action) {
                        case 'add_category':
                            if (isset($_POST['category'])) {
                                $category_id = intval($_POST['category']);
                                foreach ($post_ids as $post_id) {
                                    wp_set_post_categories($post_id, array($category_id), true);
                                }
                            }
                            break;
    
                        case 'remove_category':
                            if (isset($_POST['category'])) {
                                $category_id = intval($_POST['category']);
                                foreach ($post_ids as $post_id) {
                                    $categories = wp_get_post_categories($post_id);
                                    if (($key = array_search($category_id, $categories)) !== false) {
                                        unset($categories[$key]);
                                        wp_set_post_categories($post_id, $categories);
                                    }
                                }
                            }
                            break;
    
                        case 'delete_posts':
                            foreach ($post_ids as $post_id) {
                                wp_delete_post($post_id, true);
                            }
                            break;
                    }
                }
            }
    
            if (isset($_POST['publish_action'])) {
                $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
                if (!empty($post_ids)) {
                    $api_key = get_option('option_api_key');
                    $version = get_option('option_version');
                    $summary = get_option('option_summary');
    
                    $api_handler = new Gen_Article_Client_API();
    
                    foreach ($post_ids as $post_id) {
                        $api_handler->handle_post($post_id, $api_key, $version, $summary);
                    }
                }
            }
    
            wp_redirect(admin_url('admin.php?page=gen-article-client'));
            exit;
        } catch (Exception $e) {
            error_log('Exception dans handle_bulk_actions : ' . $e->getMessage());
            wp_die('Une erreur est survenue. Veuillez vérifier les journaux pour plus de détails.');
        }
    }
    
    

    /**
     * Enregistre le contexte pour l'article
     *
     * @return void
     */
    public function save_post_context() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'post_context_') === 0) {
                    $post_id = str_replace('post_context_', '', $key);
                    $context = sanitize_text_field($value);
                    update_post_meta($post_id, '_post_context', $context);
                    error_log("Saved context for post " . $post_id . ": " . $context);
                }
            }
        }
    }
    
    
}
