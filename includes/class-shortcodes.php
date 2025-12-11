<?php
if (!defined('ABSPATH')) exit;

class M365_LM_Shortcodes {
    
    public function __construct() {
        add_shortcode('m365_main_page', array($this, 'main_page'));
        add_shortcode('m365_recycle_bin', array($this, 'recycle_bin'));
        add_shortcode('m365_settings', array($this, 'settings_page'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_m365_sync_licenses', array($this, 'ajax_sync_licenses'));
        add_action('wp_ajax_m365_delete_license', array($this, 'ajax_delete_license'));
        add_action('wp_ajax_m365_restore_license', array($this, 'ajax_restore_license'));
        add_action('wp_ajax_m365_hard_delete', array($this, 'ajax_hard_delete'));
        add_action('wp_ajax_m365_save_license', array($this, 'ajax_save_license'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('m365-lm-style', M365_LM_PLUGIN_URL . 'assets/style.css', array(), M365_LM_VERSION);
        wp_enqueue_script('m365-lm-script', M365_LM_PLUGIN_URL . 'assets/script.js', array('jquery'), M365_LM_VERSION, true);
        wp_localize_script('m365-lm-script', 'm365Ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('m365_nonce'),
            'dcCustomers' => M365_LM_Database::get_dc_customers(),
        ));
    }
    
    // דף ראשי
    public function main_page($atts) {
        ob_start();
        $active = 'main';
        $licenses = M365_LM_Database::get_licenses();
        $customers = M365_LM_Database::get_customers();
        include M365_LM_PLUGIN_DIR . 'templates/main-page.php';
        return ob_get_clean();
    }
    
    // סל מחזור
    public function recycle_bin($atts) {
        ob_start();
        $active = 'recycle';
        $deleted_licenses = M365_LM_Database::get_licenses(true);
        $deleted_licenses = array_filter($deleted_licenses, function($license) {
            return $license->is_deleted == 1;
        });
        include M365_LM_PLUGIN_DIR . 'templates/recycle-bin.php';
        return ob_get_clean();
    }
    
    // הגדרות
    public function settings_page($atts) {
        ob_start();
        $active = 'settings';
        $customers = M365_LM_Database::get_customers();
        $license_types = M365_LM_Database::get_license_types();
        include M365_LM_PLUGIN_DIR . 'templates/settings.php';
        return ob_get_clean();
    }
    
    // AJAX - סנכרון רישיונות
    public function ajax_sync_licenses() {
        check_ajax_referer('m365_nonce', 'nonce');
        
        $customer_id = intval($_POST['customer_id']);
        $customer = M365_LM_Database::get_customer($customer_id);
        
        if (!$customer) {
            wp_send_json_error(array('message' => 'לקוח לא נמצא'));
        }
        
        $api = new M365_LM_API_Connector(
            $customer->tenant_id,
            $customer->client_id,
            $customer->client_secret
        );
        
        $skus = $api->get_subscribed_skus();
        
        if (isset($skus['error'])) {
            wp_send_json_error(array('message' => $skus['error']));
        }
        
        // שמירת הרישיונות בדאטהבייס
        foreach ($skus as $sku) {
            M365_LM_Database::save_license(array(
                'customer_id' => $customer_id,
                'sku_id' => $sku['sku_id'],
                'plan_name' => $sku['plan_name'],
                'quantity' => $sku['enabled_units'],
                'billing_cycle' => 'monthly' // ברירת מחדל
            ));
        }
        
        wp_send_json_success(array('message' => 'סנכרון הושלם בהצלחה', 'count' => count($skus)));
    }
    
    // AJAX - מחיקה רכה
    public function ajax_delete_license() {
        check_ajax_referer('m365_nonce', 'nonce');
        $id = intval($_POST['id']);
        M365_LM_Database::soft_delete_license($id);
        wp_send_json_success();
    }
    
    // AJAX - שחזור
    public function ajax_restore_license() {
        check_ajax_referer('m365_nonce', 'nonce');
        $id = intval($_POST['id']);
        M365_LM_Database::restore_license($id);
        wp_send_json_success();
    }
    
    // AJAX - מחיקה קשה
    public function ajax_hard_delete() {
        check_ajax_referer('m365_nonce', 'nonce');
        $id = intval($_POST['id']);
        
        if ($id === 0) {
            M365_LM_Database::hard_delete_all_deleted();
        } else {
            M365_LM_Database::hard_delete_license($id);
        }
        
        wp_send_json_success();
    }
    
    // AJAX - שמירת רישיון
    public function ajax_save_license() {
        check_ajax_referer('m365_nonce', 'nonce');
        
        $data = array(
            'id' => intval($_POST['id']),
            'customer_id' => intval($_POST['customer_id']),
            'plan_name' => sanitize_text_field($_POST['plan_name']),
            'cost_price' => floatval($_POST['cost_price']),
            'selling_price' => floatval($_POST['selling_price']),
            'quantity' => intval($_POST['quantity']),
            'billing_cycle' => sanitize_text_field($_POST['billing_cycle']),
            'billing_frequency' => sanitize_text_field($_POST['billing_frequency'])
        );
        
        M365_LM_Database::save_license($data);
        wp_send_json_success();
    }
}
