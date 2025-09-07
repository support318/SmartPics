/**
 * SmartPics Admin JavaScript
 */

(function($) {
    'use strict';
    
    var SmartPics_Admin = {
        
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
            $('.smartpics-field-row input[type="range"]').on('input', function() {
                $(this).next('.range-value').text($(this).val());
            });
        },
        
        initConnectionTests: function() {
            $('.smartpics-test-connection').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $status = $button.siblings('.smartpics-connection-status');
                var provider = $button.data('provider');
                var $apiKeyField = $button.closest('.smartpics-field-row').find('input[type="password"]');
                var apiKey = $apiKeyField.val();
                
                if (!apiKey || apiKey.indexOf('*') !== -1) {
                    $status.removeClass('success error').addClass('error').text('Please enter API key first');
                    return;
                }
                
                $button.prop('disabled', true).text('Testing...');
                $status.removeClass('success error').text('');
                
                wp.ajax.post('smartpics_test_connection', {
                    nonce: smartpics_admin.nonce,
                    provider: provider,
                    api_key: apiKey
                }).done(function(response) {
                    $status.addClass('success').text(smartpics_admin.strings.connection_test_success);
                }).fail(function(response) {
                    $status.addClass('error').text(response.message || smartpics_admin.strings.connection_test_failed);
                }).always(function() {
                    $button.prop('disabled', false).text('Test Connection');
                });
            });
        },
        
        initBulkProcessing: function() {
            var $bulkButton = $('#smartpics-start-bulk');
            var $progressContainer = $('.smartpics-progress-container');
            var $progressBar = $('.smartpics-progress-fill');
            var $progressText = $('.smartpics-progress-text');
            var bulkJobId = null;
            var progressInterval = null;
            
            $bulkButton.on('click', function(e) {
                e.preventDefault();
                
                if (!confirm(smartpics_admin.strings.confirm_bulk)) {
                    return;
                }
                
                $bulkButton.prop('disabled', true).text('Starting...');
                $progressContainer.show();
                
                wp.ajax.post('smartpics_bulk_process', {
                    nonce: smartpics_admin.nonce
                }).done(function(response) {
                    bulkJobId = response.job_id;
                    $bulkButton.text('Processing...');
                    
                    // Start progress monitoring
                    progressInterval = setInterval(function() {
                        SmartPics_Admin.checkBulkProgress(bulkJobId);
                    }, 2000);
                    
                }).fail(function(response) {
                    SmartPics_Admin.showNotification('error', response.message || 'Failed to start bulk processing');
                    $bulkButton.prop('disabled', false).text('Start Bulk Processing');
                    $progressContainer.hide();
                });
            });
            
            // Cancel bulk processing
            $('#smartpics-cancel-bulk').on('click', function(e) {
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
            wp.ajax.post('smartpics_get_progress', {
                nonce: smartpics_admin.nonce,
                job_id: jobId
            }).done(function(response) {
                var progress = response;
                var percentage = (progress.processed / progress.total) * 100;
                
                $('.smartpics-progress-fill').css('width', percentage + '%');
                $('.smartpics-progress-text').text(
                    progress.processed + ' of ' + progress.total + ' images processed'
                );
                
                if (progress.status === 'completed' || progress.processed >= progress.total) {
                    clearInterval(progressInterval);
                    $('#smartpics-start-bulk').prop('disabled', false).text('Start Bulk Processing');
                    
                    SmartPics_Admin.showNotification('success', 
                        'Bulk processing completed! ' + progress.successful + ' successful, ' + 
                        progress.failed + ' failed.'
                    );
                    
                    // Refresh dashboard data
                    setTimeout(function() {
                        SmartPics_Admin.loadDashboardData();
                    }, 1000);
                }
            }).fail(function(response) {
                clearInterval(progressInterval);
                SmartPics_Admin.showNotification('error', 'Failed to get progress update');
            });
        },
        
        initCacheManagement: function() {
            $('.smartpics-clear-cache').on('click', function(e) {
                e.preventDefault();
                
                var cacheType = $(this).data('type') || 'all';
                
                if (!confirm(smartpics_admin.strings.confirm_cache_clear)) {
                    return;
                }
                
                var $button = $(this);
                $button.prop('disabled', true);
                
                wp.ajax.post('smartpics_clear_cache', {
                    nonce: smartpics_admin.nonce,
                    type: cacheType
                }).done(function(response) {
                    SmartPics_Admin.showNotification('success', 'Cache cleared successfully');
                    // Refresh cache stats
                    location.reload();
                }).fail(function(response) {
                    SmartPics_Admin.showNotification('error', response.message || 'Failed to clear cache');
                }).always(function() {
                    $button.prop('disabled', false);
                });
            });
        },
        
        loadDashboardData: function() {
            if (!$('.smartpics-dashboard').length) {
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
            wp.ajax.post('smartpics_get_provider_stats', {
                nonce: smartpics_admin.nonce
            }).done(function(response) {
                SmartPics_Admin.renderProviderStats(response);
            }).fail(function(response) {
                console.error('Failed to load provider stats:', response);
            });
        },
        
        renderProviderStats: function(stats) {
            var $container = $('.smartpics-provider-grid');
            
            if (!$container.length) {
                return;
            }
            
            $container.empty();
            
            $.each(stats, function(provider, data) {
                var $card = $('<div class="smartpics-provider-card">');
                
                if (data.is_primary) {
                    $card.addClass('primary');
                } else if (data.is_active) {
                    $card.addClass('active');
                }
                
                var successRate = data.total_requests > 0 
                    ? Math.round((data.successful_requests / data.total_requests) * 100)
                    : 0;
                
                $card.html(`
                    <div class="smartpics-provider-header">
                        <div class="smartpics-provider-name">${data.name}</div>
                        <div class="smartpics-provider-status ${data.is_active ? 'success' : 'error'}">
                            ${data.is_active ? 'Active' : 'Inactive'}
                        </div>
                    </div>
                    <div class="smartpics-provider-stats">
                        <div class="smartpics-provider-stat">
                            <span class="smartpics-provider-stat-value">${data.total_requests}</span>
                            <span class="smartpics-provider-stat-label">Requests</span>
                        </div>
                        <div class="smartpics-provider-stat">
                            <span class="smartpics-provider-stat-value">${successRate}%</span>
                            <span class="smartpics-provider-stat-label">Success Rate</span>
                        </div>
                        <div class="smartpics-provider-stat">
                            <span class="smartpics-provider-stat-value">$${data.estimated_cost.toFixed(2)}</span>
                            <span class="smartpics-provider-stat-label">Cost</span>
                        </div>
                        <div class="smartpics-provider-stat">
                            <span class="smartpics-provider-stat-value">${data.avg_response_time}ms</span>
                            <span class="smartpics-provider-stat-label">Avg Response</span>
                        </div>
                    </div>
                `);
                
                $container.append($card);
            });
        },
        
        loadCacheStats: function() {
            wp.ajax.post('smartpics_get_cache_stats', {
                nonce: smartpics_admin.nonce
            }).done(function(response) {
                SmartPics_Admin.updateCacheStats(response);
            }).fail(function(response) {
                console.error('Failed to load cache stats:', response);
            });
        },
        
        updateCacheStats: function(stats) {
            $('.smartpics-stat-card[data-stat="cached_images"] .smartpics-stat-value').text(stats.total_cached || 0);
            $('.smartpics-stat-card[data-stat="cache_hits"] .smartpics-stat-value').text(stats.total_cache_hits || 0);
            $('.smartpics-stat-card[data-stat="cache_hit_rate"] .smartpics-stat-value').text(
                stats.cache_hit_rate ? stats.cache_hit_rate.toFixed(1) + '%' : '0%'
            );
        },
        
        loadRecentActivity: function() {
            wp.ajax.post('smartpics_get_recent_activity', {
                nonce: smartpics_admin.nonce
            }).done(function(response) {
                SmartPics_Admin.renderRecentActivity(response);
            }).fail(function(response) {
                console.error('Failed to load recent activity:', response);
            });
        },
        
        renderRecentActivity: function(activities) {
            var $container = $('.smartpics-recent-activity tbody');
            
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
                        <td><span class="smartpics-status ${statusClass}">${activity.status}</span></td>
                        <td>${activity.processed_at}</td>
                    </tr>
                `);
                
                $container.append($row);
            });
        },
        
        showNotification: function(type, message) {
            var $notification = $(`
                <div class="smartpics-notification ${type}">
                    <p>${message}</p>
                </div>
            `);
            
            $('.smartpics-admin-page').prepend($notification);
            
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
        SmartPics_Admin.init();
    });
    
    // Export for global access
    window.SmartPics_Admin = SmartPics_Admin;
    
})(jQuery);