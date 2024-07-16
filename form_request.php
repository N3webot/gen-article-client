<?php
error_log('Form_request chargé');

if (!function_exists('handle_post')) {
    /**
     * Gère les opérations pour un post donné.
     *
     * @param int $post_id ID du post.
     * @param string $api_key Clé API.
     * @param string $version Version.
     * @param string $summary Résumé.
     */
    function handle_post($post_id, $api_key, $version, $summary) {
        error_log('Début de handle_post pour le post_id: ' . $post_id);
        $subject = get_the_title($post_id);
        $context = get_post_meta($post_id, '_post_context', true) ?: '';
        $data = array(
            'api_key' => $api_key,
            'ndd' => parse_url(get_site_url(), PHP_URL_HOST),
            'subject' => $subject,
            'summary' => $summary,
            'context' => $context,
            'version' => $version
        );
        $response = wp_remote_post('https://dev-blog-gen.n3web-dev.fr/wp-json/blog-architect/v1/endpoint', array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($data),
            'timeout' => 500,
        ));
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            error_log('Requête API réussie pour le post_id: ' . $post_id);
            process_api_response($response, $post_id);
        } else {
            error_log('Erreur API : ' . wp_remote_retrieve_response_message($response));
        }
    }
}

if (!function_exists('process_api_response')) {
    /**
     * Traite la réponse de l'API et gère la création du nouveau post.
     *
     * @param array $response Réponse de l'API.
     * @param int $post_id ID du post d'origine.
     */
    function process_api_response($response, $post_id) {
        error_log('Début de process_api_response pour le post_id: ' . $post_id);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['id'])) {
            $article_title = $data['id']['post_result']['metas']['title'];
            $content = $data['id']['post_result']['content'];
            $thumbnail = $data['id']['post_result']['thumbnail'];
            $post_article = wp_insert_post(array(
                'post_title' => $article_title,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_type' => 'post',
            ));
            $category_ids = array_map(function($category) {
                return $category->term_id;
            }, wp_get_post_categories($post_id, array('fields' => 'all')));
            wp_set_post_categories($post_article, $category_ids);
            media_sideload_image($thumbnail, $post_article, $article_title);
            $attachments = get_posts(array(
                'post_type' => 'attachment',
                'numberposts' => 1,
                'order' => 'DESC',
            ));
            set_post_thumbnail($post_article, $attachments[0]->ID);
            wp_delete_post($post_id, true);
            error_log('Nouveau post publié avec succès pour le post_id: ' . $post_article);
        } else {
            error_log('Erreur de réponse : ' . print_r($data, true));
        }
    }
}

if (!function_exists('update_categories_for_selected_posts')) {
    /**
     * Met à jour les catégories pour les posts sélectionnés.
     *
     * @param array $selected_posts IDs des posts sélectionnés.
     */
    function update_categories_for_selected_posts($selected_posts) {
        $action = sanitize_text_field($_POST['bulk_action']);
        if ($action === 'remove_category') {
            $default_category_id = get_option('default_category');
            foreach ($selected_posts as $post_id) {
                wp_set_post_categories($post_id, array($default_category_id));
            }
        } elseif ($action === 'add_category') {
            $category_id = intval($_POST['category']);
            if ($category_id !== 0) {
                $default_category_id = get_option('default_category');
                foreach ($selected_posts as $post_id) {
                    $current_categories = wp_get_post_categories($post_id, array('fields' => 'ids'));
                    $new_categories = array_diff($current_categories, array($default_category_id));
                    $new_categories[] = $category_id;
                    wp_set_post_categories($post_id, $new_categories);
                }
            }
        } elseif ($action === 'delete_posts') {
            foreach ($selected_posts as $post_id) {
                wp_delete_post($post_id, true);
            }
        }
    }
}

// Gestion de l'action de publication
add_action('admin_post_blog_architect_handle_bulk_actions', 'handle_bulk_actions');

if (!function_exists('handle_bulk_actions')) {
    function handle_bulk_actions() {
        if (isset($_POST['publish_action'])) {
            publish_action_handler();
        } elseif (isset($_POST['save_button'])) {
            update_categories_for_selected_posts($_POST['post_ids']);
        } elseif (isset($_POST['posts_ready_button'])) {
            mark_posts_as_ready($_POST['post_ids']);
        }
    }
}

if (!function_exists('publish_action_handler')) {
    function publish_action_handler() {
        error_log("Bouton cliqué");
        if (!current_user_can('edit_posts')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
        }

        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'meta_query' => array(
                array(
                    'key' => '_is_ba_post',
                    'compare' => 'EXISTS'
                )
            ),
            'posts_per_page' => 1,
            'order' => 'ASC'
        );

        $query = new WP_Query($query_args);

        if ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $api_key = get_option('option_api_key');
            $version = get_option('option_version');
            $summary = get_option('option_summary');

            handle_post($post_id, $api_key, $version, $summary);
        } else {
            error_log('Aucun article en brouillon trouvé.');
        }

        wp_redirect(admin_url('admin.php?page=client-plugin'));
        exit;
    }
}


/**
 * Nettoie et enregistre le contexte pour chaque article
 */

 function save_post_context() {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'post_context_') === 0) {
            $post_id = str_replace('post_context_', '', $key);
            $context = sanitize_text_field($value);
            update_post_meta($post_id, '_post_context', $context);
            error_log("Saved context for post" . $post_id . ":" . $context);
        }
    }
 }
 add_action('admin_post_save_post_context', 'save_post_context');