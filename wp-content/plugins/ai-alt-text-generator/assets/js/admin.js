/**
 * AI Alt Text Generator Admin JavaScript
 */

(function($) {
    'use strict';
    
    var AIALT_Admin = {
        
        init: function() {
            this.bindEvents();
            this.initRangeSliders();
            this.initConnectionTests();
            this.initBulkProcessing();
            this.initCacheManagement();
            this.loadDashboardData();
        },
        
        bindEvents: function() {
            $(document).ready(this.init.bind(this));
        },
        
        initRangeSliders: function() {
            $('.aialt-field-row input[type="range"]').on('input', function() {
                $(this).next('.range-value').text($(this).val());
            });
        },
        
        initConnectionTests: function() {
            $('.aialt-test-connection').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $status = $button.siblings('.aialt-connection-status');
                var provider = $button.data('provider');
                var $apiKeyField = $button.closest('.aialt-field-row').find('input[type="password"]');
                var apiKey = $apiKeyField.val();
                
                if (!apiKey || apiKey.indexOf('*') !== -1) {
                    $status.removeClass('success error').addClass('error').text('Please enter API key first');
                    return;
                }
                
                $button.prop('disabled', true).text('Testing...');
                $status.removeClass('success error').text('');
                
                wp.ajax.post('aialt_test_connection', {
                    nonce: aialt_admin.nonce,
                    provider: provider,
                    api_key: apiKey
                }).done(function(response) {
                    $status.addClass('success').text(aialt_admin.strings.connection_test_success);
                }).fail(function(response) {
                    $status.addClass('error').text(response.message || aialt_admin.strings.connection_test_failed);
                }).always(function() {
                    $button.prop('disabled', false).text('Test Connection');
                });
            });
        },
        
        initBulkProcessing: function() {
            var $bulkButton = $('#aialt-start-bulk');
            var $progressContainer = $('.aialt-progress-container');
            var $progressBar = $('.aialt-progress-fill');
            var $progressText = $('.aialt-progress-text');
            var bulkJobId = null;
            var progressInterval = null;
            
            $bulkButton.on('click', function(e) {
                e.preventDefault();
                
                if (!confirm(aialt_admin.strings.confirm_bulk)) {
                    return;
                }
                
                $bulkButton.prop('disabled', true).text('Starting...');
                $progressContainer.show();
                
                wp.ajax.post('aialt_bulk_process', {
                    nonce: aialt_admin.nonce
                }).done(function(response) {
                    bulkJobId = response.job_id;
                    $bulkButton.text('Processing...');
                    
                    // Start progress monitoring
                    progressInterval = setInterval(function() {
                        AIALT_Admin.checkBulkProgress(bulkJobId);
                    }, 2000);
                    
                }).fail(function(response) {
                    AIALT_Admin.showNotification('error', response.message || 'Failed to start bulk processing');
                    $bulkButton.prop('disabled', false).text('Start Bulk Processing');
                    $progressContainer.hide();
                });
            });
            
            // Cancel bulk processing
            $('#aialt-cancel-bulk').on('click', function(e) {
                e.preventDefault();
                
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
                
                bulkJobId = null;
                $bulkButton.prop('disabled', false).text('Start Bulk Processing');
                $progressContainer.hide();
                $progressBar.css('width', '0%');
                $progressText.text('');
            });
        },
        
        checkBulkProgress: function(jobId) {
            wp.ajax.post('aialt_get_progress', {
                nonce: aialt_admin.nonce,
                job_id: jobId
            }).done(function(response) {
                var progress = response;
                var percentage = (progress.processed / progress.total) * 100;
                
                $('.aialt-progress-fill').css('width', percentage + '%');
                $('.aialt-progress-text').text(
                    progress.processed + ' of ' + progress.total + ' images processed'
                );
                
                if (progress.status === 'completed' || progress.processed >= progress.total) {
                    clearInterval(progressInterval);
                    $('#aialt-start-bulk').prop('disabled', false).text('Start Bulk Processing');
                    
                    AIALT_Admin.showNotification('success', 
                        'Bulk processing completed! ' + progress.successful + ' successful, ' + 
                        progress.failed + ' failed.'
                    );
                    
                    // Refresh dashboard data
                    setTimeout(function() {
                        AIALT_Admin.loadDashboardData();
                    }, 1000);
                }
            }).fail(function(response) {
                clearInterval(progressInterval);
                AIALT_Admin.showNotification('error', 'Failed to get progress update');
            });
        },
        
        initCacheManagement: function() {
            $('.aialt-clear-cache').on('click', function(e) {
                e.preventDefault();
                
                var cacheType = $(this).data('type') || 'all';
                
                if (!confirm(aialt_admin.strings.confirm_cache_clear)) {
                    return;
                }
                
                var $button = $(this);
                $button.prop('disabled', true);
                
                wp.ajax.post('aialt_clear_cache', {
                    nonce: aialt_admin.nonce,
                    type: cacheType
                }).done(function(response) {
                    AIALT_Admin.showNotification('success', 'Cache cleared successfully');
                    // Refresh cache stats
                    location.reload();
                }).fail(function(response) {
                    AIALT_Admin.showNotification('error', response.message || 'Failed to clear cache');
                }).always(function() {
                    $button.prop('disabled', false);
                });
            });
        },
        
        loadDashboardData: function() {
            if (!$('.aialt-dashboard').length) {
                return;
            }
            
            // Load provider statistics
            this.loadProviderStats();
            
            // Load cache statistics
            this.loadCacheStats();
            
            // Load recent activity
            this.loadRecentActivity();
        },
        
        loadProviderStats: function() {
            wp.ajax.post('aialt_get_provider_stats', {
                nonce: aialt_admin.nonce
            }).done(function(response) {
                AIALT_Admin.renderProviderStats(response);
            }).fail(function(response) {
                console.error('Failed to load provider stats:', response);
            });
        },
        
        renderProviderStats: function(stats) {
            var $container = $('.aialt-provider-grid');
            
            if (!$container.length) {
                return;
            }
            
            $container.empty();
            
            $.each(stats, function(provider, data) {
                var $card = $('<div class="aialt-provider-card">');
                
                if (data.is_primary) {
                    $card.addClass('primary');
                } else if (data.is_active) {
                    $card.addClass('active');
                }
                
                var successRate = data.total_requests > 0 
                    ? Math.round((data.successful_requests / data.total_requests) * 100)
                    : 0;
                
                $card.html(`
                    <div class="aialt-provider-header">
                        <div class="aialt-provider-name">${data.name}</div>
                        <div class="aialt-provider-status ${data.is_active ? 'success' : 'error'}">
                            ${data.is_active ? 'Active' : 'Inactive'}
                        </div>
                    </div>
                    <div class="aialt-provider-stats">
                        <div class="aialt-provider-stat">
                            <span class="aialt-provider-stat-value">${data.total_requests}</span>
                            <span class="aialt-provider-stat-label">Requests</span>
                        </div>
                        <div class="aialt-provider-stat">
                            <span class="aialt-provider-stat-value">${successRate}%</span>
                            <span class="aialt-provider-stat-label">Success Rate</span>
                        </div>
                        <div class="aialt-provider-stat">
                            <span class="aialt-provider-stat-value">$${data.estimated_cost.toFixed(2)}</span>
                            <span class="aialt-provider-stat-label">Cost</span>
                        </div>
                        <div class="aialt-provider-stat">
                            <span class="aialt-provider-stat-value">${data.avg_response_time}ms</span>
                            <span class="aialt-provider-stat-label">Avg Response</span>
                        </div>
                    </div>
                `);
                
                $container.append($card);
            });
        },
        
        loadCacheStats: function() {
            wp.ajax.post('aialt_get_cache_stats', {
                nonce: aialt_admin.nonce
            }).done(function(response) {
                AIALT_Admin.updateCacheStats(response);
            }).fail(function(response) {
                console.error('Failed to load cache stats:', response);
            });
        },
        
        updateCacheStats: function(stats) {
            $('.aialt-stat-card[data-stat="cached_images"] .aialt-stat-value').text(stats.total_cached || 0);
            $('.aialt-stat-card[data-stat="cache_hits"] .aialt-stat-value').text(stats.total_cache_hits || 0);
            $('.aialt-stat-card[data-stat="cache_hit_rate"] .aialt-stat-value').text(
                stats.cache_hit_rate ? stats.cache_hit_rate.toFixed(1) + '%' : '0%'
            );
        },
        
        loadRecentActivity: function() {
            wp.ajax.post('aialt_get_recent_activity', {
                nonce: aialt_admin.nonce
            }).done(function(response) {
                AIALT_Admin.renderRecentActivity(response);
            }).fail(function(response) {
                console.error('Failed to load recent activity:', response);
            });
        },
        
        renderRecentActivity: function(activities) {
            var $container = $('.aialt-recent-activity tbody');
            
            if (!$container.length) {
                return;
            }
            
            $container.empty();
            
            if (!activities || activities.length === 0) {
                $container.html('<tr><td colspan="4">No recent activity</td></tr>');
                return;
            }
            
            $.each(activities, function(index, activity) {
                var statusClass = activity.status === 'success' ? 'success' : 'error';
                var $row = $(`
                    <tr>
                        <td>${activity.image_name}</td>
                        <td>${activity.provider}</td>
                        <td><span class="aialt-status ${statusClass}">${activity.status}</span></td>
                        <td>${activity.processed_at}</td>
                    </tr>
                `);
                
                $container.append($row);
            });
        },
        
        showNotification: function(type, message) {
            var $notification = $(`
                <div class="aialt-notification ${type}">
                    <p>${message}</p>
                </div>
            `);
            
            $('.aialt-admin-page').prepend($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Chart rendering for analytics
        renderChart: function(canvasId, data, options) {
            if (typeof Chart === 'undefined') {
                return;
            }
            
            var ctx = document.getElementById(canvasId);
            if (!ctx) {
                return;
            }
            
            new Chart(ctx, {
                type: options.type || 'line',
                data: data,
                options: options
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        AIALT_Admin.init();
    });
    
    // Export for global access
    window.AIALT_Admin = AIALT_Admin;
    
})(jQuery);