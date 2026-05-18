/**
 * Site Extensions Snapshot - Admin Scripts
 *
 * @package Siteexsn
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    var SiteexsnAdmin = {

        currentFilter: 'all',
        currentSearch: '',

        init: function () {
            this.bindEvents();
            this.initTooltips();
        },

        bindEvents: function () {
            $(document).on('click', '.siteexsn-export-section .button', function (e) {
                SiteexsnAdmin.handleExportClick(e);
            });

            $(document).on('click', '.nav-tab-wrapper .nav-tab', function (e) {
                SiteexsnAdmin.handleTabClick(e);
            });

            $(document).on('click', '.siteexsn-filter-chips .siteexsn-chip', function (e) {
                SiteexsnAdmin.handleChipClick(e);
            });

            $(document).on('keydown', function (e) {
                SiteexsnAdmin.handleKeyboardNavigation(e);
            });
        },

        handleExportClick: function (e) {
            var $button = $(e.currentTarget);
            var $form = $button.closest('form');

            $button.addClass('siteexsn-loading');
            $button.prop('disabled', true);

            var originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update"></span> ' + siteexsn_ajax.strings.exporting);

            $form.submit();

            setTimeout(function () {
                $button.removeClass('siteexsn-loading');
                $button.prop('disabled', false);
                $button.html(originalText);
            }, 5000);
        },

        handleTabClick: function (e) {
            var $tab = $(e.currentTarget);
            var href = $tab.attr('href') || '';
            var match = href.match(/[?&]tab=([^&]+)/);
            if (match) {
                sessionStorage.setItem('siteexsn_current_tab', match[1]);
            }
        },

        handleChipClick: function (e) {
            var $chip = $(e.currentTarget);
            var filter = $chip.data('filter');

            $chip.closest('.siteexsn-filter-chips').find('.siteexsn-chip')
                .removeClass('is-active')
                .attr('aria-selected', 'false');
            $chip.addClass('is-active').attr('aria-selected', 'true');

            this.currentFilter = filter;
            this.applyFilters();
        },

        handleKeyboardNavigation: function (e) {
            // Avoid hijacking shortcuts when the user is typing in an input.
            var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                return;
            }

            if ((e.ctrlKey || e.metaKey) && e.keyCode === 69) {
                e.preventDefault();
                $('.siteexsn-export-section .button').click();
            }

            if ((e.ctrlKey || e.metaKey) && e.keyCode === 49) {
                e.preventDefault();
                $('.nav-tab-wrapper .nav-tab[href*="tab=plugins"]')[0].click();
            }

            if ((e.ctrlKey || e.metaKey) && e.keyCode === 50) {
                e.preventDefault();
                $('.nav-tab-wrapper .nav-tab[href*="tab=themes"]')[0].click();
            }
        },

        initTooltips: function () {
            $('.siteexsn-status').each(function () {
                var $status = $(this);
                var status = $.trim($status.text()).toLowerCase();
                var tooltip = '';

                if (status === 'active') {
                    tooltip = siteexsn_ajax.strings.tooltip_active;
                } else if (status === 'inactive') {
                    tooltip = siteexsn_ajax.strings.tooltip_inactive;
                }

                if (tooltip) {
                    $status.attr('title', tooltip);
                }
            });
        },

        debounce: function (func, wait) {
            var timeout;
            return function () {
                clearTimeout(timeout);
                timeout = setTimeout(func, wait);
            };
        },

        initSearch: function () {
            var $searchInput = $('.siteexsn-export-section .siteexsn-search');
            if (!$searchInput.length) {
                return;
            }

            $searchInput.attr('placeholder', siteexsn_ajax.strings.search_placeholder);

            var debouncedSearch = this.debounce(function () {
                SiteexsnAdmin.currentSearch = ($searchInput.val() || '').toLowerCase();
                SiteexsnAdmin.applyFilters();
            }, 250);

            $searchInput.on('input', debouncedSearch);
        },

        applyFilters: function () {
            var filter = this.currentFilter;
            var search = this.currentSearch;

            $('.siteexsn-list-table tbody tr').each(function () {
                var $row = $(this);
                var status = ($row.data('status') || '').toString();
                var hasUpdate = $row.data('update') === 1 || $row.data('update') === '1';

                var matchesFilter = (
                    filter === 'all' ||
                    (filter === 'active' && status === 'active') ||
                    (filter === 'inactive' && status === 'inactive') ||
                    (filter === 'update' && hasUpdate)
                );

                var matchesSearch = !search || $row.text().toLowerCase().indexOf(search) > -1;

                $row.toggle(matchesFilter && matchesSearch);
            });
        },

        initSortableTables: function () {
            // Scope strictly to our own list-table headers so we don't touch
            // other tables that may exist on the page.
            $('.siteexsn-list-table thead th').each(function () {
                var $th = $(this);
                var columnIndex = $th.index();

                $th.css('cursor', 'pointer');
                $th.append(' <span class="dashicons dashicons-arrow-up-alt2 siteexsn-sort-icon"></span>');

                $th.on('click', function () {
                    SiteexsnAdmin.sortTable($th.closest('table'), columnIndex);
                });
            });
        },

        sortTable: function ($table, columnIndex) {
            var $tbody = $table.find('tbody');
            var $rows = $tbody.find('tr').toArray();
            var sortDirection = $table.data('sort-direction') === 'asc' ? 'desc' : 'asc';

            $rows.sort(function (a, b) {
                var aText = $(a).find('td').eq(columnIndex).text().trim();
                var bText = $(b).find('td').eq(columnIndex).text().trim();
                return sortDirection === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });

            $tbody.empty().append($rows);
            $table.data('sort-direction', sortDirection);

            $table.find('.siteexsn-sort-icon').removeClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
            $table.find('thead th').eq(columnIndex).find('.siteexsn-sort-icon').addClass(
                sortDirection === 'asc' ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'
            );
        }
    };

    $(document).ready(function () {
        SiteexsnAdmin.init();
        SiteexsnAdmin.initSearch();
        SiteexsnAdmin.initSortableTables();
    });

})(jQuery);
