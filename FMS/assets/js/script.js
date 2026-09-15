// FMS Custom JavaScript
(function () {
    'use strict';

    var formIsDirty = false;

    function addInteractionStyles() {
        if (document.getElementById('fms-interaction-styles')) return;

        var style = document.createElement('style');
        style.id = 'fms-interaction-styles';
        style.textContent = [
            '.fms-table-tools{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap}',
            '.fms-table-search{max-width:320px}',
            '.fms-table-result{color:#6c757d;font-size:.82rem}',
            '.fms-sortable{cursor:pointer;user-select:none;white-space:nowrap}',
            '.fms-sortable:focus{outline:2px solid #087f8c;outline-offset:-2px}',
            '.fms-sortable::after{content:"\\f0dc";font-family:"Font Awesome 6 Free";font-weight:900;margin-left:7px;color:#adb5bd;font-size:.72em}',
            '.fms-sortable.fms-sort-asc::after{content:"\\f0de";color:#087f8c}',
            '.fms-sortable.fms-sort-desc::after{content:"\\f0dd";color:#087f8c}',
            '.fms-progress{height:4px;margin-top:6px;background:#e9ecef;border-radius:3px;overflow:hidden}',
            '.fms-progress-bar{height:100%;width:0;background:#087f8c;transition:width .2s ease}',
            '.fms-count{float:right;color:#6c757d;font-size:.75rem;font-weight:400}',
            '.fms-sidebar-overlay{display:none}',
            '@media(max-width:991.98px){.fms-sidebar-overlay{display:block;position:fixed;inset:0;background:rgba(24,32,43,.35);z-index:1035;opacity:0;pointer-events:none;transition:opacity .2s}.sidebar-open .fms-sidebar-overlay{opacity:1;pointer-events:auto}}',
            '@media(prefers-reduced-motion:reduce){.fms-progress-bar,.fms-sidebar-overlay{transition:none}}'
        ].join('');
        document.head.appendChild(style);
    }

    function setupSidebar() {
        var toggle = document.querySelector('[data-widget="pushmenu"]') || document.getElementById('sidebarToggle');
        if (!toggle) return;

        var overlay = document.createElement('div');
        overlay.className = 'fms-sidebar-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(overlay);

        function closeOnSmallScreen() {
            if (window.innerWidth < 992) document.body.classList.remove('sidebar-open');
        }

        toggle.addEventListener('click', function () {
            if (window.innerWidth < 992) document.body.classList.toggle('sidebar-open');
            toggle.setAttribute('aria-expanded', String(!document.body.classList.contains('sidebar-collapse')));
        });
        overlay.addEventListener('click', closeOnSmallScreen);
        document.querySelectorAll('.main-sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', closeOnSmallScreen);
        });
    }

    function setupTableTools() {
        document.querySelectorAll('.table').forEach(function (table) {
            var body = table.tBodies[0];
            var rows = body ? Array.from(body.rows) : [];
            if (!body || rows.length < 4 || table.closest('.notification-dropdown')) return;

            var cardBody = table.closest('.card-body');
            if (!cardBody || cardBody.querySelector('.fms-table-tools')) return;

            var tools = document.createElement('div');
            tools.className = 'fms-table-tools';
            var search = document.createElement('input');
            search.type = 'search';
            search.className = 'form-control form-control-sm fms-table-search';
            search.placeholder = 'Search this table...';
            search.setAttribute('aria-label', 'Search this table');
            var result = document.createElement('span');
            result.className = 'fms-table-result';
            tools.append(search, result);
            cardBody.insertBefore(tools, table);

            function updateResult() {
                var query = search.value.trim().toLowerCase();
                var visible = 0;
                Array.from(body.rows).forEach(function (row) {
                    var matches = !query || row.textContent.toLowerCase().indexOf(query) !== -1;
                    row.hidden = !matches;
                    if (matches) visible++;
                });
                result.textContent = visible + ' of ' + body.rows.length + ' rows';
            }

            search.addEventListener('input', updateResult);
            updateResult();

            table.querySelectorAll('thead th').forEach(function (heading, index) {
                if (heading.textContent.trim().toLowerCase() === 'action') return;
                heading.classList.add('fms-sortable');
                heading.tabIndex = 0;
                heading.setAttribute('role', 'button');
                heading.setAttribute('aria-label', 'Sort by ' + heading.textContent.trim());

                function sortRows() {
                    var descending = heading.classList.toggle('fms-sort-desc');
                    heading.classList.toggle('fms-sort-asc', !descending);
                    table.querySelectorAll('thead th').forEach(function (other) {
                        if (other !== heading) other.classList.remove('fms-sort-asc', 'fms-sort-desc');
                    });
                    rows.sort(function (first, second) {
                        var left = (first.cells[index] || {}).textContent || '';
                        var right = (second.cells[index] || {}).textContent || '';
                        return left.trim().localeCompare(right.trim(), undefined, { numeric: true, sensitivity: 'base' }) * (descending ? -1 : 1);
                    });
                    rows.forEach(function (row) { body.appendChild(row); });
                    updateResult();
                }

                heading.addEventListener('click', sortRows);
                heading.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        sortRows();
                    }
                });
            });
        });
    }

    function setupForms() {
        document.querySelectorAll('form').forEach(function (form) {
            var fields = form.querySelectorAll('input, select, textarea');
            if (!fields.length || form.method.toLowerCase() === 'get') return;

            fields.forEach(function (field) {
                field.addEventListener('change', function () { formIsDirty = true; });
                if (field.tagName.toLowerCase() !== 'textarea') return;

                var counter = document.createElement('small');
                counter.className = 'fms-count';
                field.parentNode.appendChild(counter);
                var progress = document.createElement('div');
                progress.className = 'fms-progress';
                progress.innerHTML = '<div class="fms-progress-bar"></div>';
                field.parentNode.appendChild(progress);
                var bar = progress.firstElementChild;
                var update = function () {
                    var length = field.value.trim().length;
                    counter.textContent = length + ' characters';
                    bar.style.width = Math.min(length / 500 * 100, 100) + '%';
                };
                field.addEventListener('input', function () { formIsDirty = true; update(); });
                update();
            });

            form.addEventListener('submit', function () { formIsDirty = false; });
        });

        window.addEventListener('beforeunload', function (event) {
            if (!formIsDirty) return;
            event.preventDefault();
            event.returnValue = '';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        addInteractionStyles();
        setupSidebar();
        setupTableTools();
        setupForms();

        setTimeout(function () {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);

        $(document).on('click', '.btn-outline-danger, .btn-outline-secondary', function () {
            var label = $(this).text().trim().toLowerCase();
            if (label.includes('deactivate') || label.includes('suspend')) {
                return confirm('Are you sure you want to perform this action?');
            }
        });
    });

    $(document).on('click', '.main-header .notification-toggle, .main-header .dropdown-toggle', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var menu = $(this).closest('.nav-item.dropdown');
        var isOpen = menu.toggleClass('dropdown-open').hasClass('dropdown-open');
        $(this).attr('aria-expanded', String(isOpen));
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.main-header .nav-item.dropdown').length) {
            $('.main-header .nav-item.dropdown').removeClass('dropdown-open')
                .find('[aria-haspopup="true"]').attr('aria-expanded', 'false');
        }
    });
}());
