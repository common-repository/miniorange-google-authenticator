<?php
    
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit();
    }
    require_once dirname( __FILE__ ) . '/database/database_functions.php';
    global $wpdb;
    $Mo_gauthdbQueries = new mo_gauthDB();
    $table_name        = $wpdb->prefix . 'mo_gauth_user_details';
    $Mo_gauthdbQueries->drop_table( $table_name );
    
    if ( ! is_multisite() ) {
        // delete all stored key-value pairs
        delete_option( 'mo_gauth_msg' );
        delete_option( 'mo2f_gauth_login_page' );
        delete_option( 'mo2f_gauth_issuer' );
        delete_option( 'mo2f_gauth_enable' );
        delete_option( 'mo2f_gauth_user_email' );
        delete_option( 'mo_gauth_host_name' );
        delete_option( 'mo_gauth_message' );
        delete_option( 'mo_gauth_dbversion' );
        
        
    } else {
        
        delete_site_option( 'mo_gauth_msg' );
        delete_site_option( 'mo2f_gauth_login_page' );
        delete_site_option( 'mo2f_gauth_issuer' );
        delete_site_option( 'mo2f_gauth_enable' );
        delete_site_option( 'mo_gauth_host_name' );
        delete_site_option( 'mo2f_gauth_user_email' );
        delete_site_option( 'mo_gauth_message' );
        delete_site_option( 'mo_gauth_dbversion' );
        
    }
    
    $users = get_users( array() );
    foreach ( $users as $user ) {
        delete_user_meta( $user->ID, 'mo2f_gauth_key' );
        delete_user_meta( $user->ID, 'mo2f_gauth_configured' );
        delete_user_meta( $user->ID, 'mo2f_get_auth_rnd_string' );
        delete_user_meta( $user->ID, 'mo2f_gauth_backup_codes' );
        
    }

?>