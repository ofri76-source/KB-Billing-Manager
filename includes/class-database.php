<?php
if (!defined('ABSPATH')) exit;

class M365_LM_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // טבלת לקוחות
        $table_customers = $wpdb->prefix . 'm365_customers';
        $sql_customers = "CREATE TABLE IF NOT EXISTS $table_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_number varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            tenant_id varchar(255) NOT NULL,
            client_id varchar(255) DEFAULT NULL,
            client_secret text DEFAULT NULL,
            tenant_domain varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY customer_number (customer_number)
        ) $charset_collate;";
        
        // טבלת רישיונות
        $table_licenses = $wpdb->prefix . 'm365_licenses';
        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            sku_id varchar(255) NOT NULL,
            plan_name varchar(500) NOT NULL,
            cost_price decimal(10,2) NOT NULL DEFAULT 0,
            selling_price decimal(10,2) NOT NULL DEFAULT 0,
            quantity int(11) NOT NULL DEFAULT 0,
            billing_cycle varchar(20) NOT NULL DEFAULT 'monthly',
            billing_frequency varchar(50) DEFAULT NULL,
            is_deleted tinyint(1) DEFAULT 0,
            deleted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY is_deleted (is_deleted)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_customers);
        dbDelta($sql_licenses);
    }
    
    // פונקציות CRUD ללקוחות
    public static function get_customers() {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_customers';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY customer_name ASC");
    }
    
    public static function get_customer($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_customers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function save_customer($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_customers';
        
        if (isset($data['id']) && $data['id'] > 0) {
            $wpdb->update($table, $data, array('id' => $data['id']));
            return $data['id'];
        } else {
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }
    
    // פונקציות CRUD לרישיונות
    public static function get_licenses($include_deleted = false) {
        global $wpdb;
        $table_licenses = $wpdb->prefix . 'm365_licenses';
        $table_customers = $wpdb->prefix . 'm365_customers';
        
        $where = $include_deleted ? "" : "WHERE l.is_deleted = 0";
        
        return $wpdb->get_results("
            SELECT l.*, c.customer_number, c.customer_name 
            FROM $table_licenses l
            LEFT JOIN $table_customers c ON l.customer_id = c.id
            $where
            ORDER BY c.customer_name ASC, l.plan_name ASC
        ");
    }
    
    public static function get_license($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_licenses';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function save_license($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_licenses';
        
        if (isset($data['id']) && $data['id'] > 0) {
            $wpdb->update($table, $data, array('id' => $data['id']));
            return $data['id'];
        } else {
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }
    
    public static function soft_delete_license($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_licenses';
        return $wpdb->update(
            $table,
            array('is_deleted' => 1, 'deleted_at' => current_time('mysql')),
            array('id' => $id)
        );
    }
    
    public static function restore_license($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_licenses';
        return $wpdb->update(
            $table,
            array('is_deleted' => 0, 'deleted_at' => NULL),
            array('id' => $id)
        );
    }
    
    public static function hard_delete_license($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_licenses';
        return $wpdb->delete($table, array('id' => $id));
    }
    
    public static function hard_delete_all_deleted() {
        global $wpdb;
        $table = $wpdb->prefix . 'm365_licenses';
        return $wpdb->query("DELETE FROM $table WHERE is_deleted = 1");
    }

    /**
     * קבלת רשימת לקוחות מהתוסף המרכזי (dc_customers)
     */
    public static function get_dc_customers() {
        global $wpdb;
        $table = $wpdb->prefix . 'dc_customers';

        // בדיקה שהטבלה קיימת לפני ניסיון משיכה
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($table_exists !== $table) {
            return array();
        }

        return $wpdb->get_results(
            "SELECT customer_name, customer_number FROM $table WHERE is_deleted = 0 OR is_deleted IS NULL ORDER BY customer_name ASC"
        );
    }
}
