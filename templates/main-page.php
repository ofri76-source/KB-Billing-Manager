<div class="m365-lm-container">
    <?php
        $main_url     = 'https://kb.macomp.co.il/?page_id=14296';
        $recycle_url  = 'https://kb.macomp.co.il/?page_id=14291';
        $settings_url = 'https://kb.macomp.co.il/?page_id=14292';
        $active       = isset($active) ? $active : '';
    ?>
    <div class="m365-nav-links">
        <a href="<?php echo esc_url($main_url); ?>" class="<?php echo $active === 'main' ? 'active' : ''; ?>">ראשי</a>
        <a href="<?php echo esc_url($recycle_url); ?>" class="<?php echo $active === 'recycle' ? 'active' : ''; ?>">סל מחזור</a>
        <a href="<?php echo esc_url($settings_url); ?>" class="<?php echo $active === 'settings' ? 'active' : ''; ?>">הגדרות</a>
    </div>
    <div class="m365-header">
        <h2>ניהול רישיונות Microsoft 365</h2>
        <div class="m365-actions">
            <select id="customer-select">
                <option value="">בחר לקוח לסנכרון</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer->id; ?>">
                        <?php echo esc_html($customer->customer_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="sync-licenses" class="m365-btn m365-btn-primary">סנכרון רישיונות</button>
            <button id="add-license" class="m365-btn m365-btn-success">הוסף רישיון ידני</button>
        </div>
    </div>
    
    <div id="sync-message" class="m365-message" style="display:none;"></div>
    
    <div class="m365-table-wrapper">
        <table class="m365-table m365-table-vertical">
            <thead>
                <tr>
                    <th><div class="vertical-header"><span>מספר לקוח</span></div></th>
                    <th><div class="vertical-header"><span>שם לקוח</span></div></th>
                    <th><div class="vertical-header"><span>שם תוכנית</span></div></th>
                    <th><div class="vertical-header"><span>עלות</span></div></th>
                    <th><div class="vertical-header"><span>מחיר</span></div></th>
                    <th><div class="vertical-header"><span>כמות</span></div></th>
                    <th><div class="vertical-header"><span>חיוב ח\ש</span></div></th>
                    <th><div class="vertical-header"><span>מחזור חיוב</span></div></th>
                    <th><div class="vertical-header"><span>פעולות</span></div></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($licenses)): ?>
                    <tr>
                        <td colspan="9" class="no-data">אין רישיונות להצגה. בצע סנכרון ראשוני.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($licenses as $license): ?>
                        <tr data-id="<?php echo $license->id; ?>">
                            <td><?php echo esc_html($license->customer_number); ?></td>
                            <td><?php echo esc_html($license->customer_name); ?></td>
                            <td class="plan-name"><?php echo esc_html($license->plan_name); ?></td>
                            <td class="editable" data-field="cost_price">
                                <?php echo number_format($license->cost_price, 2); ?>
                            </td>
                            <td class="editable" data-field="selling_price">
                                <?php echo number_format($license->selling_price, 2); ?>
                            </td>
                            <td class="editable" data-field="quantity">
                                <?php echo $license->quantity; ?>
                            </td>
                            <td class="editable" data-field="billing_cycle">
                                <?php echo $license->billing_cycle === 'monthly' ? 'חודשי' : 'שנתי'; ?>
                            </td>
                            <td class="editable" data-field="billing_frequency">
                                <?php echo esc_html($license->billing_frequency); ?>
                            </td>
                            <td class="actions">
                                <button class="m365-btn m365-btn-small edit-license" data-id="<?php echo $license->id; ?>">
                                    ערוך
                                </button>
                                <button class="m365-btn m365-btn-small m365-btn-danger delete-license" data-id="<?php echo $license->id; ?>">
                                    מחק
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal לעריכת רישיון -->
<div id="edit-license-modal" class="m365-modal" style="display:none;">
    <div class="m365-modal-content">
        <span class="m365-modal-close">&times;</span>
        <h3>עריכת רישיון</h3>
        <form id="edit-license-form">
            <input type="hidden" id="license-id" name="id">
            <input type="hidden" id="license-customer-id" name="customer_id">
            
            <div class="form-group">
                <label>שם תוכנית:</label>
                <input type="text" id="license-plan-name" name="plan_name" readonly>
            </div>
            
            <div class="form-group">
                <label>עלות:</label>
                <input type="number" step="0.01" id="license-cost" name="cost_price" required>
            </div>
            
            <div class="form-group">
                <label>מחיר ללקוח:</label>
                <input type="number" step="0.01" id="license-selling" name="selling_price" required>
            </div>
            
            <div class="form-group">
                <label>כמות:</label>
                <input type="number" id="license-quantity" name="quantity" required>
            </div>
            
            <div class="form-group">
                <label>חיוב:</label>
                <select id="license-billing-cycle" name="billing_cycle">
                    <option value="monthly">חודשי</option>
                    <option value="yearly">שנתי</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>מחזור חיוב:</label>
                <input type="text" id="license-billing-frequency" name="billing_frequency">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="m365-btn m365-btn-primary">שמור</button>
                <button type="button" class="m365-btn m365-modal-cancel">ביטול</button>
            </div>
        </form>
    </div>
</div>
