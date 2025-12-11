<?php
if (!defined('ABSPATH')) exit;

class M365_LM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_m365_save_customer', array($this, 'ajax_save_customer'));
        add_action('wp_ajax_m365_delete_customer', array($this, 'ajax_delete_customer'));
        add_action('wp_ajax_m365_get_customer', array($this, 'ajax_get_customer'));
        add_action('wp_ajax_kbbm_save_customer', array($this, 'ajax_save_customer'));
        add_action('wp_ajax_kbbm_delete_customer', array($this, 'ajax_delete_customer'));
        add_action('wp_ajax_kbbm_get_customer', array($this, 'ajax_get_customer'));
        add_action('wp_ajax_kbbm_generate_script', array($this, 'ajax_generate_script'));
        add_action('wp_ajax_nopriv_kbbm_generate_script', array($this, 'ajax_generate_script'));
    }
    
    // הוספת תפריט ניהול
    public function add_admin_menu() {
        add_menu_page(
            'M365 License Manager',
            'M365 Licenses',
            'manage_options',
            'm365-license-manager',
            array($this, 'admin_page'),
            'dashicons-cloud',
            30
        );
        
        add_submenu_page(
            'm365-license-manager',
            'לקוחות',
            'לקוחות',
            'manage_options',
            'm365-customers',
            array($this, 'customers_page')
        );
        
        add_submenu_page(
            'm365-license-manager',
            'סל מחזור',
            'סל מחזור',
            'manage_options',
            'm365-recycle-bin',
            array($this, 'recycle_page')
        );
        
        add_submenu_page(
            'm365-license-manager',
            'הגדרות API',
            'הגדרות API',
            'manage_options',
            'm365-api-settings',
            array($this, 'api_settings_page')
        );
    }
    
    // טעינת סקריפטים לאדמין
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'm365') === false) {
            return;
        }
        
        wp_enqueue_style('m365-lm-admin-style', M365_LM_PLUGIN_URL . 'assets/style.css', array(), M365_LM_VERSION);
        wp_enqueue_script('m365-lm-admin-script', M365_LM_PLUGIN_URL . 'assets/script.js', array('jquery'), M365_LM_VERSION, true);
        wp_localize_script('m365-lm-admin-script', 'm365Ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('m365_nonce'),
            'dcCustomers' => M365_LM_Database::get_dc_customers(),
        ));
    }
    
    // עמוד ניהול ראשי
    public function admin_page() {
        $licenses = M365_LM_Database::get_licenses();
        $customers = M365_LM_Database::get_customers();
        $active = 'main';
        ?>
        <div class="wrap kbbm-wrap">
            <h1>ניהול רישיונות Microsoft 365</h1>
            <?php include M365_LM_PLUGIN_DIR . 'templates/main-page.php'; ?>
        </div>
        <?php
    }
    
    // עמוד לקוחות
    public function customers_page() {
        $customers = M365_LM_Database::get_customers();
        $license_types = M365_LM_Database::get_license_types();
        $active = 'settings';
        ?>
        <div class="wrap kbbm-wrap">
            <h1>ניהול לקוחות</h1>
            <?php include M365_LM_PLUGIN_DIR . 'templates/settings.php'; ?>
        </div>
        <?php
    }
    
    // עמוד סל מחזור
    public function recycle_page() {
        $deleted_licenses = M365_LM_Database::get_licenses(true);
        $deleted_licenses = array_filter($deleted_licenses, function($license) {
            return $license->is_deleted == 1;
        });
        $active = 'recycle';
        ?>
        <div class="wrap kbbm-wrap">
            <h1>סל מחזור</h1>
            <?php include M365_LM_PLUGIN_DIR . 'templates/recycle-bin.php'; ?>
        </div>
        <?php
    }
    
    // עמוד הגדרות API
    public function api_settings_page() {
        $customers = M365_LM_Database::get_customers();
        ?>
        <div class="wrap kbbm-wrap">
            <h1>הגדרות API</h1>
            <div class="m365-lm-container">
                <div class="m365-section">
                    <h3>יצירת סקריפט להגדרת API</h3>
                    <p>סקריפט זה יעזור לך להגדיר את ה-API בצד של Microsoft 365 עבור כל לקוח.</p>
                    
                    <div class="form-group">
                        <label>בחר לקוח:</label>
                        <select id="api-customer-select">
                            <option value="">בחר לקוח</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo esc_attr($customer->tenant_domain); ?>">
                                    <?php echo esc_html($customer->customer_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button id="generate-api-script" class="button button-primary">צור סקריפט</button>
                    
                    <div id="api-script-output" style="display:none; margin-top: 20px;">
                        <h4>סקריפט PowerShell:</h4>
                        <textarea id="api-script-text" readonly style="width: 100%; height: 400px; font-family: monospace; direction: ltr; text-align: left;"></textarea>
                        <button id="copy-api-script" class="button button-secondary">העתק ללוח</button>
                    </div>
                    
                    <div class="m365-info-box" style="margin-top: 20px;">
                        <h4>הוראות שימוש:</h4>
                        <ol>
                            <li>בחר לקוח מהרשימה</li>
                            <li>לחץ על "צור סקריפט"</li>
                            <li>העתק את הסקריפט והפעל אותו ב-PowerShell כמנהל</li>
                            <li>העתק את הפרטים שיוצגו (Tenant ID, Client ID, Client Secret)</li>
                            <li>עדכן את פרטי הלקוח בדף "לקוחות"</li>
                            <li>אשר את ההרשאות ב-Azure Portal</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // AJAX - שמירת לקוח
    public function ajax_save_customer() {
        check_ajax_referer('m365_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה'));
        }
        
        $data = array(
            'customer_number' => sanitize_text_field($_POST['customer_number']),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'tenant_id' => sanitize_text_field($_POST['tenant_id']),
            'client_id' => sanitize_text_field($_POST['client_id']),
            'client_secret' => sanitize_text_field($_POST['client_secret']),
            'tenant_domain' => sanitize_text_field($_POST['tenant_domain'])
        );
        
        if (!empty($_POST['id'])) {
            $data['id'] = intval($_POST['id']);
        }
        
        $result = M365_LM_Database::save_customer($data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'לקוח נשמר בהצלחה'));
        } else {
            wp_send_json_error(array('message' => 'שגיאה בשמירת הלקוח'));
        }
    }
    
    // AJAX - קבלת נתוני לקוח
    public function ajax_get_customer() {
        check_ajax_referer('m365_nonce', 'nonce');
        
        $customer_id = intval($_POST['id']);
        $customer = M365_LM_Database::get_customer($customer_id);
        
        if ($customer) {
            wp_send_json_success($customer);
        } else {
            wp_send_json_error(array('message' => 'לקוח לא נמצא'));
        }
    }
    
    // AJAX - מחיקת לקוח
    public function ajax_delete_customer() {
        check_ajax_referer('m365_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה'));
        }
        
        global $wpdb;
        $customer_id = intval($_POST['id']);
        
        // בדיקה אם יש רישיונות קשורים
        $table_licenses = $wpdb->prefix . 'm365_licenses';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_licenses WHERE customer_id = %d",
            $customer_id
        ));
        
        if ($count > 0) {
            wp_send_json_error(array('message' => 'לא ניתן למחוק לקוח עם רישיונות קיימים'));
        }
        
        $table_customers = M365_LM_Database::get_customers_table_name();
        $result = $wpdb->delete($table_customers, array('id' => $customer_id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'לקוח נמחק בהצלחה'));
        } else {
            wp_send_json_error(array('message' => 'שגיאה במחיקת הלקוח'));
        }
    }

    // AJAX - יצירת סקריפט PowerShell מותאם ללקוח
    public function ajax_generate_script() {
        check_ajax_referer('m365_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה'));
        }

        $customer_id = intval($_POST['customer_id']);
        $script      = kbbm_generate_ps_script($customer_id);

        wp_send_json(array('script' => $script));
    }
}

/**
 * יצירת סקריפט PowerShell מותאם ללקוח שנבחר
 */
function kbbm_generate_ps_script($customer_id) {
    global $wpdb;

    $table = M365_LM_Database::get_customers_table_name();
    $row   = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT customer_number, customer_name, tenant_domain FROM {$table} WHERE id = %d",
            $customer_id
        ),
        ARRAY_A
    );

    if (!$row) {
        return '';
    }

    $customer_number = sanitize_text_field($row['customer_number'] ?? '');
    $customer_name   = sanitize_text_field($row['customer_name'] ?? '');
    $tenant_domain   = sanitize_text_field($row['tenant_domain'] ?? '');

    $script = <<<PS
<#
KB Billing Manager Setup Script
Customer: {$customer_name} ({$customer_number})
#>

param(
    [string]$TenantDomain = "{$tenant_domain}"
)

# Microsoft 365 API Setup Script
# הפעל סקריפט זה ב-PowerShell כמנהל

# התחברות ל-Azure AD
Connect-AzureAD -TenantDomain "$TenantDomain"

# יצירת App Registration
$appName = "M365 License Manager - $TenantDomain"
$app = New-AzureADApplication -DisplayName $appName

# יצירת Service Principal
$sp = New-AzureADServicePrincipal -AppId $app.AppId

# יצירת Client Secret (תוקף 2 שנים)
$secret = New-AzureADApplicationPasswordCredential -ObjectId $app.ObjectId -CustomKeyIdentifier "M365LM" -EndDate (Get-Date).AddYears(2)

# הענקת הרשאות Microsoft Graph API
$graphResourceId = "00000003-0000-0000-c000-000000000000"

# Directory.Read.All
$directoryReadAll = "7ab1d382-f21e-4acd-a863-ba3e13f7da61"

# Organization.Read.All
$orgReadAll = "498476ce-e0fe-48b0-b801-37ba7e2685c6"

$requiredResourceAccess = New-Object -TypeName "Microsoft.Open.AzureAD.Model.RequiredResourceAccess"
$requiredResourceAccess.ResourceAppId = $graphResourceId

$permission1 = New-Object -TypeName "Microsoft.Open.AzureAD.Model.ResourceAccess"
$permission1.Type = "Role"
$permission1.Id = $directoryReadAll

$permission2 = New-Object -TypeName "Microsoft.Open.AzureAD.Model.ResourceAccess"
$permission2.Type = "Role"
$permission2.Id = $orgReadAll

$requiredResourceAccess.ResourceAccess = $permission1, $permission2

Set-AzureADApplication -ObjectId $app.ObjectId -RequiredResourceAccess $requiredResourceAccess

Write-Host "=================================="
Write-Host "App Registration נוצר בהצלחה!"
Write-Host "=================================="
Write-Host "לקוח: {$customer_name} ({$customer_number})"
Write-Host "Tenant Domain: $TenantDomain"
Write-Host "Tenant ID: " (Get-AzureADTenantDetail).ObjectId
Write-Host "Application (Client) ID: " $app.AppId
Write-Host "Client Secret: " $secret.Value
Write-Host "=================================="
Write-Host "העתק את הפרטים האלה למסך ההגדרות בתוסף WordPress"
Write-Host "=================================="
Write-Host ""
Write-Host "חשוב! עבור ל-Azure Portal ואשר את ההרשאות:"
Write-Host "1. היכנס ל-Azure Portal (portal.azure.com)"
Write-Host "2. עבור ל-Azure Active Directory > App Registrations"
Write-Host "3. מצא את האפליקציה: $appName"
Write-Host "4. לחץ על API Permissions"
Write-Host "5. לחץ על 'Grant admin consent for $TenantDomain'"
Write-Host "=================================="
PS;

    return $script;
}

/**
 * Handler להורדת קובץ הסקריפט ללקוח
 */
function kbbm_download_script_handler() {
    if (!current_user_can('manage_options')) {
        wp_die('No permission');
    }

    $customer_id = intval($_GET['customer_id'] ?? 0);
    if (!$customer_id) {
        wp_die('No customer selected');
    }

    $script = kbbm_generate_ps_script($customer_id);

    if (empty($script)) {
        wp_die('Customer not found');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="KBBM-Setup-' . $customer_id . '.ps1"');
    echo $script;
    exit;
}
