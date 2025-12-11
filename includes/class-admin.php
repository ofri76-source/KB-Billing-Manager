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
                        <select id="api-customer-select" data-download-base="<?php echo esc_url(admin_url('admin-post.php?action=kbbm_download_script&customer_id=')); ?>">
                            <option value="">בחר לקוח</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo esc_attr($customer->id); ?>">
                                    <?php echo esc_html($customer->customer_name); ?> (<?php echo esc_html($customer->customer_number); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button id="generate-api-script" class="button button-primary">צור סקריפט</button>

                    <div id="api-script-output" style="display:none; margin-top: 20px;">
                        <h4>סקריפט PowerShell:</h4>
                        <textarea id="api-script-text" readonly style="width: 100%; height: 400px; font-family: monospace; direction: ltr; text-align: left;"></textarea>
                        <div class="form-actions" style="margin-top:10px;">
                            <button id="copy-api-script" class="button button-secondary" type="button">העתק ללוח</button>
                            <a id="download-api-script" class="button" href="#" target="_blank" rel="noreferrer">הורד סקריפט</a>
                        </div>
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

    $ps_template = <<<'PS'
<#
KB Billing Manager Setup Script
Customer: {{CUSTOMER_NAME}} ({{CUSTOMER_NUMBER}})
#>

param(
    [string]$TenantDomain = "{{TENANT_DOMAIN}}"
)

$ErrorActionPreference = "Stop"
$ProgressPreference    = "SilentlyContinue"

Write-Host "Starting KB Billing Manager setup for tenant $TenantDomain" -ForegroundColor Cyan

function Write-Section {
    param([string]$Text)
    Write-Host "`n==================================" -ForegroundColor DarkGray
    Write-Host $Text -ForegroundColor Cyan
    Write-Host "==================================" -ForegroundColor DarkGray
}

Add-Type -AssemblyName System.Web

# 1) Acquire device code
$clientId = "04f0c124-f2bc-4f1a-af72-7e29a4e4b007"
$scope    = "https://graph.microsoft.com/.default offline_access"
$deviceCodeResponse = Invoke-RestMethod -Method Post -Uri "https://login.microsoftonline.com/organizations/oauth2/v2.0/devicecode" -Body @{ client_id = $clientId; scope = $scope }

Write-Section "Authorize this session"
Write-Host "To continue, open the following URL and enter the code below:" -ForegroundColor Yellow
Write-Host $deviceCodeResponse.verification_uri -ForegroundColor Green
Write-Host "Code: $($deviceCodeResponse.user_code)" -ForegroundColor Green
Write-Host "Waiting up to 5 minutes for authentication..." -ForegroundColor Yellow

# 2) Poll for token up to 5 minutes
$token = $null
$endTime = (Get-Date).AddMinutes(5)
$tokenEndpoint = "https://login.microsoftonline.com/organizations/oauth2/v2.0/token"
while (-not $token -and (Get-Date) -lt $endTime) {
    Start-Sleep -Seconds [int]$deviceCodeResponse.interval
    try {
        $token = Invoke-RestMethod -Method Post -Uri $tokenEndpoint -Body @{
            grant_type = "urn:ietf:params:oauth:grant-type:device_code"
            client_id  = $clientId
            device_code = $deviceCodeResponse.device_code
            scope      = $scope
        }
    } catch {
        $err = $_.ErrorDetails.Message
        if ($err -and $err -match "authorization_pending") {
            continue
        }
        throw
    }
}

if (-not $token) {
    throw "No token received. Please restart and approve within 5 minutes."
}

$headers = @{ Authorization = "Bearer $($token.access_token)" }

# 3) Resolve tenant id
$org = Invoke-RestMethod -Method Get -Uri "https://graph.microsoft.com/v1.0/organization" -Headers $headers
$tenantId = $org.value[0].id

# 4) Find or create the application
$displayName = "KB Billing Manager - $TenantDomain"
$filter      = [System.Web.HttpUtility]::UrlEncode("displayName eq '$displayName'")
$lookupUri   = "https://graph.microsoft.com/v1.0/applications?`$filter=$filter"
$existingApp = Invoke-RestMethod -Method Get -Uri $lookupUri -Headers $headers

if ($existingApp.value.Count -gt 0) {
    $app = $existingApp.value[0]
    Write-Host "Found existing app registration: $displayName" -ForegroundColor Green
} else {
    $resourceAppId = "00000003-0000-0000-c000-000000000000" # Microsoft Graph
    $requiredRoles = @(
        @{ id = "7ab1d382-f21e-4acd-a863-ba3e13f7da61"; type = "Role" },  # Directory.Read.All
        @{ id = "498476ce-e0fe-48b0-b801-37ba7e2685c6"; type = "Role" }   # Organization.Read.All
    )

    $appPayload = @{ 
        displayName          = $displayName
        signInAudience       = "AzureADMyOrg"
        requiredResourceAccess = @(
            @{ resourceAppId = $resourceAppId; resourceAccess = $requiredRoles }
        )
    } | ConvertTo-Json -Depth 5

    $app = Invoke-RestMethod -Method Post -Uri "https://graph.microsoft.com/v1.0/applications" -Headers ($headers + @{ "Content-Type" = "application/json" }) -Body $appPayload
    Write-Host "Created app registration: $displayName" -ForegroundColor Green
}

# 5) Ensure service principal exists
$spLookup = Invoke-RestMethod -Method Get -Uri "https://graph.microsoft.com/v1.0/servicePrincipals?`$filter=appId eq '$($app.appId)'" -Headers $headers
if ($spLookup.value.Count -gt 0) {
    $sp = $spLookup.value[0]
} else {
    $sp = Invoke-RestMethod -Method Post -Uri "https://graph.microsoft.com/v1.0/servicePrincipals" -Headers ($headers + @{ "Content-Type" = "application/json" }) -Body (@{ appId = $app.appId } | ConvertTo-Json)
    Write-Host "Created service principal" -ForegroundColor Green
}

# 6) Create 2-year client secret
$endDate = (Get-Date).AddYears(2).ToString("yyyy-MM-ddTHH:mm:ssZ")
$secretPayload = @{ passwordCredential = @{ displayName = "KBBM Auto Generated"; endDateTime = $endDate } } | ConvertTo-Json
$secret = Invoke-RestMethod -Method Post -Uri "https://graph.microsoft.com/v1.0/applications/$($app.id)/addPassword" -Headers ($headers + @{ "Content-Type" = "application/json" }) -Body $secretPayload

Write-Section "KB Billing Manager App Details"
Write-Host "Customer: {{CUSTOMER_NAME}} ({{CUSTOMER_NUMBER}})" -ForegroundColor White
Write-Host "Tenant Domain: $TenantDomain" -ForegroundColor White
Write-Host "Tenant ID: $tenantId" -ForegroundColor White
Write-Host "Client ID: $($app.appId)" -ForegroundColor White
Write-Host "Client Secret: $($secret.secretText)" -ForegroundColor White
Write-Host "Secret valid until: $endDate" -ForegroundColor White

Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1) In Azure Portal, open App Registrations > $displayName" -ForegroundColor Yellow
Write-Host "2) Go to API Permissions and grant admin consent" -ForegroundColor Yellow
Write-Host "3) Copy Tenant ID, Client ID, and Client Secret into the KB Billing Manager plugin" -ForegroundColor Yellow
Write-Host "Setup complete." -ForegroundColor Green
PS;

    $script = str_replace(
        array('{{CUSTOMER_NAME}}', '{{CUSTOMER_NUMBER}}', '{{TENANT_DOMAIN}}'),
        array($customer_name, $customer_number, $tenant_domain),
        $ps_template
    );

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
