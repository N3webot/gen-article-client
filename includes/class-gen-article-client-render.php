<?php

class Gen_Article_Client_Render {
    public static function render_template( $template, $data = array() ) {
        if ( file_exists( plugin_dir_path( __FILE__ ) . '../templates/' . $template . '.php' ) ) {
            extract( $data );
            include plugin_dir_path( __FILE__ ) . '../templates/' . $template . '.php';
        }
    }

    public static function render_form( $form, $data = array() ) {
        if ( file_exists( plugin_dir_path( __FILE__ ) . '../templates/forms/' . $form . '.php' ) ) {
            extract( $data );
            include plugin_dir_path( __FILE__ ) . '../templates/forms/' . $form . '.php';
        }
    }
}
