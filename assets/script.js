jQuery(document).ready(function($) {

    const dcCustomers = Array.isArray(m365Ajax.dcCustomers) ? m365Ajax.dcCustomers : [];
    const customerFormWrapper = $('#customer-form-wrapper');
    const customerFormPlaceholder = $('#customer-form-placeholder');
    let inlineFormRow = null;

    function hideCustomerForm() {
        if (inlineFormRow) {
            inlineFormRow.remove();
            inlineFormRow = null;
        }

        if (customerFormPlaceholder.length) {
            customerFormPlaceholder.after(customerFormWrapper);
        }

        customerFormWrapper.hide();
    }

    function showCustomerFormUnderRow(row) {
        if (!row || !row.length) {
            return;
        }

        if (inlineFormRow) {
            inlineFormRow.remove();
        }

        inlineFormRow = $('<tr class="inline-form-row"><td colspan="6"></td></tr>');
        inlineFormRow.find('td').append(customerFormWrapper);
        row.after(inlineFormRow);
        customerFormWrapper.show();
        $('html, body').animate({ scrollTop: customerFormWrapper.offset().top - 60 }, 300);
    }

    function showCustomerFormInPlaceholder() {
        if (inlineFormRow) {
            inlineFormRow.remove();
            inlineFormRow = null;
        }

        if (customerFormPlaceholder.length) {
            customerFormPlaceholder.after(customerFormWrapper);
        }

        customerFormWrapper.show();
        $('html, body').animate({ scrollTop: customerFormWrapper.offset().top - 60 }, 300);
    }

    if (customerFormWrapper.length) {
        customerFormWrapper.hide();
    }
    
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
                    const msg = response.data && response.data.message ? response.data.message : 'סנכרון הושלם בהצלחה';
                    const count = response.data && typeof response.data.count !== 'undefined' ? response.data.count : 0;
                    showMessage('success', `${msg} - ${count} רישיונות`);
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    const msg = response && response.data && response.data.message ? response.data.message : 'שגיאת Graph כללית';
                    showMessage('error', msg);
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
        $('#license-quantity').val(row.data('enabled') || row.data('quantity') || 0);

        const billingCycle = row.data('billing-cycle') || 'monthly';
        $('#license-billing-cycle').val(billingCycle);
        $('#license-billing-frequency').val(row.data('billing-frequency') || '');
        
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
        $('#customer-paste-source').val('');

        showCustomerFormInPlaceholder();
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
                $('#customer-paste-source').val('');

                const row = $(e.target).closest('tr');
                if (row.length) {
                    showCustomerFormUnderRow(row);
                } else {
                    showCustomerFormInPlaceholder();
                }
            } else {
                alert('לקוח לא נמצא');
            }
        });
    });

    $('#customer-paste-fill').on('click', function() {
        const raw = ($('#customer-paste-source').val() || '').trim();
        if (!raw) return;

        const patterns = [
            { selector: '#customer-tenant-id', regex: /Tenant\s*ID[:=\s]+([0-9a-fA-F-]{8,})/i },
            { selector: '#customer-client-id', regex: /Client\s*ID[:=\s]+([0-9a-fA-F-]{8,})/i },
            { selector: '#customer-client-id', regex: /Application\s*\(Client\)\s*ID[:=\s]+([0-9a-fA-F-]{8,})/i },
            { selector: '#customer-client-secret', regex: /Client\s*Secret[:=\s]+([A-Za-z0-9\-_.+/=]{8,})/i },
            { selector: '#customer-tenant-domain', regex: /Tenant\s*Domain[:=\s]+([\w\.-]+\.[\w\.\-]+)/i },
            { selector: '#customer-number', regex: /Customer\s*Number[:=\s]+([\w-]+)/i },
            { selector: '#customer-name', regex: /Customer\s*Name[:=\s]+(.+)/i },
        ];

        patterns.forEach(function(mapper) {
            if ($(mapper.selector).length) {
                const match = raw.match(mapper.regex);
                if (match && match[1]) {
                    $(mapper.selector).val(match[1].trim());
                }
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

    // בדיקת חיבור
    $(document).on('click', '.kbbm-test-connection', function(e) {
        e.preventDefault();

        const btn = $(this);
        const id = btn.data('id');
        const statusEl = $(`#connection-status-${id}`);

        if (!id) return;

        btn.prop('disabled', true).text('בודק...');

        $.post(m365Ajax.ajaxurl, {
            action: 'kbbm_test_connection',
            nonce: m365Ajax.nonce,
            id: id
        }, function(response) {
            const message = response && response.data && response.data.message ? response.data.message : '';
            if (response && response.success) {
                updateStatus(statusEl, 'connected', message);
            } else {
                updateStatus(statusEl, 'failed', message || 'חיבור נכשל');
                alert(message || 'חיבור נכשל');
            }
        }).always(function() {
            btn.prop('disabled', false).text('בדוק חיבור');
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

    // יצירת סקריפט API + תצוגה במודאל
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
        }).done(function(response) {
            if (response && response.success && response.data && typeof response.data.script === 'string') {
                const data = response.data;
                $('#kbbm-script-preview').val(data.script);
                $('#kbbm-script-modal').fadeIn();
                $('#kbbm-download-script').attr('href', data.download_url || (downloadBase + customerId));
                $('#kbbm-tenant-id').text(data.tenant_id || '');
                $('#kbbm-client-id').text(data.client_id || '');
                $('#kbbm-client-secret').text(data.client_secret || '');
                $('#kbbm-tenant-domain').text(data.tenant_domain || '');
            } else if (response && typeof response.script === 'string') {
                $('#kbbm-script-preview').val(response.script);
                $('#kbbm-script-modal').fadeIn();
                $('#kbbm-download-script').attr('href', downloadBase + customerId);
            } else {
                const message = response && response.data && response.data.message ? response.data.message : 'לא ניתן ליצור סקריפט עבור הלקוח הנבחר';
                alert(message);
            }
        }).fail(function() {
            alert('שגיאה ביצירת הסקריפט');
        });
    });

    // העתקת סקריפט API
    $('#kbbm-copy-script, #copy-api-script').on('click', function() {
        const scriptText = $('#kbbm-script-preview').val() || $('#api-script-text').val();

        if (navigator.clipboard && scriptText) {
            navigator.clipboard.writeText(scriptText).then(() => {
                $('#kbbm-copy-script, #copy-api-script').text('הועתק!').prop('disabled', true);
                setTimeout(function() {
                    $('#kbbm-copy-script').text('Copy Script').prop('disabled', false);
                    $('#copy-api-script').text('העתק ללוח').prop('disabled', false);
                }, 2000);
            });
        } else {
            const textArea = $('#kbbm-script-preview').length ? $('#kbbm-script-preview') : $('#api-script-text');
            textArea.trigger('select');
            document.execCommand('copy');
        }
    });
    
    // סגירת Modal
    $('.m365-modal-close, .m365-modal-cancel').on('click', function() {
        if ($(this).closest('#customer-form-wrapper').length) {
            hideCustomerForm();
            return;
        }

        $(this).closest('.m365-modal, .kbbm-modal-overlay').fadeOut();
    });
    
    // סגירת Modal בלחיצה על הרקע
    $('.m365-modal, .kbbm-modal-overlay').on('click', function(e) {
        if ($(e.target).hasClass('m365-modal') || $(e.target).hasClass('kbbm-modal-overlay')) {
            $(this).fadeOut();
        }
    });

    $('#kbbm-log-settings-form').on('submit', function(e) {
        e.preventDefault();

        const days = parseInt($('#kbbm-log-retention-days').val(), 10) || 120;

        $.post(m365Ajax.ajaxurl, {
            action: 'kbbm_save_settings',
            nonce: m365Ajax.nonce,
            log_retention_days: days
        }, function(response) {
            if (response && response.success) {
                showMessage('success', (response.data && response.data.message) ? response.data.message : 'ההגדרות נשמרו');
            } else {
                const msg = response && response.data && response.data.message ? response.data.message : 'שמירת הגדרות נכשלה';
                showMessage('error', msg);
            }
        }).fail(function() {
            showMessage('error', 'שגיאה בשמירת ההגדרות');
        });
    });

    const logTable = $('.kbbm-log-table');
    if (logTable.length) {
        const logHeaders = logTable.find('th.sortable');
        const logSearch = $('#kbbm-log-search-input');
        const logFilters = $('.kbbm-log-filter');
        let sortState = { index: 0, dir: 'desc' };

        function applyLogFilters() {
            const searchTerm = (logSearch.val() || '').toLowerCase();
            logTable.find('tbody tr').each(function() {
                const row = $(this);
                const textMatch = !searchTerm || row.text().toLowerCase().indexOf(searchTerm) !== -1;
                let filtersMatch = true;

                logFilters.each(function() {
                    const value = $(this).val();
                    const field = $(this).data('field');
                    if (!value) return;

                    const dataVal = (row.data(field) || '').toString();
                    if (field === 'tenant_domain') {
                        if (dataVal.toLowerCase() !== value.toLowerCase()) {
                            filtersMatch = false;
                            return false;
                        }
                    } else if (field === 'customer') {
                        if (String(row.data('customer')) !== String(value)) {
                            filtersMatch = false;
                            return false;
                        }
                    } else if (dataVal.toLowerCase() !== value.toLowerCase()) {
                        filtersMatch = false;
                        return false;
                    }
                });

                row.toggle(textMatch && filtersMatch);
            });
        }

        function sortLogTable(columnIndex) {
            const tbody = logTable.find('tbody');
            const rows = tbody.find('tr').get();
            const newDir = (sortState.index === columnIndex && sortState.dir === 'asc') ? 'desc' : 'asc';
            sortState = { index: columnIndex, dir: newDir };

            rows.sort(function(a, b) {
                const cellA = $(a).children('td').eq(columnIndex);
                const cellB = $(b).children('td').eq(columnIndex);
                const valA = (cellA.data('sort-value') || cellA.text()).toString().toLowerCase();
                const valB = (cellB.data('sort-value') || cellB.text()).toString().toLowerCase();

                if (valA < valB) return newDir === 'asc' ? -1 : 1;
                if (valA > valB) return newDir === 'asc' ? 1 : -1;
                return 0;
            });

            tbody.append(rows);
        }

        logHeaders.on('click', function() {
            sortLogTable($(this).index());
            applyLogFilters();
        });

        logSearch.on('input', applyLogFilters);
        logFilters.on('change', applyLogFilters);
    }
    
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

    function updateStatus(el, status, message) {
        if (!el || !el.length) return;

        el.removeClass('status-connected status-failed status-unknown')
          .addClass('status-' + status)
          .text(statusLabel(status, message));

        if (message) {
            el.attr('title', message);
        }
    }

    function statusLabel(status, message) {
        switch (status) {
            case 'connected':
                return 'מחובר';
            case 'failed':
                return message ? 'נכשל: ' + message : 'נכשל';
            default:
                return 'לא נבדק';
        }
    }
    
});
