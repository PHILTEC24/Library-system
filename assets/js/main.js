/**
 * LibraryMS - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // =========================================================
    // 1. Confirmation dialogs for delete links/forms
    //    Usage: <a href="..." data-confirm="Are you sure?">Delete</a>
    //           <button data-confirm="Are you sure?">Delete</button>
    //           <form data-confirm="Are you sure?">...</form>
    // =========================================================
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        const tag = el.tagName.toLowerCase();

        if (tag === 'form') {
            el.addEventListener('submit', function (e) {
                const msg = el.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        } else {
            // anchor or button
            el.addEventListener('click', function (e) {
                const msg = el.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    });

    // =========================================================
    // 2. Auto-dismiss flash/alert messages after 5 seconds
    // =========================================================
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            // Use Bootstrap's Alert API if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } else {
                // Fallback: fade out manually
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function () {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    });

    // =========================================================
    // 3. Initialize Bootstrap Tooltips
    //    Usage: <span data-bs-toggle="tooltip" title="My tip">...</span>
    // =========================================================
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el, {
                trigger: 'hover focus'
            });
        });
    }

    // =========================================================
    // 4. Live search / filter for tables with class 'searchable'
    //    Expects: <input class="table-filter" data-target="#myTable">
    //    or auto-detects .searchable tables paired with a
    //    .table-search-input within the same container.
    //
    //    Filters rows whose text content contains the search term.
    // =========================================================

    // Auto-setup: find every .searchable table and inject a search
    // input above it if one isn't already present.
    document.querySelectorAll('table.searchable').forEach(function (table) {
        // Look for an existing sibling input with data-target pointing here
        const existingInput = document.querySelector(
            '[data-table-filter][data-target="#' + table.id + '"]'
        );
        if (existingInput) return; // already has one, skip

        if (!table.id) {
            table.id = 'tbl-' + Math.random().toString(36).slice(2, 7);
        }

        // Inject search input before the table (or its .table-responsive wrapper)
        const wrapper = table.closest('.table-responsive') || table;
        const container = wrapper.parentElement;

        const searchDiv = document.createElement('div');
        searchDiv.className = 'mb-2 search-box';
        searchDiv.innerHTML =
            '<input type="search" class="form-control table-search-input" ' +
            'placeholder="Search..." data-table-filter data-target="#' + table.id + '">';
        container.insertBefore(searchDiv, wrapper);
    });

    // Attach filter logic to all [data-table-filter] inputs
    document.querySelectorAll('[data-table-filter]').forEach(function (input) {
        input.addEventListener('input', function () {
            const targetSelector = input.getAttribute('data-target');
            if (!targetSelector) return;

            const table = document.querySelector(targetSelector);
            if (!table) return;

            const term = input.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });

            // Show "no results" row if all hidden
            let visibleCount = 0;
            rows.forEach(function (r) { if (r.style.display !== 'none') visibleCount++; });

            let noResultsRow = table.querySelector('.no-results-row');
            if (visibleCount === 0) {
                if (!noResultsRow) {
                    const colCount = table.querySelectorAll('thead th').length || 1;
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML =
                        '<td colspan="' + colCount + '" class="text-center text-muted py-3">' +
                        '<i class="bi bi-search me-1"></i>No matching records found.</td>';
                    table.querySelector('tbody').appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        });
    });

    // =========================================================
    // 5. Highlight current nav link based on URL path
    // =========================================================
    const path = window.location.pathname;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(function (link) {
        if (link.href && path.startsWith(new URL(link.href, window.location.origin).pathname)) {
            link.classList.add('active');
        }
    });

});
