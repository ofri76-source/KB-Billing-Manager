<?php
/**
 * Plugin Name: KB- Billing License Manager
 * Plugin URI: https://kb.macomp.co.il
 * Description: ניהול ומעקב אחר רישיונות Microsoft 365 עבור מספר Tenants
 * Version: 1.0.0
 * Author: O.K Software
 * Text Domain: m365-license-manager
 */

if (!defined('ABSPATH')) exit;

// הגדרת קבועים
define('M365_LM_VERSION', '1.0.0');
define('M365_LM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('M365_LM_PLUGIN_URL', plugin_dir_url(__FILE__));

// טעינת קבצים נדרשים
require_once M365_LM_PLUGIN_DIR . 'includes/class-database.php';
require_once M365_LM_PLUGIN_DIR . 'includes/class-api-connector.php';
require_once M365_LM_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once M365_LM_PLUGIN_DIR . 'includes/class-admin.php';

add_action('admin_post_kbbm_download_script', 'kbbm_download_script_handler');
add_action('admin_post_nopriv_kbbm_download_script', 'kbbm_download_script_handler');

// הפעלה והסרה
register_activation_hook(__FILE__, 'kb_billing_manager_activate');
register_deactivation_hook(__FILE__, 'm365_lm_deactivate');

// תיקון סכימה גם לאחר שדרוגים
add_action('admin_init', 'kb_billing_manager_maybe_install');

function kb_billing_manager_activate() {
    M365_LM_Database::create_tables();
    flush_rewrite_rules();
}

function kb_billing_manager_maybe_install() {
    // מריץ את יצירת/תיקון הטבלאות גם לאחר שדרוגים כדי להוסיף עמודות חסרות
    M365_LM_Database::create_tables();
}

// שמירה על תאימות לאחור
function m365_lm_activate() {
    kb_billing_manager_activate();
}

function m365_lm_deactivate() {
    flush_rewrite_rules();
}

// אתחול התוסף
add_action('plugins_loaded', 'm365_lm_init');
function m365_lm_init() {
    new M365_LM_Shortcodes();
    new M365_LM_Admin();
}
