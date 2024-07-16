<?php

class Gen_Article_Client_API {
    
    /**
     * Gère la génération d'un article
     *
     * @param [type] $post_id
     * @param [type] $api_key
     * @param [type] $version
     * @param [type] $summary
     * @return void
     */
    public function handle_post($post_id, $api_key, $version, $summary) {
        try {
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
                $this->process_api_response($response, $post_id);
            } else {
                error_log('Erreur API : ' . wp_remote_retrieve_response_message($response));
                throw new Exception('Erreur API : ' . wp_remote_retrieve_response_message($response));
            }
        } catch (Exception $e) {
            error_log('Exception dans handle_post : ' . $e->getMessage());
        }
    }

    /**
     * Gère la réponse de l'appel API
     * Sauvegarde le contenu et publie l'article
     *
     * @param [type] $response
     * @param [type] $post_id
     * @return void
     */
    private function process_api_response($response, $post_id) {
        try {
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

                if (is_wp_error($post_article)) {
                    throw new Exception('Erreur lors de la création du post : ' . $post_article->get_error_message());
                }

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

                if (!empty($attachments)) {
                    set_post_thumbnail($post_article, $attachments[0]->ID);
                } else {
                    throw new Exception('Erreur lors de la récupération de la miniature');
                }

                wp_delete_post($post_id, true);

                error_log('Nouveau post publié avec succès pour le post_id: ' . $post_article);
            } else {
                error_log('Erreur de réponse : ' . print_r($data, true));
                throw new Exception('Erreur de réponse : ' . print_r($data, true));
            }
        } catch (Exception $e) {
            error_log('Exception dans process_api_response : ' . $e->getMessage());
        }
    }
}
