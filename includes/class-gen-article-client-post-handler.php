<?php

class Gen_Article_Client_Post_Handler {
    public function create_draft_post( $title ) {
        $post_data = array(
            'post_title'   => $title,
            'post_content' => '',
            'post_status'  => 'draft',
            'post_type'    => 'post'
        );

        $post_id = wp_insert_post( $post_data );
        add_post_meta($post_id, '_is_ba_post', true);

        return $post_id;
    }

    public function update_post_category( $post_id, $category_id ) {
        wp_set_post_categories( $post_id, array( $category_id ) );
    }

    /**
     * Marque les articles comme étant prêts
     *
     * @param [type] $post_id
     * @return void
     */
    public function mark_as_ready($post_id) {
        update_post_meta($post_id, '_is_ready', true);
    }
    
}
