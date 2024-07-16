<?php 
/** Le formulaire de réglage généraux */
Gen_Article_Client_Render::render_form('settings-form');

/** Le formulaire de créationd 'articles */
Gen_Article_Client_Render::render_form('post-creation-form');

/**
 * Le formulaire de gestion des articles avant génération
 */
// Appel de la méthode de sauvegarde de contexte
$admin_instance = new Gen_Article_Client_Admin();
$admin_instance->save_post_context();

Gen_Article_Client_Render::render_form('manage-posts-form');
