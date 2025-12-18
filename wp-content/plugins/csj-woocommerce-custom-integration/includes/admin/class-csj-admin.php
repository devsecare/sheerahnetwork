<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_ajax_csj_search_products', array($this, 'ajax_search_products'));
    }

    public function ajax_search_products()
    {
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $search = sanitize_text_field($_GET['term']);
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 10,
            's' => $search,
            'fields' => 'ids'
        );

        $query = new WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $product = wc_get_product($post_id);
                if ($product) {
                    $results[] = [
                        'id' => $product->get_sku(), // Use SKU as ID
                        'text' => $product->get_name() . ' (' . $product->get_sku() . ')'
                    ];
                }
            }
        }

        // Also search by SKU directly
        $sku_product_id = wc_get_product_id_by_sku($search);
        if ($sku_product_id && !in_array($sku_product_id, $query->posts)) {
            $product = wc_get_product($sku_product_id);
            if ($product) {
                array_unshift($results, [
                    'id' => $product->get_sku(),
                    'text' => $product->get_name() . ' (' . $product->get_sku() . ')'
                ]);
            }
        }

        wp_send_json($results);
    }

    public function enqueue_styles($hook)
    {
        if ('toplevel_page_csj_custom_settings' !== $hook) {
            return;
        }
        // Enqueue Tailwind via CDN for admin page
        wp_enqueue_script('csj-tailwind', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);

        // Add custom JS
        wp_add_inline_script('csj-tailwind', "
            tailwind.config = {
                corePlugins: {
                    preflight: false,
                },
                prefix: 'tw-',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                        },
                        colors: {
                            amber: {
                                50: '#fffbeb',
                                100: '#fef3c7',
                                200: '#fde68a',
                                300: '#fcd34d',
                                400: '#fbbf24',
                                500: '#f59e0b',
                                600: '#d97706',
                                700: '#b45309',
                                800: '#92400e',
                                900: '#78350f',
                                950: '#451a03',
                            },
                        }
                    }
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                // Tab Switching with Fade Effect
                const tabs = document.querySelectorAll('.csj-tab-link');
                const contents = document.querySelectorAll('.csj-tab-content');
                
                function switchTab(targetId) {
                    // Update Tabs
                    tabs.forEach(t => {
                        const isCurrent = t.dataset.target === targetId;
                        if(isCurrent) {
                            t.classList.add('tw-bg-amber-50', 'tw-text-amber-700');
                            t.classList.remove('tw-text-gray-600', 'hover:tw-bg-gray-50', 'hover:tw-text-gray-900');
                            const icon = t.querySelector('.dashicons');
                            if(icon) {
                                icon.classList.remove('tw-text-gray-400');
                                icon.classList.add('tw-text-amber-600');
                            }
                        } else {
                            t.classList.remove('tw-bg-amber-50', 'tw-text-amber-700');
                            t.classList.add('tw-text-gray-600', 'hover:tw-bg-gray-50', 'hover:tw-text-gray-900');
                            const icon = t.querySelector('.dashicons');
                            if(icon) {
                                icon.classList.add('tw-text-gray-400');
                                icon.classList.remove('tw-text-amber-600');
                            }
                        }
                    });

                    // Update Content
                    contents.forEach(c => {
                        if(c.id === targetId) {
                            c.classList.remove('tw-hidden');
                            // Small delay for fade in if needed, but simple toggle is faster
                            c.classList.add('tw-animate-in', 'tw-fade-in', 'tw-slide-in-from-bottom-2', 'tw-duration-300');
                        } else {
                            c.classList.add('tw-hidden');
                            c.classList.remove('tw-animate-in', 'tw-fade-in', 'tw-slide-in-from-bottom-2');
                        }
                    });

                    // Save state
                    localStorage.setItem('csj_active_tab', targetId);
                }

                // Initialize
                const savedTab = localStorage.getItem('csj_active_tab');
                const initialTab = savedTab && document.getElementById(savedTab) ? savedTab : 'tab-general';
                switchTab(initialTab);

                tabs.forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        e.preventDefault();
                        switchTab(tab.dataset.target);
                    });
                });

                // SKU Search Logic
                const searchInput = document.getElementById('csj_sku_search');
                const resultsContainer = document.getElementById('csj_sku_results');
                const chipsContainer = document.getElementById('csj_sku_chips');
                const hiddenInput = document.getElementById('csj_shipping_dpd_skus');
                
                if(searchInput && hiddenInput) {
                    let selectedSkus = hiddenInput.value ? hiddenInput.value.split(',').filter(Boolean) : [];
                    
                    function renderChips() {
                        chipsContainer.innerHTML = '';
                        selectedSkus.forEach(sku => {
                            const chip = document.createElement('div');
                            chip.className = 'tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium tw-bg-amber-50 tw-text-amber-700 tw-border tw-border-amber-100 tw-mr-2 tw-mb-2 tw-transition-all hover:tw-bg-amber-100';
                            
                            chip.innerHTML = `
                                \${sku}
                                <button type='button' class='tw-ml-2 tw-inline-flex tw-items-center tw-justify-center tw-h-4 tw-w-4 tw-rounded-full tw-text-amber-400 hover:tw-text-amber-600 focus:tw-outline-none'>
                                    <span class='tw-sr-only'>Remove</span>
                                    <svg class='tw-h-3 tw-w-3' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L10 8.586 5.707 4.293a1 1 0 010-1.414z' clip-rule='evenodd'/></svg>
                                </button>
                            `;
                            
                            chip.querySelector('button').addEventListener('click', () => {
                                selectedSkus = selectedSkus.filter(s => s !== sku);
                                updateHiddenInput();
                                renderChips();
                            });
                            chipsContainer.appendChild(chip);
                        });
                    }
                    
                    function updateHiddenInput() {
                        hiddenInput.value = selectedSkus.join(',');
                    }

                    renderChips();

                    let debounceTimer;
                    searchInput.addEventListener('input', (e) => {
                        clearTimeout(debounceTimer);
                        const term = e.target.value;
                        if(term.length < 2) {
                            resultsContainer.innerHTML = '';
                            resultsContainer.classList.add('tw-hidden');
                            return;
                        }
                        
                        debounceTimer = setTimeout(() => {
                            fetch(ajaxurl + '?action=csj_search_products&term=' + term)
                                .then(res => res.json())
                                .then(data => {
                                    resultsContainer.innerHTML = '';
                                    if(data.length > 0) {
                                        resultsContainer.classList.remove('tw-hidden');
                                        data.forEach(item => {
                                            const div = document.createElement('div');
                                            div.className = 'tw-px-4 tw-py-3 tw-cursor-pointer hover:tw-bg-amber-50 tw-text-sm tw-text-gray-700 tw-transition-colors';
                                            div.textContent = item.text;
                                            div.addEventListener('click', () => {
                                                if(!selectedSkus.includes(item.id)) {
                                                    selectedSkus.push(item.id);
                                                    updateHiddenInput();
                                                    renderChips();
                                                }
                                                searchInput.value = '';
                                                resultsContainer.classList.add('tw-hidden');
                                            });
                                            resultsContainer.appendChild(div);
                                        });
                                    } else {
                                        resultsContainer.classList.add('tw-hidden');
                                    }
                                });
                        }, 300);
                    });
                    
                    document.addEventListener('click', (e) => {
                        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                            resultsContainer.classList.add('tw-hidden');
                        }
                    });
                }
            });
        ");
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('CSJ Settings', 'csj-custom'),
            __('CSJ Settings', 'csj-custom'),
            'manage_options',
            'csj_custom_settings',
            array($this, 'render_settings_page'),
            'dashicons-pets',
            55
        );
    }

    public function register_settings()
    {
        // General Section
        register_setting('csj_custom_settings', 'csj_maps_api_key');

        // Loyalty Section
        register_setting('csj_custom_settings', 'csj_loyalty_points_ratio');
        register_setting('csj_custom_settings', 'csj_loyalty_redemption_rate');

        // Affiliate Section
        register_setting('csj_custom_settings', 'csj_affiliate_commission_rate');

        // Shipping Section
        register_setting('csj_custom_settings', 'csj_shipping_split_weight');
        register_setting('csj_custom_settings', 'csj_shipping_dpd_skus');
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap tw-font-sans tw-bg-gray-50/50 tw-min-h-screen -tw-ml-5 -tw-mt-2 tw-p-6 sm:tw-p-10">
            <h1 class="wp-heading-inline" style="display:none;"></h1>

            <div class="tw-max-w-7xl tw-mx-auto">
                <form method="post" action="options.php">
                    <?php settings_fields('csj_custom_settings'); ?>

                    <!-- Modern Header -->
                    <div
                        class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-mb-10">
                        <div>
                            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-tracking-tight">
                                Settings
                            </h1>
                            <p class="tw-mt-2 tw-text-base tw-text-gray-500">
                                Configure your CSJ integration modules and global preferences.
                            </p>
                        </div>
                        <div class="tw-mt-4 sm:tw-mt-0">
                            <button type="submit"
                                class="tw-inline-flex tw-items-center tw-justify-center tw-px-8 tw-py-2.5 tw-border tw-border-transparent tw-text-sm tw-font-semibold tw-rounded-full tw-shadow-sm tw-text-white tw-bg-amber-600 hover:tw-bg-amber-700 hover:tw-shadow-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-amber-500 tw-transition-all tw-duration-200">
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-8">
                        <!-- Navigation Sidebar -->
                        <aside class="lg:tw-col-span-3">
                            <nav class="tw-space-y-1">
                                <?php
                                $tabs = [
                                    'general' => ['label' => 'General', 'icon' => 'dashicons-admin-settings', 'desc' => 'API Keys & Core Setup'],
                                    'loyalty' => ['label' => 'Loyalty Program', 'icon' => 'dashicons-heart', 'desc' => 'Points & Rewards'],
                                    'affiliates' => ['label' => 'Affiliates', 'icon' => 'dashicons-groups', 'desc' => 'Commission Rates'],
                                    'shipping' => ['label' => 'Shipping Rules', 'icon' => 'dashicons-truck', 'desc' => 'Order Splitting'],
                                ];

                                foreach ($tabs as $id => $tab):
                                    ?>
                                    <a href="#"
                                        class="csj-tab-link tw-group tw-flex tw-items-center tw-px-4 tw-py-3.5 tw-text-sm tw-font-medium tw-rounded-xl tw-transition-all tw-duration-200 tw-mb-1"
                                        data-target="tab-<?php echo $id; ?>">
                                        <div
                                            class="tw-flex tw-items-center tw-justify-center tw-w-8 tw-h-8 tw-rounded-lg tw-bg-white/50 group-hover:tw-bg-white tw-mr-3 tw-transition-colors">
                                            <span
                                                class="dashicons <?php echo $tab['icon']; ?> tw-text-lg tw-transition-colors"></span>
                                        </div>
                                        <span class="tw-text-[15px]"><?php echo $tab['label']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>

                            <!-- Helpful Card (Optional) -->
                            <div class="tw-mt-8 tw-p-4 tw-bg-amber-50 tw-rounded-2xl tw-border tw-border-amber-100">
                                <h4 class="tw-text-sm tw-font-semibold tw-text-amber-800 tw-mb-1">Need help?</h4>
                                <p class="tw-text-xs tw-text-amber-700 tw-leading-relaxed">Contact support for assistance with
                                    these configurations.</p>
                            </div>
                        </aside>

                        <!-- Content Area -->
                        <div class="lg:tw-col-span-9">

                            <!-- Wrapper for all tabs to maintain height/structure -->
                            <div
                                class="tw-bg-white tw-shadow-xl tw-shadow-gray-200/40 tw-ring-1 tw-ring-gray-100 tw-rounded-3xl tw-p-8 sm:tw-p-10">

                                <!-- General Tab -->
                                <section id="tab-general" class="csj-tab-content">
                                    <div class="tw-mb-8">
                                        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">General Settings</h2>
                                        <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Essential settings for external
                                            integrations.</p>
                                    </div>

                                    <div class="tw-space-y-6">
                                        <div class="tw-group">
                                            <label for="csj_maps_api_key"
                                                class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Google
                                                Maps API Key</label>
                                            <div class="tw-relative">
                                                <input type="text" name="csj_maps_api_key" id="csj_maps_api_key"
                                                    value="<?php echo esc_attr(get_option('csj_maps_api_key')); ?>"
                                                    class="tw-block tw-w-full tw-rounded-xl tw-border-0 tw-py-3 tw-px-4 tw-text-gray-900 tw-shadow-sm tw-ring-1 tw-ring-inset tw-ring-gray-200 placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-amber-500 sm:tw-text-sm sm:tw-leading-6 tw-bg-gray-50 focus:tw-bg-white tw-transition-all"
                                                    placeholder="AIwaSy...">
                                                <div
                                                    class="tw-absolute tw-inset-y-0 tw-right-0 tw-flex tw-items-center tw-pr-3">
                                                    <span class="dashicons dashicons-location tw-text-gray-400"></span>
                                                </div>
                                            </div>
                                            <p class="tw-mt-2 tw-text-xs tw-text-gray-500">Required for the Stockist Locator
                                                map. <a
                                                    href="https://developers.google.com/maps/documentation/javascript/get-api-key"
                                                    target="_blank"
                                                    class="tw-text-amber-600 hover:tw-text-amber-700 tw-font-medium">Get an API
                                                    Key &rarr;</a></p>
                                        </div>
                                    </div>
                                </section>

                                <!-- Loyalty Tab -->
                                <section id="tab-loyalty" class="csj-tab-content tw-hidden">
                                    <div class="tw-mb-8">
                                        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">PawPoints Loyalty</h2>
                                        <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Configure how customers earn and redeem
                                            points.</p>
                                    </div>

                                    <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 tw-gap-8">
                                        <div>
                                            <label for="csj_loyalty_points_ratio"
                                                class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Points
                                                Earning Ratio</label>
                                            <div class="tw-relative tw-rounded-xl tw-shadow-sm">
                                                <input type="number" step="0.1" name="csj_loyalty_points_ratio"
                                                    id="csj_loyalty_points_ratio"
                                                    value="<?php echo esc_attr(get_option('csj_loyalty_points_ratio', 1)); ?>"
                                                    class="tw-block tw-w-full tw-rounded-xl tw-border-0 tw-py-3 tw-pl-4 tw-pr-24 tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-200 placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-amber-500 sm:tw-text-sm sm:tw-leading-6 tw-bg-gray-50 focus:tw-bg-white tw-transition-all">
                                                <div
                                                    class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center tw-pointer-events-none">
                                                    <span class="tw-text-gray-500 sm:tw-text-sm tw-font-medium">points /
                                                        £1</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="csj_loyalty_redemption_rate"
                                                class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Redemption
                                                Value</label>
                                            <div class="tw-relative tw-rounded-xl tw-shadow-sm">
                                                <div
                                                    class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                                                    <span class="tw-text-gray-500 sm:tw-text-sm tw-font-medium">£</span>
                                                </div>
                                                <input type="number" step="0.01" name="csj_loyalty_redemption_rate"
                                                    id="csj_loyalty_redemption_rate"
                                                    value="<?php echo esc_attr(get_option('csj_loyalty_redemption_rate', 0.01)); ?>"
                                                    class="tw-block tw-w-full tw-rounded-xl tw-border-0 tw-py-3 tw-pl-8 tw-pr-20 tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-200 placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-amber-500 sm:tw-text-sm sm:tw-leading-6 tw-bg-gray-50 focus:tw-bg-white tw-transition-all">
                                                <div
                                                    class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center tw-pointer-events-none">
                                                    <span class="tw-text-gray-500 sm:tw-text-sm tw-font-medium">/ point</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tw-mt-8 tw-p-4 tw-bg-blue-50 tw-rounded-xl tw-border tw-border-blue-100">
                                        <div class="tw-flex">
                                            <div class="tw-flex-shrink-0">
                                                <span class="dashicons dashicons-info tw-text-blue-400"></span>
                                            </div>
                                            <div class="tw-ml-3">
                                                <h3 class="tw-text-sm tw-font-medium tw-text-blue-800">Calculation Example</h3>
                                                <div class="tw-mt-2 tw-text-sm tw-text-blue-700">
                                                    <p>A £100 purchase earns
                                                        <strong><?php echo 100 * get_option('csj_loyalty_points_ratio', 1); ?>
                                                            points</strong>.</p>
                                                    <p>100 points can be redeemed for
                                                        <strong>£<?php echo 100 * get_option('csj_loyalty_redemption_rate', 0.01); ?></strong>
                                                        discount.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Affiliates Tab -->
                                <section id="tab-affiliates" class="csj-tab-content tw-hidden">
                                    <div class="tw-mb-8">
                                        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">Affiliate Settings</h2>
                                        <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Manage referral program configurations.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="csj_affiliate_commission_rate"
                                            class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Default
                                            Commission Rate</label>
                                        <div class="tw-relative tw-rounded-xl tw-shadow-sm tw-max-w-xs">
                                            <input type="number" step="1" name="csj_affiliate_commission_rate"
                                                id="csj_affiliate_commission_rate"
                                                value="<?php echo esc_attr(get_option('csj_affiliate_commission_rate', 10)); ?>"
                                                class="tw-block tw-w-full tw-rounded-xl tw-border-0 tw-py-3 tw-pl-4 tw-pr-12 tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-200 placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-amber-500 sm:tw-text-sm sm:tw-leading-6 tw-bg-gray-50 focus:tw-bg-white tw-transition-all">
                                            <div
                                                class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center tw-pointer-events-none">
                                                <span class="tw-text-gray-500 sm:tw-text-sm tw-font-bold">%</span>
                                            </div>
                                        </div>
                                        <p class="tw-mt-2 tw-text-sm tw-text-gray-500">Percentage of order total awarded to the
                                            affiliate.</p>
                                    </div>
                                </section>

                                <!-- Shipping Tab -->
                                <section id="tab-shipping" class="csj-tab-content tw-hidden">
                                    <div class="tw-mb-8">
                                        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">Shipping Rules</h2>
                                        <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Configure logistics and order splitting
                                            logic.</p>
                                    </div>

                                    <div class="tw-space-y-10">
                                        <!-- Weight Threshold -->
                                        <div class="tw-bg-gray-50 tw-p-6 tw-rounded-2xl tw-border tw-border-gray-100">
                                            <label for="csj_shipping_split_weight"
                                                class="tw-block tw-text-sm tw-font-semibold tw-text-gray-900 tw-mb-2">Split
                                                Weight Threshold</label>
                                            <div class="tw-flex tw-items-center tw-gap-4">
                                                <div class="tw-relative tw-rounded-xl tw-shadow-sm tw-w-40">
                                                    <input type="number" step="0.5" name="csj_shipping_split_weight"
                                                        id="csj_shipping_split_weight"
                                                        value="<?php echo esc_attr(get_option('csj_shipping_split_weight', 20)); ?>"
                                                        class="tw-block tw-w-full tw-rounded-xl tw-border-0 tw-py-3 tw-pl-4 tw-pr-12 tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-200 placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-amber-500 sm:tw-text-sm sm:tw-leading-6 tw-bg-white focus:tw-bg-white tw-transition-all">
                                                    <div
                                                        class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center tw-pointer-events-none">
                                                        <span class="tw-text-gray-500 sm:tw-text-sm tw-font-medium">kg</span>
                                                    </div>
                                                </div>
                                                <div class="tw-text-sm tw-text-gray-500">
                                                    Orders heavier than this will split to DPD + Royal Mail.
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SKU Selection -->
                                        <div>
                                            <label class="tw-block tw-text-base tw-font-bold tw-text-gray-900">Force DPD
                                                Shipping (By SKU)</label>
                                            <p class="tw-text-sm tw-text-gray-500 tw-mb-4 tw-mt-1">Search and select SKUs that
                                                will automatically trigger DPD shipping.</p>

                                            <!-- Chip Container -->
                                            <div id="csj_sku_chips"
                                                class="tw-flex tw-flex-wrap tw-gap-2 tw-mb-3 tw-min-h-[2rem]">
                                                <!-- Chips via JS -->
                                            </div>

                                            <div class="tw-relative">
                                                <div
                                                    class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                                                    <span class="dashicons dashicons-search tw-text-gray-400"></span>
                                                </div>
                                                <input type="text" id="csj_sku_search"
                                                    class="tw-block tw-w-full tw-rounded-xl tw-border-0 tw-py-3.5 tw-pl-11 tw-text-gray-900 tw-ring-1 tw-ring-inset tw-ring-gray-200 placeholder:tw-text-gray-400 focus:tw-ring-2 focus:tw-ring-inset focus:tw-ring-amber-500 sm:tw-text-sm sm:tw-leading-6 tw-bg-gray-50 focus:tw-bg-white tw-transition-all"
                                                    placeholder="Search by SKU or Product Name...">

                                                <!-- Results Dropdown -->
                                                <div id="csj_sku_results"
                                                    class="tw-hidden tw-absolute tw-z-50 tw-mt-2 tw-w-full tw-bg-white tw-shadow-2xl tw-max-h-60 tw-rounded-xl tw-ring-1 tw-ring-black/5 tw-overflow-auto focus:tw-outline-none sm:tw-text-sm tw-py-2">
                                                </div>
                                            </div>

                                            <!-- Hidden store for SKUs -->
                                            <input type="hidden" name="csj_shipping_dpd_skus" id="csj_shipping_dpd_skus"
                                                value="<?php echo esc_attr(get_option('csj_shipping_dpd_skus')); ?>">
                                        </div>
                                    </div>
                                </section>

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
