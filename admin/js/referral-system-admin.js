jQuery(document).ready(function($) {
    let currentPage = 1;
    let currentTab = 'codes';

    // Initialize
    init();

    function init() {
        setupTabs();
        setupChart();
        loadCodesTable();
        setupEventHandlers();
    }

    function setupTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const tab = $(this).data('tab');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show/hide content
            $('.osna-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
            
            currentTab = tab;
            
            // Load data for the tab
            switch(tab) {
                case 'codes':
                    loadCodesTable();
                    break;
                case 'usage':
                    loadUsageTable();
                    break;
                case 'rewards':
                    loadRewardsTable();
                    break;
            }
        });
    }

    function setupChart() {
        if (typeof Chart === 'undefined' || !referralsChartData) {
            return;
        }

        const ctx = document.getElementById('referralsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: referralsChartData.labels,
                datasets: [{
                    label: 'Referrals',
                    data: referralsChartData.data,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function setupEventHandlers() {
        // Create code button
        $('#create-code-btn').on('click', function() {
            openCodeModal();
        });

        // Search functionality
        $('#codes-search-btn').on('click', function() {
            currentPage = 1;
            loadCodesTable();
        });

        $('#codes-search').on('keypress', function(e) {
            if (e.which === 13) {
                currentPage = 1;
                loadCodesTable();
            }
        });

        // Code form submission
        $('#code-form').on('submit', function(e) {
            e.preventDefault();
            saveCode();
        });

        // Modal close
        $('.osna-modal-close').on('click', function() {
            closeCodeModal();
        });

        // Code input formatting
        $('#code').on('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        // Settings form
        $('#referral-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }

    function loadCodesTable(page = 1) {
        const search = $('#codes-search').val();
        
        $.ajax({
            url: osnaReferralAdmin.restUrl + 'referral/codes',
            method: 'GET',
            headers: {
                'X-WP-Nonce': osnaReferralAdmin.nonce
            },
            data: {
                page: page,
                per_page: 20,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderCodesTable(response.data);
                    renderPagination(response.pagination, 'codes');
                }
            },
            error: function() {
                alert('Failed to load referral codes');
            }
        });
    }

    function loadUsageTable(page = 1) {
        $.ajax({
            url: osnaReferralAdmin.restUrl + 'referral/usage',
            method: 'GET',
            headers: {
                'X-WP-Nonce': osnaReferralAdmin.nonce
            },
            data: {
                page: page,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    renderUsageTable(response.data);
                    renderPagination(response.pagination, 'usage');
                }
            },
            error: function() {
                alert('Failed to load usage data');
            }
        });
    }

    function loadRewardsTable(page = 1) {
        // This would load rewards data - placeholder for now
        $('#rewards-table-body').html('<tr><td colspan="7">Rewards functionality coming soon...</td></tr>');
    }

    function renderCodesTable(codes) {
        let html = '';
        
        codes.forEach(function(code) {
            const discountText = code.discount_type === 'percentage' 
                ? code.discount_value + '%' 
                : '$' + parseFloat(code.discount_value).toFixed(2);
            
            const rewardText = code.reward_type === 'percentage' 
                ? code.reward_value + '%' 
                : '$' + parseFloat(code.reward_value).toFixed(2);

            const usageText = code.usage_limit 
                ? code.usage_count + '/' + code.usage_limit 
                : code.usage_count;

            const statusBadge = code.status === 'active' 
                ? '<span class="status-active">Active</span>' 
                : '<span class="status-inactive">Inactive</span>';

            html += `
                <tr>
                    <td><strong>${code.code}</strong></td>
                    <td>${code.user_name || 'Unknown'}<br><small>${code.user_email || ''}</small></td>
                    <td>${discountText}</td>
                    <td>${rewardText}</td>
                    <td>${usageText}</td>
                    <td>${statusBadge}</td>
                    <td>${formatDate(code.created_at)}</td>
                    <td>
                        <button class="button edit-code" data-id="${code.id}">Edit</button>
                        <button class="button delete-code" data-id="${code.id}">Delete</button>
                    </td>
                </tr>
            `;
        });

        $('#codes-table-body').html(html);

        // Attach event handlers for action buttons
        $('.edit-code').on('click', function() {
            const id = $(this).data('id');
            editCode(id);
        });

        $('.delete-code').on('click', function() {
            const id = $(this).data('id');
            if (confirm('Are you sure you want to delete this referral code?')) {
                deleteCode(id);
            }
        });
    }

    function renderUsageTable(usage) {
        let html = '';
        
        usage.forEach(function(item) {
            html += `
                <tr>
                    <td><strong>${item.code}</strong></td>
                    <td>${item.referrer_name}<br><small>${item.referrer_email}</small></td>
                    <td>${item.referee_name || 'Guest'}<br><small>${item.referee_email || ''}</small></td>
                    <td>#${item.order_id}</td>
                    <td>$${parseFloat(item.discount_amount).toFixed(2)}</td>
                    <td>$${parseFloat(item.reward_amount).toFixed(2)}</td>
                    <td>${formatDate(item.created_at)}</td>
                </tr>
            `;
        });

        $('#usage-table-body').html(html);
    }

    function renderPagination(pagination, type) {
        let html = '';
        
        if (pagination.total_pages > 1) {
            html += '<div class="tablenav"><div class="tablenav-pages">';
            html += `<span class="pagination-links">`;
            
            // Previous button
            if (pagination.page > 1) {
                html += `<a class="button pagination-btn" data-page="${pagination.page - 1}" data-type="${type}">‹</a>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.page) {
                    html += `<span class="current-page">${i}</span>`;
                } else {
                    html += `<a class="button pagination-btn" data-page="${i}" data-type="${type}">${i}</a>`;
                }
            }
            
            // Next button
            if (pagination.page < pagination.total_pages) {
                html += `<a class="button pagination-btn" data-page="${pagination.page + 1}" data-type="${type}">›</a>`;
            }
            
            html += '</span></div></div>';
        }

        $('#' + type + '-pagination').html(html);

        // Attach pagination event handlers
        $('.pagination-btn').on('click', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            const type = $(this).data('type');
            
            if (type === 'codes') {
                loadCodesTable(page);
            } else if (type === 'usage') {
                loadUsageTable(page);
            }
        });
    }

    function openCodeModal(codeData = null) {
        if (codeData) {
            $('#modal-title').text('Edit Referral Code');
            $('#code-id').val(codeData.id);
            $('#code').val(codeData.code).prop('disabled', true);
            $('#user_id').val(codeData.user_id);
            $('#discount_type').val(codeData.discount_type);
            $('#discount_value').val(codeData.discount_value);
            $('#reward_type').val(codeData.reward_type);
            $('#reward_value').val(codeData.reward_value);
            $('#usage_limit').val(codeData.usage_limit);
            $('#status').val(codeData.status);
        } else {
            $('#modal-title').text('Create Referral Code');
            $('#code-form')[0].reset();
            $('#code-id').val('');
            $('#code').prop('disabled', false);
        }
        
        $('#code-modal').show();
    }

    function closeCodeModal() {
        $('#code-modal').hide();
    }

    function saveCode() {
        const formData = $('#code-form').serializeArray();
        const data = {};
        
        formData.forEach(function(item) {
            if (item.value !== '') {
                data[item.name] = item.value;
            }
        });

        const isEdit = data.id && data.id !== '';
        const url = isEdit 
            ? osnaReferralAdmin.restUrl + 'referral/codes/' + data.id
            : osnaReferralAdmin.restUrl + 'referral/create';
        
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            headers: {
                'X-WP-Nonce': osnaReferralAdmin.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(data),
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    closeCodeModal();
                    loadCodesTable();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to save referral code');
            }
        });
    }

    function editCode(id) {
        // Get code data and open modal
        // For now, just reload the table to get fresh data
        loadCodesTable();
        // In a real implementation, you'd fetch the specific code data
        alert('Edit functionality - fetch code data for ID: ' + id);
    }

    function deleteCode(id) {
        $.ajax({
            url: osnaReferralAdmin.restUrl + 'referral/codes/' + id,
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': osnaReferralAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    loadCodesTable();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to delete referral code');
            }
        });
    }

    function saveSettings() {
        const formData = $('#referral-settings-form').serializeArray();
        const data = {};
        
        formData.forEach(function(item) {
            data[item.name] = item.value;
        });

        // Save settings via AJAX (placeholder)
        alert('Settings saved: ' + JSON.stringify(data));
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    // Make functions global for external access
    window.closeCodeModal = closeCodeModal;
});