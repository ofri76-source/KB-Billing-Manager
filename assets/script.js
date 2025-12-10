jQuery(document).ready(function($) {

    const dcCustomers = Array.isArray(m365Ajax.dcCustomers) ? m365Ajax.dcCustomers : [];
    
    // סנכרון רישיונות
    $('#sync-licenses').on('click', function() {
        const customerId = $('#customer-select').val();
        
        if (!customerId) {
            showMessage('error', 'בחר לקוח לסנכרון');
            return;
        }
        
        $(this).prop('disabled', true).text('מסנכרן...');
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'm365_sync_licenses',
                nonce: m365Ajax.nonce,
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message + ' - ' + response.data.count + ' רישיונות');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data.message);
                }
            },
            error: function() {
                showMessage('error', 'שגיאה בתקשורת עם השרת');
            },
            complete: function() {
                $('#sync-licenses').prop('disabled', false).text('סנכרון רישיונות');
            }
        });
    });
    
    // עריכת רישיון
    $(document).on('click', '.edit-license', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        
        // מילוי הנתונים מהשורה
        $('#license-id').val(id);
        $('#license-plan-name').val(row.find('.plan-name').text());
        $('#license-cost').val(row.find('[data-field="cost_price"]').text().trim());
        $('#license-selling').val(row.find('[data-field="selling_price"]').text().trim());
        $('#license-quantity').val(row.find('[data-field="quantity"]').text().trim());
        
        const billingCycle = row.find('[data-field="billing_cycle"]').text().trim() === 'חודשי' ? 'monthly' : 'yearly';
        $('#license-billing-cycle').val(billingCycle);
        $('#license-billing-frequency').val(row.find('[data-field="billing_frequency"]').text().trim());
        
        $('#edit-license-modal').fadeIn();
    });
    
    // שמירת רישיון
    $('#edit-license-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'm365_save_license' });
        formData.push({ name: 'nonce', value: m365Ajax.nonce });
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: $.param(formData),
            success: function(response) {
                if (response.success) {
                    showMessage('success', 'הרישיון נשמר בהצלחה');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('error', 'שגיאה בשמירת הרישיון');
                }
            },
            error: function() {
                showMessage('error', 'שגיאה בתקשורת עם השרת');
            }
        });
    });
    
    // מחיקת רישיון (רכה)
    $(document).on('click', '.delete-license', function() {
        if (!confirm('האם אתה בטוח שברצונך למחוק רישיון זה?')) {
            return;
        }
        
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'm365_delete_license',
                nonce: m365Ajax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                    showMessage('success', 'הרישיון הועבר לסל המחזור');
                }
            }
        });
    });
    
    // שחזור רישיון
    $(document).on('click', '.restore-license', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'm365_restore_license',
                nonce: m365Ajax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                    showMessage('success', 'הרישיון שוחזר בהצלחה');
                }
            }
        });
    });
    
    // מחיקה קשה של רישיון בודד
    $(document).on('click', '.hard-delete-license', function() {
        if (!confirm('האם אתה בטוח? פעולה זו בלתי הפיכה!')) {
            return;
        }
        
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'm365_hard_delete',
                nonce: m365Ajax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                    showMessage('success', 'הרישיון נמחק לצמיתות');
                }
            }
        });
    });
    
    // מחיקת כל הרישיונות לצמיתות
    $('#delete-all-permanent').on('click', function() {
        if (!confirm('האם אתה בטוח שברצונך למחוק את כל הרישיונות לצמיתות? פעולה זו בלתי הפיכה!')) {
            return;
        }
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'm365_hard_delete',
                nonce: m365Ajax.nonce,
                id: 0  // 0 = מחק הכל
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', 'כל הרישיונות נמחקו לצמיתות');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            }
        });
    });
    
    // טאבים בהגדרות
    $('.m365-tab-btn').on('click', function() {
        const tab = $(this).data('tab');

        $('.m365-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.m365-tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });

    // חיפוש לקוח קיים מהתוסף המרכזי
    function renderCustomerResults(results) {
        const resultsContainer = $('#customer-lookup-results');
        resultsContainer.empty();

        if (!results.length) {
            resultsContainer.hide();
            return;
        }

        results.forEach(function(customer) {
            const item = $('<div class="customer-result"></div>').text(
                `${customer.customer_number} - ${customer.customer_name}`
            );
            item.data('customer', customer);
            resultsContainer.append(item);
        });

        resultsContainer.show();
    }

    $('#customer-lookup').on('input', function() {
        const term = $(this).val().toLowerCase();

        if (!term) {
            renderCustomerResults([]);
            return;
        }

        const matches = dcCustomers.filter(function(customer) {
            return (
                (customer.customer_name && customer.customer_name.toLowerCase().includes(term)) ||
                (customer.customer_number && customer.customer_number.toLowerCase().includes(term))
            );
        });

        renderCustomerResults(matches);
    });

    $(document).on('click', '.customer-result', function() {
        const customer = $(this).data('customer');
        if (!customer) {
            return;
        }

        $('#customer-number').val(customer.customer_number || '');
        $('#customer-name').val(customer.customer_name || '');
        $('#customer-lookup-results').hide();
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.customer-lookup').length) {
            $('#customer-lookup-results').hide();
        }
    });

    // הוספת לקוח
    $('#add-customer').on('click', function() {
        $('#customer-modal-title').text('הוסף לקוח חדש');
        $('#customer-form')[0].reset();
        $('#customer-id').val('');
        $('#customer-lookup').val('');
        $('#customer-lookup-results').hide();
        $('#customer-modal').fadeIn();
    });
    
    // שמירת לקוח
    $('#customer-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'm365_save_customer' });
        formData.push({ name: 'nonce', value: m365Ajax.nonce });
        
        $.ajax({
            url: m365Ajax.ajaxurl,
            type: 'POST',
            data: $.param(formData),
            success: function(response) {
                if (response.success) {
                    showMessage('success', 'הלקוח נשמר בהצלחה');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('error', 'שגיאה בשמירת הלקוח');
                }
            }
        });
    });
    
    // יצירת סקריפט API
    $('#generate-api-script').on('click', function() {
        const tenantDomain = $('#api-customer-select').val();
        
        if (!tenantDomain) {
            alert('בחר לקוח');
            return;
        }
        
        const script = generateAPIScript(tenantDomain);
        $('#api-script-text').val(script);
        $('#api-script-output').slideDown();
    });
    
    // העתקת סקריפט API
    $('#copy-api-script').on('click', function() {
        const scriptText = $('#api-script-text');
        scriptText.select();
        document.execCommand('copy');
        
        $(this).text('הועתק!').prop('disabled', true);
        setTimeout(function() {
            $('#copy-api-script').text('העתק ללוח').prop('disabled', false);
        }, 2000);
    });
    
    // סגירת Modal
    $('.m365-modal-close, .m365-modal-cancel').on('click', function() {
        $(this).closest('.m365-modal').fadeOut();
    });
    
    // סגירת Modal בלחיצה על הרקע
    $('.m365-modal').on('click', function(e) {
        if ($(e.target).hasClass('m365-modal')) {
            $(this).fadeOut();
        }
    });
    
    // פונקציית עזר - הצגת הודעה
    function showMessage(type, message) {
        const messageDiv = $('#sync-message');
        messageDiv.removeClass('success error')
                  .addClass(type)
                  .text(message)
                  .fadeIn();
        
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 5000);
    }
    
    // פונקציה ליצירת סקריפט API
    function generateAPIScript(tenantDomain) {
        return `# Microsoft 365 API Setup Script
# הפעל סקריפט זה ב-PowerShell כמנהל

# התחברות ל-Azure AD
Connect-AzureAD -TenantDomain "${tenantDomain}"

# יצירת App Registration
$appName = "M365 License Manager - ${tenantDomain}"
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
Write-Host "5. לחץ על 'Grant admin consent for ${tenantDomain}'"
Write-Host "=================================="
`;
    }
});
