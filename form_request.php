<?php

/**
 * Gère les opérations pour un post donné.
 *
 * @param int $post_id ID du post.
 * @param string $api_key Clé API.
 * @param string $version Version.
 * @param string $summary Résumé.
 */
function handle_post($post_id, $api_key, $version, $summary) {
    $subject = get_the_title($post_id);
    $context = get_post_meta($post_id, '_post_context', true) ?: '';

    // Préparation des données pour l'API
    $data = array(
        'api_key' => $api_key,
        'ndd' => parse_url(get_site_url(), PHP_URL_HOST),
        'subject' => $subject,
        'summary' => $summary,
        'context' => $context,
        'version' => $version
    );

    // Envoi de la requête à l'API
    $response = wp_remote_post('https://dev-blog-gen.n3web-dev.fr/wp-json/blog-architect/v1/endpoint', array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($data),
        'timeout' => 500,
    ));

    // Vérification de la réponse de l'API
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
        process_api_response($response, $post_id);
    }
}

/**
 * Traite la réponse de l'API et gère la création du nouveau post.
 *
 * @param array $response Réponse de l'API.
 * @param int $post_id ID du post d'origine.
 */
function process_api_response($response, $post_id) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['id'])) {
        $article_title = $data['id']['post_result']['metas']['title'];
        $content = $data['id']['post_result']['content'];
        $thumbnail = $data['id']['post_result']['thumbnail'];

        // Insertion d'un nouveau post
        $post_article = wp_insert_post(array(
            'post_title' => $article_title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'post',
        ));

        // Attribution des catégories au nouveau post
        $category_ids = array_map(function($category) {
            return $category->term_id;
        }, wp_get_post_categories($post_id, array('fields' => 'all')));
        wp_set_post_categories($post_article, $category_ids);

        // Ajout de l'image à la une
        media_sideload_image($thumbnail, $post_article, $article_title);
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'numberposts' => 1,
            'order' => 'DESC',
        ));
        set_post_thumbnail($post_article, $attachments[0]->ID);

        // Suppression du post d'origine
        wp_delete_post($post_id, true);
    }
}

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

?>
