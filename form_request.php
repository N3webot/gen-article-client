<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST request received');
    $api_key = get_option('option_api_key');
    $version = get_option('option_version');
    $summary = get_option('option_summary');
    error_log('API Key: ' . $api_key);
    error_log('Version: ' . $version);
    error_log('Summary: ' . $summary);
    error_log("GET ICI");
    error_log('GET data: ' . print_r($_GET, true));
    if ($_POST['action'] === 'blog_architect_handle_bulk_actions') {
        if (isset($_POST['post_ids'])) {
            $selected_posts = $_POST['post_ids'];
            error_log('Selected Posts: ' . print_r($selected_posts, true));
            update_categories_for_selected_posts($selected_posts);
        }
    }
    if(!isset($_POST['post_ids'])){
        error_log('empty');
    }else{
        $post_ids= $_POST['post_ids'];

        foreach ($post_ids as $post_id) {
            $subject = get_the_title($post_id);
            error_log('Post ID: ' . $post_id . ', Subject: ' . $subject);
            $context = get_post_meta($post_id, '_post_context', true);
            if (empty($context)) {
                $context = "";
            }
            error_log('Post ID: ' . $post_id . ', Subject: ' . $subject . ', Context: ' . $context);

            $data = array(
                'api_key' => $api_key,
                'ndd' => parse_url(get_site_url(), PHP_URL_HOST),
                'subject' => $subject,
                'summary' => $summary,
                'context' => $context,
                'version' => $version
            );

            $categories = wp_get_post_categories($post_id, array('fields' => 'all'));
            foreach ($categories as $category) {
                error_log('Category for post ' . $post_id . ': ' . $category->name);
            }
            error_log(print_r($data, true));

            /*$response = wp_remote_post('https://dev-blog-gen.n3web-dev.fr/wp-json/blog-architect/v1/endpoint', array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json'),
                'body' => json_encode($data),
                'timeout' => 500,
            ));*/

            $response = wp_remote_post('https://dev.n3web-dev.fr/wp-json/blog-architect/v1/endpoint', array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json'),
                'body' => json_encode($data),
                'timeout' => 500,
            ));

            error_log('Response: ' . print_r($response, true));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                error_log('Data2: ' . print_r($data, true));
                error_log('Data2 ID: ' . $data['id']['post_result']['metas']['title']);
                if (isset($data['id'])) {
                    $article_title = $data['id']['post_result']['metas']['title'];
                    error_log('Article Title: ' . $article_title);
                    $content = $data['id']['post_result']['content'];
                    error_log('Content: ' . $content);
                    $thumbnail = $data['id']['post_result']['thumbnail'];
                    error_log('Thumbnail: ' . $thumbnail);
                    $post_article = wp_insert_post(array(
                        'post_title' => $article_title,
                        'post_content' => $content,
                        'post_status' => 'publish',
                        'post_type' => 'post',
                    ));
                    error_log('Post ID: ' . $post_article);
                    $category_ids = array_map(function($category) {
                        return $category->term_id;
                    }, $categories);
                    wp_set_post_categories($post_article, $category_ids);

                    media_sideload_image($thumbnail, $post_article, $article_title);
                    $attachments = get_posts(array(
                        'post_type' => 'attachment',
                        'numberposts' => 1,
                        'order' => 'DESC',
                    ));
                    set_post_thumbnail($post_article, $attachments[0]->ID);

                }
                wp_delete_post($post_id, true);
            }

        }
        
    }
}

 
function update_categories_for_selected_posts($selected_posts) {
    $selected_posts = array_map('intval', $selected_posts);

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
                // Get current categories of the post
                $current_categories = wp_get_post_categories($post_id, array('fields' => 'ids'));

                // Remove the default category from the array
                $new_categories = array_diff($current_categories, array($default_category_id));

                // Add the new category to the array
                $new_categories[] = $category_id;

                // Set the updated categories back to the post
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