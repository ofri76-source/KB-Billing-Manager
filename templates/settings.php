<div class="m365-lm-container">
    <div class="m365-nav-links">
        <a class="m365-btn m365-btn-secondary" href="https://kb.macomp.co.il/?page_id=14296">ראשי</a>
        <a class="m365-btn m365-btn-secondary" href="https://kb.macomp.co.il/?page_id=14291">סל מחזור</a>
        <a class="m365-btn m365-btn-secondary active" href="https://kb.macomp.co.il/?page_id=14292">הגדרות</a>
        <a class="m365-btn m365-btn-success" href="https://kb.macomp.co.il/?page_id=14292#customers-table">לקוח חדש</a>
    </div>
    <div class="m365-header">
        <h2>הגדרות</h2>
    </div>
    
    <div class="m365-settings-tabs">
        <button class="m365-tab-btn active" data-tab="customers">ניהול לקוחות</button>
        <button class="m365-tab-btn" data-tab="api-setup">הגדרת API</button>
    </div>
    
    <!-- טאב לקוחות -->
    <div class="m365-tab-content active" id="customers-tab">
        <div class="m365-section">
            <h3>לקוחות רשומים</h3>
            <button id="add-customer" class="m365-btn m365-btn-primary">הוסף לקוח חדש</button>
            
            <table id="customers-table" class="m365-table" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>מספר לקוח</th>
                        <th>שם לקוח</th>
                        <th>Tenant ID</th>
                        <th>Client ID</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="5" class="no-data">אין לקוחות רשומים</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo esc_html($customer->customer_number); ?></td>
                                <td><?php echo esc_html($customer->customer_name); ?></td>
                                <td><?php echo esc_html(substr($customer->tenant_id, 0, 20)) . '...'; ?></td>
                                <td><?php echo esc_html(substr($customer->client_id, 0, 20)) . '...'; ?></td>
                                <td>
                                    <button class="m365-btn m365-btn-small edit-customer" data-id="<?php echo $customer->id; ?>">
                                        ערוך
                                    </button>
                                    <button class="m365-btn m365-btn-small m365-btn-danger delete-customer" data-id="<?php echo $customer->id; ?>">
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
    
    <!-- טאב הגדרת API -->
    <div class="m365-tab-content" id="api-setup-tab">
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
            
            <button id="generate-api-script" class="m365-btn m365-btn-primary">צור סקריפט</button>
            
            <div id="api-script-output" style="display:none; margin-top: 20px;">
                <h4>סקריפט PowerShell:</h4>
                <textarea id="api-script-text" readonly style="width: 100%; height: 400px; font-family: monospace; direction: ltr; text-align: left;"></textarea>
                <button id="copy-api-script" class="m365-btn m365-btn-success">העתק ללוח</button>
            </div>
            
            <div class="m365-info-box" style="margin-top: 20px;">
                <h4>הוראות שימוש:</h4>
                <ol>
                    <li>בחר לקוח מהרשימה</li>
                    <li>לחץ על "צור סקריפט"</li>
                    <li>העתק את הסקריפט והפעל אותו ב-PowerShell כמנהל</li>
                    <li>העתק את הפרטים שיוצגו (Tenant ID, Client ID, Client Secret)</li>
                    <li>עדכן את פרטי הלקוח בטאב "ניהול לקוחות"</li>
                    <li>אשר את ההרשאות ב-Azure Portal</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Modal לעריכת/הוספת לקוח -->
<div id="customer-modal" class="m365-modal" style="display:none;">
    <div class="m365-modal-content">
        <span class="m365-modal-close">&times;</span>
        <h3 id="customer-modal-title">הוסף לקוח חדש</h3>
        <form id="customer-form">
            <input type="hidden" id="customer-id" name="id">

            <div class="form-group customer-lookup">
                <label>חיפוש לקוח קיים (מהתוסף המרכזי):</label>
                <input type="text" id="customer-lookup" placeholder="התחל להקליד שם או מספר לקוח">
                <div id="customer-lookup-results" class="customer-lookup-results"></div>
                <small class="customer-lookup-hint">הקלד כל חלק מהמחרוזת ולחץ על התוצאה כדי למלא את הטופס.</small>
            </div>

            <div class="form-group">
                <label>מספר לקוח:</label>
                <input type="text" id="customer-number" name="customer_number" required>
            </div>
            
            <div class="form-group">
                <label>שם לקוח:</label>
                <input type="text" id="customer-name" name="customer_name" required>
            </div>
            
            <div class="form-group">
                <label>Tenant ID:</label>
                <input type="text" id="customer-tenant-id" name="tenant_id" required>
            </div>
            
            <div class="form-group">
                <label>Client ID:</label>
                <input type="text" id="customer-client-id" name="client_id">
            </div>
            
            <div class="form-group">
                <label>Client Secret:</label>
                <input type="password" id="customer-client-secret" name="client_secret">
            </div>
            
            <div class="form-group">
                <label>Tenant Domain:</label>
                <input type="text" id="customer-tenant-domain" name="tenant_domain" placeholder="example.onmicrosoft.com">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="m365-btn m365-btn-primary">שמור</button>
                <button type="button" class="m365-btn m365-modal-cancel">ביטול</button>
            </div>
        </form>
    </div>
</div>
