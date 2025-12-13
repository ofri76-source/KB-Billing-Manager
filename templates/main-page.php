<?php
if (!defined('ABSPATH')) exit;

$main_url     = 'https://kb.macomp.co.il/?page_id=14296';
$recycle_url  = 'https://kb.macomp.co.il/?page_id=14291';
$settings_url = 'https://kb.macomp.co.il/?page_id=14292';
$logs_url     = 'https://kb.macomp.co.il/?page_id=14285';
$alerts_url   = 'https://kb.macomp.co.il/?page_id=14290';
$active       = isset($active) ? $active : '';

// Billing period input removed from header per user request; keep defaults for downstream use if present
$grouped_customers = array();
$types_by_sku      = array();

if (!empty($license_types)) {
    foreach ($license_types as $type) {
        if (!empty($type->sku)) {
            $types_by_sku[strtolower($type->sku)] = $type;
        }
    }
}

if (!empty($licenses)) {
    foreach ($licenses as $license) {
        $cid = isset($license->customer_id) ? $license->customer_id : $license->customer_number;

        $sku_key = '';
        if (!empty($license->sku_id)) {
            $sku_key = strtolower($license->sku_id);
        } elseif (!empty($license->sku)) {
            $sku_key = strtolower($license->sku);
        }

        $type = (!empty($sku_key) && isset($types_by_sku[$sku_key])) ? $types_by_sku[$sku_key] : null;

        if ($type && isset($type->show_in_main) && intval($type->show_in_main) === 0) {
            continue;
        }

        $display_plan_name = $license->plan_name;
        if ($type) {
            if (!empty($type->display_name)) {
                $display_plan_name = $type->display_name;
            } elseif (!empty($type->name)) {
                $display_plan_name = $type->name;
            }
        }

        $license->display_plan_name = $display_plan_name;

        if (!isset($grouped_customers[$cid])) {
            $grouped_customers[$cid] = array(
                'customer_number' => $license->customer_number ?? '',
                'customer_name'   => $license->customer_name ?? '',
                'tenant_domain'   => $license->tenant_domain ?? '',
                'licenses'        => array(),
            );
        }

        $grouped_customers[$cid]['licenses'][] = $license;
    }
}
?>

<div class="m365-lm-container">
    <div class="m365-nav-links">
        <a href="<?php echo esc_url($main_url); ?>" class="<?php echo $active === 'main' ? 'active' : ''; ?>">ראשי</a>
        <a href="<?php echo esc_url($recycle_url); ?>" class="<?php echo $active === 'recycle' ? 'active' : ''; ?>">סל מחזור</a>
        <a href="<?php echo esc_url($settings_url); ?>" class="<?php echo $active === 'settings' ? 'active' : ''; ?>">הגדרות</a>
        <a href="<?php echo esc_url($logs_url); ?>" class="<?php echo $active === 'logs' ? 'active' : ''; ?>">לוגים</a>
        <a href="<?php echo esc_url($alerts_url); ?>" class="<?php echo $active === 'alerts' ? 'active' : ''; ?>">התראות</a>
    </div>

    <div class="m365-header">
        <div class="m365-header-left">
            <h2>ניהול רישיונות Microsoft 365</h2>
        </div>
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
            <button id="sync-all-licenses" class="m365-btn m365-btn-secondary">סנכרון הכל</button>
        </div>
    </div>

    <div id="sync-message" class="m365-message" style="display:none;"></div>

    <div class="m365-table-wrapper">
        <table class="m365-table kbbm-report-table kbbm-details-table">
            <thead>
                <tr class="customer-header-row">
                    <th>מספר לקוח</th>
                    <th>שם לקוח</th>
                    <th>Tenant Domain</th>
                    <th>סה"כ חיובים</th>
                    <th class="actions">פעולות</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($grouped_customers)): ?>
                <tr>
                    <td colspan="5" class="kbbm-no-data">אין נתונים להצגה. בצע סנכרון ראשוני.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($grouped_customers as $cid => $customer): ?>
                    <?php
                        $total_charges = 0;
                        $customer_notes = '';
                        foreach ($customer['licenses'] as $license) {
                            $total_purchased = ($license->quantity > 0) ? $license->quantity : $license->enabled_units;
                            $total_charges  += $total_purchased * $license->selling_price;
                            if (empty($customer_notes) && !empty($license->notes)) {
                                $customer_notes = $license->notes;
                            }
                        }

                        $has_customer_number = !empty($customer['customer_number']);
                        $has_customer_name   = !empty($customer['customer_name']);
                        $has_tenant_domain   = !empty($customer['tenant_domain']);
                        $has_total_charges   = $total_charges > 0;
                    ?>
                    <tr class="customer-summary" data-customer="<?php echo esc_attr($cid); ?>">
                        <td class="<?php echo $has_customer_number ? '' : 'kbbm-empty-summary'; ?>"><?php echo $has_customer_number ? esc_html($customer['customer_number']) : ''; ?></td>
                        <td class="<?php echo $has_customer_name ? '' : 'kbbm-empty-summary'; ?>"><?php echo $has_customer_name ? esc_html($customer['customer_name']) : ''; ?></td>
                        <td class="<?php echo $has_tenant_domain ? '' : 'kbbm-empty-summary'; ?>"><?php echo $has_tenant_domain ? esc_html($customer['tenant_domain']) : ''; ?></td>
                        <td class="<?php echo $has_total_charges ? '' : 'kbbm-empty-summary'; ?>"><?php echo $has_total_charges ? number_format($total_charges, 2) : ''; ?></td>
                        <td class="actions">
                            <button type="button" class="kb-toggle-details" aria-label="הצג פירוט">▼</button>
                        </td>
                    </tr>
                    <tr class="customer-details" data-customer="<?php echo esc_attr($cid); ?>" style="display:none;">
                        <td colspan="5">
                            <div class="inner-wrap">
                                <table class="kbbm-details-table-inner">
                                    <thead>
                                        <tr class="plans-header-row">
                                            <th class="col-plan-display">תוכנית ללקוח</th>
                                            <th>חשבון חיוב</th>
                                            <th class="col-numeric">מחיר ללקוח</th>
                                            <th class="col-numeric">מחיר רכישה</th>
                                            <th class="col-numeric">נרכש</th>
                                            <th class="col-numeric">בשימוש</th>
                                            <th class="col-numeric">פנוי</th>
                                            <th class="col-numeric">ת. חיוב</th>
                                            <th class="col-numeric">חודשי / שנתי</th>
                                            <th class="col-numeric">פעולות</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customer['licenses'] as $license): ?>
                                            <?php
                                                $total_purchased = ($license->quantity > 0) ? $license->quantity : $license->enabled_units;
                                                $available = $total_purchased - $license->consumed_units;
                                                $billing_display = $license->billing_cycle;
                                                if (!empty($license->billing_frequency)) {
                                                    $billing_display .= ' / ' . $license->billing_frequency;
                                                }
                                                $plan_display = isset($license->display_plan_name) ? $license->display_plan_name : $license->plan_name;
                                            ?>
                                            <tr class="license-row"
                                                data-id="<?php echo esc_attr($license->id); ?>"
                                                data-customer="<?php echo esc_attr($cid); ?>"
                                                data-billing-cycle="<?php echo esc_attr($license->billing_cycle); ?>"
                                                data-billing-frequency="<?php echo esc_attr($license->billing_frequency); ?>"
                                                data-quantity="<?php echo esc_attr($license->quantity); ?>"
                                                data-enabled="<?php echo esc_attr($license->enabled_units); ?>"
                                                data-notes="<?php echo esc_attr($license->notes); ?>"
                                            >
                                                <td class="plan-name col-plan-display" data-field="plan_name"><?php echo esc_html($plan_display); ?></td>
                                                <td data-field="billing_account"><?php echo esc_html($license->billing_account); ?></td>
                                                <td class="editable-price col-numeric" data-field="selling_price"><?php echo esc_html($license->selling_price); ?></td>
                                                <td class="editable-price col-numeric" data-field="cost_price"><?php echo esc_html($license->cost_price); ?></td>
                                                <td class="col-numeric" data-field="total_purchased"><?php echo esc_html($total_purchased); ?></td>
                                                <td class="col-numeric" data-field="consumed_units"><?php echo esc_html($license->consumed_units); ?></td>
                                                <td class="col-numeric" data-field="available_units"><?php echo esc_html($available); ?></td>
                                                <td class="col-numeric" data-field="renewal_date"><?php echo esc_html($license->renewal_date); ?></td>
                                                <td class="col-numeric" data-field="billing_cycle"><?php echo esc_html($billing_display); ?></td>
                                                <td class="actions col-numeric">
                                                    <button type="button" class="m365-btn m365-btn-small m365-btn-secondary edit-license">ערוך</button>
                                                    <button type="button" class="m365-btn m365-btn-small m365-btn-danger delete-license" data-id="<?php echo esc_attr($license->id); ?>">מחק</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="kb-notes-row" data-customer="<?php echo esc_attr($cid); ?>">
                                            <td colspan="10" class="kb-notes-cell">
                                                <strong>הערות:</strong>
                                                <span class="kb-notes-value"><?php echo esc_html($customer_notes); ?></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="edit-license-modal" class="m365-modal">
    <div class="m365-modal-content">
        <span class="m365-modal-close">&times;</span>
        <h3>עריכת רישיון</h3>
        <form id="edit-license-form">
            <input type="hidden" id="license-id" name="id">
            <input type="hidden" id="license-customer-id" name="customer_id">
            <div class="form-field">
                <label for="license-plan-name">תוכנית ללקוח</label>
                <input type="text" id="license-plan-name" name="plan_name" required>
            </div>
            <div class="form-field">
                <label for="license-billing-account">חשבון חיוב</label>
                <input type="text" id="license-billing-account" name="billing_account">
            </div>
            <div class="form-field">
                <label for="license-selling">מחיר ללקוח</label>
                <input type="number" step="0.01" id="license-selling" name="selling_price" required>
            </div>
            <div class="form-field">
                <label for="license-cost">מחיר לנו</label>
                <input type="number" step="0.01" id="license-cost" name="cost_price" required>
            </div>
            <div class="form-field">
                <label for="license-quantity">סה"כ נרכש</label>
                <input type="number" id="license-quantity" name="quantity" min="0">
            </div>
            <div class="form-field">
                <label for="license-billing-cycle">מחזור חיוב</label>
                <select id="license-billing-cycle" name="billing_cycle">
                    <option value="monthly">monthly</option>
                    <option value="yearly">yearly</option>
                </select>
            </div>
            <div class="form-field">
                <label for="license-billing-frequency">תדירות חיוב</label>
                <input type="text" id="license-billing-frequency" name="billing_frequency">
            </div>
            <div class="form-field">
                <label for="license-renewal-date">ת. חיוב</label>
                <input type="date" id="license-renewal-date" name="renewal_date">
            </div>
            <div class="form-field">
                <label for="license-notes">הערות</label>
                <textarea id="license-notes" name="notes" rows="3" style="width:100%;"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="m365-btn m365-btn-primary">שמור</button>
                <button type="button" class="m365-btn m365-btn-secondary m365-modal-cancel">ביטול</button>
            </div>
        </form>
    </div>
</div>
