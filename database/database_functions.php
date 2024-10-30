<?php
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    class mo_gauthDB {
        private $userDetailsTable;
        
        function __construct() {
            global $wpdb;
            $this->userDetailsTable = $wpdb->prefix . 'mo_gauth_user_details';
        }
        
        public function mo_plugin_activate() {
            global $wpdb;
            $gauth = new miniorange_GAuth();
            if ( ! $gauth->mo2f_GAuth_get_option( 'mo_gauth_dbversion' ) ) {
                $gauth->mo2f_GAuth_update_option( 'mo_gauth_dbversion', 101 );
                $this->generate_tables();
            } else {
                $current_db_version = $gauth->mo2f_GAuth_get_option( 'mo_gauth_dbversion' );
                if ( $current_db_version < 101 ) {
                    $gauth->mo2f_GAuth_update_option( 'mo_gauth_dbversion', 101 );
                }
                //update the tables based on DB_VERSION.
            }
        }
        
        public function generate_tables() {
            global $wpdb;
            
            $tableName = $this->userDetailsTable;
            $sql       = $wpdb->prepare("CREATE TABLE IF NOT EXISTS " . $tableName . " (
				`user_id` bigint NOT NULL,
				`mo2f_gauth_configured` bigint NOT NULL ,
				`mo2f_gauth_key` mediumtext NOT NULL ,
				`mo_gauth_user_email` mediumtext NOT NULL,
				`mo2f_gauth_backup_codes` mediumtext NOT NULL,
				`mo2f_get_auth_rnd_string` mediumtext NOT NULL,
				UNIQUE KEY user_id (user_id) );", []);
            dbDelta( $sql );
            
            
        }
        
        
        public function insert_user( $user_id ) {
            global $wpdb;
            $sql = $wpdb->prepare( "INSERT INTO $this->userDetailsTable (user_id) VALUES(%d) ON DUPLICATE KEY UPDATE user_id=%d", [
                $user_id,
                $user_id
            ] );
            $wpdb->query( $sql );
        }
        
        public function drop_table( $table_name ) {
            global $wpdb;
            $sql = $wpdb->prepare( "DROP TABLE {$table_name}", [] );
            $wpdb->query( $sql );
        }
        
        
        public function get_user_detail( $column_name, $user_id ) {
            global $wpdb;
			$sql = $wpdb->prepare("SELECT %s FROM " . $this->userDetailsTable . " WHERE user_id = %d;", [$column_name, $user_id]);
            $user_column_detail = $wpdb->get_results( "SELECT " . $column_name . " FROM " . $this->userDetailsTable . " WHERE user_id = " . $user_id . ";" );
            $value              = empty( $user_column_detail ) ? '' : get_object_vars( $user_column_detail[0] );
            
            return $value == '' ? '' : $value[ $column_name ];
        }
        
        public function delete_user_details( $user_id ) {
            global $wpdb;
	        $sql = $wpdb->prepare( "DELETE FROM " . $this->userDetailsTable . " WHERE user_id = %d", $user_id );
            $wpdb->query( $sql );
            return;
        }
        
        public function check_if_table_exists() {
            global $wpdb;
	        $sql = $wpdb->prepare("SHOW TABLES LIKE  '" . $this->userDetailsTable . "';", []);
            $does_table_exist = $wpdb->query( $sql);
            
            return $does_table_exist;
        }
        
        public function check_if_user_column_exists( $user_id ) {
            global $wpdb;
            $sql = $wpdb->prepare( "SELECT * FROM " . $this->userDetailsTable . " WHERE user_id = %d", $user_id );
            $value = $wpdb->query( $sql);
            
            return $value;
            
        }
        
        public function update_user_details( $user_id, $update ) {
            global $wpdb;
            $count = count( $update );
            $sql   = "UPDATE " . $this->userDetailsTable . " SET ";
            $i     = 1;
            foreach ( $update as $key => $value ) {
                
                $sql .= $key . "='" . $value . "'";
                if ( $i < $count ) {
                    $sql .= ' , ';
                }
                $i ++;
            }
            $sql .= " WHERE user_id= %d;";
			$sql1 = $wpdb->prepare( $sql, $user_id);
            $wpdb->query( $sql1 );
            
            return;
            
        }
        
    }