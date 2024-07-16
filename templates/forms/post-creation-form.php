<!-- Champs de création de nom d'articles -->
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

    <input type="hidden" name="action" value="blog_architect_generate_posts" />
    <?php wp_nonce_field('blog_architect_generate_posts_action', 'blog_architect_generate_posts_nonce'); ?>

    <h3>Enter Post Subject</h3>
    <textarea name="post_titles" rows="6" style="width: 100%;"></textarea>
    <input type="submit" class="button button-primary" name="save_as_draft" value="Generate Posts" />

</form>