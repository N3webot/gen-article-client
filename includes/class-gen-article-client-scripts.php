<?php

class Gen_Article_Client_Scripts {
    public static function enqueue_styles() {
        wp_enqueue_style( 'gen-article-client', plugins_url( '../assets/css/gen-article-client.css', __FILE__ ) );
    }

    public static function enqueue_scripts() {
        wp_enqueue_script( 'gen-article-client', plugins_url( '../assets/js/gen-article-client.js', __FILE__ ), array( 'jquery' ), null, true );
    }
}
