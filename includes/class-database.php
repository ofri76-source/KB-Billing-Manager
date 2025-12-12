<?php
if (!defined('ABSPATH')) exit;

class M365_LM_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // טבלת לקוחות (ברירת מחדל קיימת)
        $table_customers = $wpdb->prefix . 'm365_customers';
        $sql_customers = "CREATE TABLE IF NOT EXISTS $table_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_number varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            tenant_id varchar(255) NOT NULL,
            client_id varchar(255) DEFAULT NULL,
            client_secret text DEFAULT NULL,
            tenant_domain varchar(255) DEFAULT NULL,
            last_connection_status varchar(20) DEFAULT 'unknown',
            last_connection_message text DEFAULT NULL,
            last_connection_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY customer_number (customer_number)
        ) $charset_collate;";

        // טבלת לקוחות חדשה עם סכימה מעודכנת
        $kb_customers_table = $wpdb->prefix . 'kb_billing_customers';
        $sql_kb_customers = "CREATE TABLE IF NOT EXISTS {$kb_customers_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_number varchar(50) DEFAULT NULL,
            customer_name varchar(255) DEFAULT NULL,
            tenant_id varchar(255) DEFAULT NULL,
            client_id varchar(255) DEFAULT NULL,
            client_secret text DEFAULT NULL,
            tenant_domain varchar(255) DEFAULT NULL,
            last_connection_status varchar(20) DEFAULT 'unknown',
            last_connection_message text DEFAULT NULL,
            last_connection_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY customer_number (customer_number)
        ) {$charset_collate};";

        // טבלת לוגים
        $table_logs = $wpdb->prefix . 'kb_billing_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            level varchar(20) NOT NULL DEFAULT 'info',
            context varchar(100) DEFAULT NULL,
            customer_id bigint(20) DEFAULT NULL,
            message text,
            data longtext,
            PRIMARY KEY (id),
            KEY idx_time (event_time),
            KEY idx_level (level),
            KEY idx_context (context),
            KEY idx_customer (customer_id)
        ) {$charset_collate};";

        // טבלת רישיונות קיימת
        $table_licenses = $wpdb->prefix . 'm365_licenses';
        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            sku_id varchar(255) NOT NULL,
            plan_name varchar(500) NOT NULL,
            billing_account varchar(255) DEFAULT NULL,
            enabled_units int(11) NOT NULL DEFAULT 0,
            consumed_units int(11) NOT NULL DEFAULT 0,
            status_text varchar(100) DEFAULT NULL,
            cost_price decimal(10,2) NOT NULL DEFAULT 0,
            selling_price decimal(10,2) NOT NULL DEFAULT 0,
            quantity int(11) NOT NULL DEFAULT 0,
            billing_cycle varchar(20) NOT NULL DEFAULT 'monthly',
            billing_frequency varchar(50) DEFAULT NULL,
            renewal_date date DEFAULT NULL,
            notes text DEFAULT NULL,
            is_deleted tinyint(1) DEFAULT 0,
            deleted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY is_deleted (is_deleted)
        ) $charset_collate;";

        // טבלת רישיונות חדשה בשם kb_billing_licenses
        $kb_licenses_table = $wpdb->prefix . 'kb_billing_licenses';
        $sql_kb_licenses = "CREATE TABLE {$kb_licenses_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT(20) UNSIGNED NOT NULL,
            program_name VARCHAR(255) NOT NULL,
            sku VARCHAR(150) DEFAULT '',
            billing_account VARCHAR(255) DEFAULT NULL,
            cost_price DECIMAL(10,2) DEFAULT 0,
            selling_price DECIMAL(10,2) DEFAULT 0,
            quantity INT(11) DEFAULT 0,
            enabled_units INT(11) DEFAULT 0,
            consumed_units INT(11) DEFAULT 0,
            status_text varchar(100) DEFAULT NULL,
            billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
            billing_frequency INT(11) DEFAULT 1,
            renewal_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_program (customer_id, program_name)
        ) {$charset_collate};";

        // טבלת סוגי רישיונות (אופציונלית אך שימושית)
        $types_table = $wpdb->prefix . 'kb_billing_license_types';
        $sql_license_types = "CREATE TABLE {$types_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sku VARCHAR(150) NOT NULL,
            name VARCHAR(255) NOT NULL,
            default_cost_price DECIMAL(10,2) DEFAULT 0,
            default_selling_price DECIMAL(10,2) DEFAULT 0,
            default_billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
            default_billing_frequency INT(11) DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_customers);
        dbDelta($sql_kb_customers);
        dbDelta($sql_licenses);
        dbDelta($sql_kb_licenses);
        dbDelta($sql_license_types);
        dbDelta($sql_logs);

        self::ensure_legacy_license_schema($table_licenses);
        self::maybe_add_column($kb_licenses_table, 'billing_account', "billing_account VARCHAR(255) DEFAULT NULL AFTER sku");
        self::maybe_add_column($kb_licenses_table, 'renewal_date', "renewal_date DATE DEFAULT NULL AFTER billing_frequency");
        self::maybe_add_column($kb_licenses_table, 'notes', "notes TEXT NULL AFTER renewal_date");
    }
    
    // פונקציות CRUD ללקוחות
    public static function get_customers() {
        global $wpdb;
        $table = self::get_customers_table_name();
        return $wpdb->get_results("SELECT * FROM $table ORDER BY customer_name ASC");
    }

    public static function get_customer($id) {
        global $wpdb;
        $table = self::get_customers_table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function save_customer($data) {
        global $wpdb;
        $table = self::get_customers_table_name();

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
        $table_licenses  = $wpdb->prefix . 'm365_licenses';
        $table_customers = self::get_customers_table_name();
        
        $where = $include_deleted ? "" : "WHERE l.is_deleted = 0";
        
        return $wpdb->get_results("
            SELECT l.*, c.customer_number, c.customer_name, c.tenant_domain
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
            $result = $wpdb->update($table, $data, array('id' => $data['id']));
        } else {
            $result = $wpdb->insert($table, $data);
            $data['id'] = $wpdb->insert_id;
        }

        if ($result === false) {
            M365_LM_Database::log_event(
                'error',
                'save_license',
                'DB error while saving license',
                isset($data['customer_id']) ? intval($data['customer_id']) : null,
                array(
                    'sql_error' => $wpdb->last_error,
                    'data'      => $data,
                )
            );
        }

        return isset($data['id']) ? $data['id'] : null;
    }

    /**
     * עדכון או יצירה של רישיון לפי SKU ולקוח
     */
    public static function upsert_license_by_sku($customer_id, $sku_id, $data) {
        global $wpdb;

        $table = $wpdb->prefix . 'm365_licenses';
        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE customer_id = %d AND sku_id = %s",
                intval($customer_id),
                sanitize_text_field($sku_id)
            )
        );

        if ($existing_id) {
            $data['id'] = intval($existing_id);
        }

        return self::save_license($data);
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
     * קבלת סוגי רישיונות מטבלת ברירת המחדל
     */
    public static function get_license_types() {
        global $wpdb;
        $types_table = $wpdb->prefix . 'kb_billing_license_types';

        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $types_table));
        if ($table_exists !== $types_table) {
            return array();
        }

        return $wpdb->get_results(
            $wpdb->prepare("SELECT sku, name, default_cost_price AS cost_price, default_selling_price AS selling_price, default_billing_cycle AS billing_cycle, default_billing_frequency AS billing_frequency FROM {$types_table} WHERE is_active = %d ORDER BY name", 1)
        );
    }

    /**
     * קבלת רשימת לקוחות מהתוסף המרכזי (dc_customers)
     */
    public static function get_dc_customers() {
        global $wpdb;
        $primary_table   = $wpdb->prefix . 'kb_customers';
        $fallback_table  = $wpdb->prefix . 'dc_customers';

        // בדיקה שהטבלה קיימת לפני ניסיון משיכה
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $primary_table));
        $table_to_use = $table_exists === $primary_table ? $primary_table : $fallback_table;

        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_to_use));
        if ($table_exists !== $table_to_use) {
            return array();
        }

        return $wpdb->get_results(
            "SELECT customer_name, customer_number FROM {$table_to_use} WHERE is_deleted = 0 OR is_deleted IS NULL ORDER BY customer_name ASC"
        );
    }

    /**
     * זיהוי טבלת הלקוחות בשימוש (עדיפות לטבלה החדשה)
     */
    public static function get_customers_table_name() {
        global $wpdb;
        $kb_table      = $wpdb->prefix . 'kb_billing_customers';
        $legacy_table  = $wpdb->prefix . 'm365_customers';

        $kb_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $kb_table)) === $kb_table;
        $legacy_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $legacy_table)) === $legacy_table;

        if ($kb_exists) {
            return $kb_table;
        }

        return $legacy_exists ? $legacy_table : $kb_table;
    }

    /**
     * עדכון סטטוס חיבור אחרון ללקוח
     */

    public static function log_event($level, $context, $message, $customer_id = null, $data = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'kb_billing_logs';

        $insert = array(
            'event_time'  => current_time('mysql'),
            'level'       => sanitize_text_field($level),
            'context'     => sanitize_text_field($context),
            'customer_id' => !empty($customer_id) ? intval($customer_id) : null,
            'message'     => $message,
            'data'        => !empty($data) ? wp_json_encode($data) : null,
        );

        $format = array('%s','%s','%s','%d','%s','%s');
        $result = $wpdb->insert($table, $insert, $format);

        if ($result !== false) {
            self::prune_logs();
        }

        return $result;
    }

    public static function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit'       => 200,
            'customer_id' => null,
            'level'       => '',
            'context'     => '',
        );
        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'kb_billing_logs';

        $where = array();
        if (!empty($args['customer_id'])) {
            $where[] = $wpdb->prepare('customer_id = %d', $args['customer_id']);
        }
        if (!empty($args['level'])) {
            $where[] = $wpdb->prepare('level = %s', $args['level']);
        }
        if (!empty($args['context'])) {
            $where[] = $wpdb->prepare('context = %s', $args['context']);
        }

        $sql = "SELECT * FROM {$table}";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY event_time DESC';

        $limit = intval($args['limit']);
        if ($limit > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d', $limit);
        }

        return $wpdb->get_results($sql);
    }

    public static function get_log_retention_days() {
        $days = intval(get_option('kbbm_log_retention_days', 120));
        return $days > 0 ? $days : 120;
    }

    public static function prune_logs($days = null) {
        global $wpdb;

        $days = $days !== null ? intval($days) : self::get_log_retention_days();
        $days = $days > 0 ? $days : 120;

        $table = $wpdb->prefix . 'kb_billing_logs';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE event_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }


    public static function update_connection_status($customer_id, $status, $message = '') {
        global $wpdb;

        $table = self::get_customers_table_name();
        $data  = array(
            'last_connection_status'  => sanitize_text_field($status),
            'last_connection_message' => $message !== null ? wp_kses_post($message) : null,
            'last_connection_time'    => current_time('mysql'),
        );

        return $wpdb->update($table, $data, array('id' => intval($customer_id)));
    }

    private static function maybe_add_column($table, $column, $definition) {
        global $wpdb;

        $column_exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column)) === $column;

        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;

        if ($table_exists && !$column_exists) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$definition}");
        }
    }

    /**
     * Ensures the legacy licenses table contains the required columns and logs if repairs fail.
     */
    private static function ensure_legacy_license_schema($table_licenses) {
        global $wpdb;

        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_licenses)) === $table_licenses;

        if (!$table_exists) {
            self::log_event(
                'error',
                'schema_check',
                'Legacy license table is missing; recreate the plugin tables.',
                null,
                array('table' => $table_licenses)
            );
            return;
        }

        $required_columns = array(
            'billing_account'   => "billing_account VARCHAR(255) DEFAULT NULL AFTER plan_name",
            'enabled_units'     => "enabled_units int(11) NOT NULL DEFAULT 0 AFTER plan_name",
            'consumed_units'    => "consumed_units int(11) NOT NULL DEFAULT 0 AFTER enabled_units",
            'status_text'       => "status_text varchar(100) DEFAULT NULL AFTER consumed_units",
            'cost_price'        => "cost_price decimal(10,2) NOT NULL DEFAULT 0 AFTER status_text",
            'selling_price'     => "selling_price decimal(10,2) NOT NULL DEFAULT 0 AFTER cost_price",
            'quantity'          => "quantity int(11) NOT NULL DEFAULT 0 AFTER selling_price",
            'billing_cycle'     => "billing_cycle varchar(20) NOT NULL DEFAULT 'monthly' AFTER quantity",
            'billing_frequency' => "billing_frequency varchar(50) DEFAULT NULL AFTER billing_cycle",
            'renewal_date'      => "renewal_date DATE DEFAULT NULL AFTER billing_frequency",
            'notes'             => "notes TEXT NULL AFTER renewal_date",
            'is_deleted'        => "is_deleted tinyint(1) DEFAULT 0 AFTER notes",
            'deleted_at'        => "deleted_at datetime DEFAULT NULL AFTER is_deleted",
        );

        foreach ($required_columns as $column => $definition) {
            self::maybe_add_column($table_licenses, $column, $definition);
        }

        $current_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_licenses}");
        $missing = array_values(array_diff(array_keys($required_columns), $current_columns));

        if (!empty($missing)) {
            self::log_event(
                'error',
                'schema_check',
                'Legacy license table is missing required columns; please drop and recreate or add them manually.',
                null,
                array(
                    'table'            => $table_licenses,
                    'missing_columns'  => $missing,
                    'instructions'     => 'DROP TABLE and reactivate the plugin, or run ALTER TABLE to add the missing columns.',
                )
            );
        }
    }
}
