<?php
include 'form_request.php';

?>

<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="save_api_key_and_version" />
    <?php wp_nonce_field('save_api_key_and_version_nonce', 'save_api_key_and_version_nonce_field'); ?>

    <?php
    $api_key = get_option('option_api_key');
    $version = get_option('option_version');
    $summary = get_option('option_summary');
    $summary = stripslashes($summary);

    ?>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="api_key">API Key</label>
            </th>
            <td>
                <input type="text" id="api_key" name="api_key" class="regular-text" value="<?php echo esc_attr($api_key); ?>">
            </td>
            <th scope="row">
                <label for="version">Version</label>
            </th>
          
            <td>
                <select id="version" name="version">
                    <option value="<?php echo esc_attr($version); ?>">src</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="summary">Summary</label>
            </th>
            <td>
                <textarea id="summary" name="summary" class="regular-text" rows="5" cols="50" ><?php echo esc_textarea($summary); ?></textarea>
            </td>
        </tr>
    </table>
    <input type="submit" class="button button-primary" value="Save" />
</form>

<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="blog_architect_generate_posts" />
    <?php wp_nonce_field('blog_architect_generate_posts_action', 'blog_architect_generate_posts_nonce'); ?>

    <h3>Enter Post Subject</h3>
        <textarea name="post_titles" rows="6" style="width: 100%;"></textarea>

        <input type="submit" class="button button-primary" name="save_as_draft" value="Generate Posts" />
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'post_context_') === 0) {
            $post_id = str_replace('post_context_', '', $key);
            $context = sanitize_text_field($value);

            update_post_meta($post_id, '_post_context', $context);
            error_log("Saved context for post" .$post_id . ":" . $context);
        }
    }
}
?>
 <h3>Generated Posts</h3>
    <form id="generated-posts-form"  method="post" >
        <input type="hidden" name="action" value="blog_architect_handle_bulk_actions" />
        <?php wp_nonce_field('blog_architect_bulk_actions_nonce', 'blog_architect_bulk_actions_nonce_field'); ?>

        <div class="bulk-actions">
            <select id="bulk-action-selector" name="bulk_action">
                <option value="">Select an action...</option>
                <option value="add_category">Add Category</option>
                <option value="remove_category">Remove Category</option>
                <option value="delete_posts">Delete Posts</option>
            </select>
            
            <select id="category-selector" name="category" style="display: none;">
                <?php
                $args = array(
                    'hide_empty' => false,
                );
                $categories = get_categories($args);
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>

            <input type="submit" id="action-button" class="button button-primary" name="save_button" value="Apply" style="display: none;">
        </div>

        <input type="submit" id="posts-ready-button" class="button button-primary" name="posts_ready_button" value="Articles prêts" style="margin-bottom: 20px;">
        <input type="submit" class="button button-primary" name="publish_action" value="Attention ! Publier articles">

        <table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <td id="cb" class="manage-column column-cb check-column">
                <input id="cb-select-all" type="checkbox">
            </td>
            <th scope="col" id="title" class="manage-column column-title column-primary">Title</th>
            <th scope="col" class="manage-column">Category</th> <!-- New column for category -->
            <th scope="col" class="manage-column">Context</th> 
        </tr>
    </thead>
    <tbody id="the-list">
        <?php
        $query_args = array(
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_is_ba_post',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key'     => '_is_ready',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        $query = new WP_Query($query_args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $context = get_post_meta($post_id, '_post_context', true);
                error_log("Retrieved context for post" . $post_id . ":" . $context);
                ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="post_ids[]" value="<?php echo get_the_ID(); ?>">
                    </th>
                    <td class="title column-title has-row-actions column-primary"><?php echo get_the_title(); ?></td>
                    <td>
                        <?php 
                        $categories = get_the_category();
                        $category_names = array();
                        foreach ($categories as $category) {
                            $category_names[] = $category->name;
                        }
                        echo implode(', ', $category_names);
                        ?>
                    </td>
                    <td>
                        <textarea name="post_context_<?php echo $post_id; ?>" rows="4" cols="50"><?php echo esc_textarea($context); ?></textarea>
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <input type="submit" name="save_context_<?php echo $post_id; ?>" value="Save Context">
                    </td>
                </tr>
                <?php
            }
            wp_reset_postdata();
        } else {
            ?>
            <tr><td colspan="3">Aucune publication trouvée</td></tr>
            <?php
        }
        ?>
    </tbody>
</table>

    </form>
</div>