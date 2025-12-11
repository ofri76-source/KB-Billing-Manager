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

    // עריכת לקוח
    $(document).on('click', '.edit-customer, .kbbm-edit-customer', function(e) {
        e.preventDefault();

        const id = $(this).data('id');
        if (!id) {
            return;
        }

        $.post(m365Ajax.ajaxurl, {
            action: 'kbbm_get_customer',
            nonce: m365Ajax.nonce,
            id: id
        }, function(response) {
            if (response && response.success && response.data) {
                const customer = response.data;
                $('#customer-modal-title').text('עריכת לקוח');
                $('#customer-id').val(customer.id || '');
                $('#customer-number').val(customer.customer_number || '');
                $('#customer-name').val(customer.customer_name || '');
                $('#customer-tenant-id').val(customer.tenant_id || '');
                $('#customer-client-id').val(customer.client_id || '');
                $('#customer-client-secret').val(customer.client_secret || '');
                $('#customer-tenant-domain').val(customer.tenant_domain || '');
                $('#customer-modal').fadeIn();
            } else {
                alert('לקוח לא נמצא');
            }
        });
    });

    // מחיקת לקוח
    $(document).on('click', '.delete-customer, .kbbm-delete-customer', function(e) {
        e.preventDefault();

        const id = $(this).data('id');
        if (!id || !confirm('Delete this customer?')) {
            return;
        }

        $.post(m365Ajax.ajaxurl, {
            action: 'kbbm_delete_customer',
            nonce: m365Ajax.nonce,
            id: id
        }, function(response) {
            if (response && response.success) {
                location.reload();
            } else {
                const message = response && response.data && response.data.message ? response.data.message : 'שגיאה במחיקת הלקוח';
                alert(message);
            }
        });
    });

    // שמירת לקוח
    $('#customer-form').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'kbbm_save_customer' });
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
                    const errorMessage = response && response.data && response.data.message ? response.data.message : 'שגיאה בשמירת הלקוח';
                    showMessage('error', errorMessage);
                }
            }
        });
    });

    // יצירת סקריפט API
    $('#generate-api-script').on('click', function() {
        const customerId = $('#api-customer-select').val();
        const downloadBase = $('#api-customer-select').data('download-base') || '';

        if (!customerId) {
            alert('בחר לקוח');
            return;
        }

        $.post(m365Ajax.ajaxurl, {
            action: 'kbbm_generate_script',
            nonce: m365Ajax.nonce,
            customer_id: customerId
        }, function(response) {
            if (response && typeof response.script === 'string') {
                $('#api-script-text').val(response.script);
                $('#api-script-output').slideDown();
                $('#download-api-script').attr('href', downloadBase + customerId);
            } else {
                alert('לא ניתן ליצור סקריפט עבור הלקוח הנבחר');
            }
        });
    });

    // העתקת סקריפט API
    $('#copy-api-script').on('click', function() {
        const scriptText = $('#api-script-text').val();

        if (navigator.clipboard && scriptText) {
            navigator.clipboard.writeText(scriptText).then(() => {
                $('#copy-api-script').text('הועתק!').prop('disabled', true);
                setTimeout(function() {
                    $('#copy-api-script').text('העתק ללוח').prop('disabled', false);
                }, 2000);
            });
        } else {
            const textArea = $('#api-script-text');
            textArea.trigger('select');
            document.execCommand('copy');
        }
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
    
});
