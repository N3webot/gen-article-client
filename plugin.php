<?
/*
Plugin Name: article client
Plugin URI: https://www.n3web.fr
Description: Appel generateur article client
Version: 1.0.0
Author: N3web
Author URI: https://www.n3web.fr
License: GPLv2 or later
*/

function enqueue_public_scripts() {
    wp_enqueue_script('generateur-articles-public-script', plugin_dir_url(__FILE__) . 'Jscript.js', array('jquery'), '1.0.0', true);
}

add_action('admin_enqueue_scripts', 'enqueue_public_scripts');

function enqueue_public_styles() {
    wp_enqueue_style('generateur-articles-public-style', plugin_dir_url(__FILE__) . 'public-style.css');
}
add_action('wp_enqueue_scripts', 'enqueue_public_styles');

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
    include 'interface.php';
}

function handle_form_submission() {
    check_admin_referer('save_api_key_and_version_nonce', 'save_api_key_and_version_nonce_field');

    update_option('option_api_key', $_POST['api_key']);
    update_option('option_version', $_POST['version']);
    update_option('option_summary', $_POST['summary']);
    wp_redirect(admin_url('admin.php?page=client-plugin'));
    exit;
}

add_action('admin_post_save_api_key_and_version', 'handle_form_submission');

add_action('admin_post_blog_architect_generate_posts', 'save_as_draft');


//add_action('admin_post_blog_architect_handle_bulk_actions', 'update_categories_for_selected_posts');

function save_as_draft(){
    $h1_list = get_post_titles_from_request();
    error_log('h1_list: ' . print_r($h1_list, true));
    foreach($h1_list as $h1) {
        save_post_as_draft($h1);
    }
    wp_redirect(admin_url('admin.php?page=client-plugin'));
}

function get_post_titles_from_request() {
    $post_titles = sanitize_textarea_field($_POST['post_titles']);

    return array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", $post_titles))));
}

function save_post_as_draft($h1) {
    $slug = sanitize_title($h1);

    $args = [
        'post_type' => 'post',
        'name' => $slug,
        'posts_per_page' => 1,
        'fields' => 'ids'
    ];

    $post = [
        'post_title' => $h1,
        'post_status' => 'draft',
        'post_type' => 'post',
    ];

    $id = wp_insert_post($post);
    if (!$id) {  
        return false;
    }

    update_post_meta($id, '_is_ba_post', 1);

    wp_reset_postdata();
    return true;
}
/*
if ($_POST['action'] === 'blog_architect_handle_bulk_actions') {
    if (isset($_GET['post_ids'])) {
        $selected_posts = $_GET['post_ids'];
        error_log('Selected Posts: ' . print_r($selected_posts, true));
        update_categories_for_selected_posts($selected_posts);
    }
}*/
/*
function update_categories_for_selected_posts() {
    $selected_post = $_POST['post_ids'];
    error_log('Selected Posts: ' . print_r($selected_post, true));

    $selected_posts = array_map('intval', $selected_post);

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
*/

?>