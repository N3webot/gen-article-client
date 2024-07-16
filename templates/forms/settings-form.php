<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

    <input type="hidden" name="action" value="save_api_key_and_version" />
    <?php wp_nonce_field('save_api_key_and_version_nonce', 'save_api_key_and_version_nonce_field'); ?>

    <?php
    $api_key = get_option('option_api_key');
    $version = get_option('option_version');
    $summary = get_option('option_summary');
    $summary = stripslashes($summary);
    ?>

    <!-- Formulaire de configuration -->
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
                <textarea id="summary" name="summary" class="regular-text" rows="5" cols="50"><?php echo esc_textarea($summary); ?></textarea>
            </td>
        </tr>
    </table>
    <input type="submit" class="button button-primary" value="Save" />
</form>