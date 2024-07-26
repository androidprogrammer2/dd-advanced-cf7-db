<?php
/*
Plugin Name: DD Advanced CF7 DB
Description: Collects Contact Form 7 data.
Version: 1.0
Author: Developer DD
*/

add_action('admin_enqueue_scripts', 'register_script_style');

function register_script_style(){
    
    wp_register_script( 'dd-script' , plugins_url( '/assests/js/dd-developer.js' , __FILE__ ) , array() ,  time() , true );
    wp_enqueue_script( 'dd-script' );

    wp_register_style( 'dd-style', plugins_url( '/assests/css/dd-custome.css' , __FILE__ ), array(), time() , false );
    wp_enqueue_style('dd-style');
}



register_activation_hook(__FILE__, 'ddacf7db_create_table');
function ddacf7db_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        submission_data longtext NOT NULL,
        user_ip varchar(100) NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('wpcf7_mail_sent', 'ddacf7db_save_cf7_data');
function ddacf7db_save_cf7_data($contact_form) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $form_id = $contact_form->id();
        $submission_data = json_encode($submission->get_posted_data());
        $user_ip = $submission->get_meta('remote_ip');

        $wpdb->insert(
            $table_name,
            array(
                'form_id' => $form_id,
                'submission_data' => $submission_data,
                'user_ip' => $user_ip,
                'submitted_at' => current_time('mysql'),
            )
        );
    }
}

//DD Create a Admin menu
add_action('admin_menu', 'ddacf7db_admin_menu');
function ddacf7db_admin_menu() {
    add_menu_page(
        'CF7 Submissions',
        'CF7 Submissions',
        'manage_options',
        'ddacf7db-submissions',
        'ddacf7db_display_submissions'
    );
}

// DD Wordpress Dashboard widget file inclusion
include('includes/dashboard-widget.php');


include('includes/backend-cf7_data.php');








