<?php
if (!defined('ABSPATH')) exit;

$main_url     = 'https://kb.macomp.co.il/?page_id=14296';
$recycle_url  = 'https://kb.macomp.co.il/?page_id=14291';
$settings_url = 'https://kb.macomp.co.il/?page_id=14292';
$logs_url     = 'https://kb.macomp.co.il/?page_id=14285';
$active       = isset($active) ? $active : '';

// קיבוץ רישיונות לפי לקוח
$grouped_customers = array();

if (!empty($licenses)) {
    foreach ($licenses as $license) {
        $cid = isset($license->customer_id) ? $license->customer_id : $license->customer_number;

        if (!isset($grouped_customers[$cid])) {
            $grouped_customers[$cid] = array(
                'customer_number' => $license->customer_number ?? '',
                'customer_name'   => $license->customer_name ?? '',
                'tenant_domain'   => $license->tenant_domain ?? '',
                'billing_cycle'   => $license->billing_cycle ?? '',
                'total_boxes'     => 0,
                'licenses'        => array(),
            );
        }

        $grouped_customers[$cid]['licenses'][] = $license;
        $enabled_units = isset($license->quantity) ? intval($license->quantity) : (isset($license->enabled_units) ? intval($license->enabled_units) : 0);
        $grouped_customers[$cid]['total_boxes'] += $enabled_units;
    }
}
?>

<div class="m365-lm-container">
    <div class="m365-nav-links">
        <a href="<?php echo esc_url($main_url); ?>" class="<?php echo $active === 'main' ? 'active' : ''; ?>">ראשי</a>
        <a href="<?php echo esc_url($recycle_url); ?>" class="<?php echo $active === 'recycle' ? 'active' : ''; ?>">סל מחזור</a>
        <a href="<?php echo esc_url($settings_url); ?>" class="<?php echo $active === 'settings' ? 'active' : ''; ?>">הגדרות</a>
        <a href="<?php echo esc_url($logs_url); ?>" class="<?php echo $active === 'logs' ? 'active' : ''; ?>">לוגים</a>
    </div>

    <div class="m365-header">
        <h2>ניהול רישיונות Microsoft 365</h2>
        <div class="m365-actions">
            <select id="customer-select">
                <option value="">בחר לקוח לסנכרון</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo esc_attr($customer->id); ?>">
                        <?php echo esc_html($customer->customer_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="sync-licenses" class="m365-btn m365-btn-primary">סנכרון רישיונות</button>
            <button id="add-license" class="m365-btn m365-btn-success">הוסף רישיון ידני</button>
        </div>
    </div>

    <div id="sync-message" class="m365-message" style="display:none;"></div>

    <div class="kbbm-main-table">
        <div class="kbbm-main-header">
            <div>מספר לקוח</div>
            <div>שם לקוח</div>
            <div>Tenant Domain</div>
            <div>מחזור חיוב</div>
            <div>סה"כ תיבות</div>
        </div>

        <?php if (empty($grouped_customers)): ?>
            <div class="kbbm-no-data">אין נתונים להצגה. בצע סנכרון ראשוני.</div>
        <?php else: ?>
            <?php foreach ($grouped_customers as $cid => $customer): ?>
                <?php
                    $summary_id = 'kbbm-details-' . esc_attr($cid);
                    $billing_cycle = !empty($customer['billing_cycle']) ? $customer['billing_cycle'] : 'לא זמין';
                ?>
                <div class="kbbm-main-row" data-target="<?php echo $summary_id; ?>">
                    <div><?php echo esc_html($customer['customer_number']); ?></div>
                    <div><?php echo esc_html($customer['customer_name']); ?></div>
                    <div><?php echo esc_html($customer['tenant_domain']); ?></div>
                    <div><?php echo esc_html($billing_cycle); ?></div>
                    <div><?php echo intval($customer['total_boxes']); ?></div>
                </div>

                <div class="kbbm-details" id="<?php echo $summary_id; ?>">
                    <div class="kbbm-details-inner">
                        <?php if (empty($customer['licenses'])): ?>
                            <div class="kbbm-no-data">אין רישיונות ללקוח זה</div>
                        <?php else: ?>
                            <table class="kbbm-details-table">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>שם תוכנית</th>
                                        <th>יחידות זמינות</th>
                                        <th>יחידות בשימוש</th>
                                        <th>סטטוס</th>
                                        <th>תדירות</th>
                                        <th>כמות</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customer['licenses'] as $license): ?>
                                        <tr>
                                            <td><?php echo esc_html($license->sku_id); ?></td>
                                            <td class="plan-name"><?php echo esc_html($license->plan_name); ?></td>
                                            <td><?php echo intval($license->enabled_units); ?></td>
                                            <td><?php echo intval($license->consumed_units); ?></td>
                                            <td><?php echo esc_html($license->status_text); ?></td>
                                            <td><?php echo esc_html($license->billing_frequency); ?></td>
                                            <td><?php echo intval($license->quantity); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
