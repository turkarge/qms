(function (window, document) {
    "use strict";

    const escape = (value) => String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

    const language = {
        emptyTable: "Tabloda veri bulunmuyor",
        info: "_TOTAL_ kayıttan _START_ - _END_ arası",
        infoEmpty: "Kayıt yok",
        infoFiltered: "(_MAX_ kayıt içinden filtrelendi)",
        lengthMenu: "Sayfada _MENU_ kayıt",
        loadingRecords: "Yükleniyor...",
        processing: "Veriler hazırlanıyor...",
        search: "",
        searchPlaceholder: "Tabloda ara...",
        zeroRecords: "Eşleşen kayıt bulunamadı",
        paginate: { first: "İlk", last: "Son", next: "Sonraki", previous: "Önceki" },
        select: { rows: { _: "%d kayıt seçildi", 0: "", 1: "1 kayıt seçildi" } }
    };

    const buildServerExportUrl = (config, dt, format) => {
        const params = new URLSearchParams({ format });
        const search = dt.search();
        if (search) params.set("search", search);
        const filters = config.filters ? config.filters(dt) : {};
        Object.entries(filters || {}).forEach(([key, value]) => {
            if (value !== "" && value !== null && value !== undefined) params.set(key, value);
        });
        return `${config.endpoint}?${params.toString()}`;
    };

    const exportButtons = (options) => {
        const columns = options.exportColumns || ":visible:not(:first-child):not(:last-child)";
        const common = {
            exportOptions: { columns, modifier: { selected: null } },
            title: options.exportTitle || document.title
        };
        const items = [
            { extend: "copyHtml5", text: '<i class="ti ti-copy me-2"></i>Kopyala', ...common },
            { extend: "csvHtml5", text: '<i class="ti ti-file-type-csv me-2"></i>CSV (görünen)', bom: true, ...common },
            { extend: "excelHtml5", text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel (görünen)', ...common },
            { extend: "print", text: '<i class="ti ti-printer me-2"></i>Yazdır', ...common }
        ];

        if (options.serverExport?.endpoint) {
            items.push(
                {
                    text: '<i class="ti ti-database-export me-2"></i>CSV (tüm sonuçlar)',
                    action: (_, dt) => { window.location.href = buildServerExportUrl(options.serverExport, dt, "csv"); }
                },
                {
                    text: '<i class="ti ti-database-export me-2"></i>Excel (tüm sonuçlar)',
                    action: (_, dt) => { window.location.href = buildServerExportUrl(options.serverExport, dt, "xls"); }
                }
            );
        }

        return items;
    };

    const toolbarButtons = (options, stateKey, serverSide) => [
        {
            extend: "collection",
            text: '<i class="ti ti-download"></i><span class="visually-hidden">Dışa aktar</span>',
            titleAttr: "Dışa aktar",
            className: "btn-icon kirpi-table-tool",
            buttons: exportButtons(options)
        },
        {
            extend: "collection",
            text: '<i class="ti ti-columns-3"></i><span class="visually-hidden">Kolonlar</span>',
            titleAttr: "Kolonları yönet",
            className: "btn-icon kirpi-table-tool",
            buttons: [
                { extend: "columnsToggle", columns: ":not(:first-child):not(:last-child)" },
                {
                    text: '<i class="ti ti-restore me-2"></i>Görünümü sıfırla',
                    action: (_, dt) => {
                        localStorage.removeItem(`kirpi_table_${stateKey}`);
                        dt.state.clear();
                        window.location.reload();
                    }
                }
            ]
        },
        {
            text: '<i class="ti ti-refresh"></i><span class="visually-hidden">Yenile</span>',
            titleAttr: "Tabloyu yenile",
            className: "btn-icon kirpi-table-tool kirpi-table-refresh",
            action: (_, dt) => serverSide ? dt.ajax.reload(null, false) : window.location.reload()
        }
    ];

    const create = (element, options) => {
        if (!window.DataTable) {
            throw new Error("DataTables yüklenmedi.");
        }

        if (DataTable.isDataTable(element)) {
            return new DataTable.Api(element);
        }

        element.classList.add("kirpi-data-table");
        element.closest(".card")?.classList.add("kirpi-table-card");

        const stateKey = options.stateKey || element.id || "table";
        const configuredColumns = options.columns || Array.from(element.querySelectorAll("thead tr:first-child th")).map(() => ({}));
        const serverSide = options.serverSide !== false;
        const enableSelection = options.select !== false;
        const enableResponsive = options.responsive !== false;
        const enablePaging = options.paging !== false;
        const enableButtons = options.buttons !== false;
        const filterDefinitions = options.columnFilters || [];
        const hasColumnFilters = filterDefinitions.some(Boolean);
        const toolbar = document.createElement("div");
        const toolbarSearch = document.createElement("input");
        toolbar.className = "input-group kirpi-table-control";
        toolbarSearch.type = "search";
        toolbarSearch.className = "form-control kirpi-table-search";
        toolbarSearch.placeholder = language.searchPlaceholder;
        toolbarSearch.setAttribute("aria-label", language.searchPlaceholder);
        toolbar.appendChild(toolbarSearch);
        const filterRow = document.createElement("tr");
        filterRow.className = "kirpi-table-column-filters";
        (hasColumnFilters ? filterDefinitions : []).forEach((filter, index) => {
            const cell = document.createElement("th");
            if (filter) {
                let control;
                if (filter.type === "select") {
                    control = document.createElement("select");
                    control.className = "form-select form-select-sm";
                    (filter.options || []).forEach((option) => {
                        const item = document.createElement("option");
                        item.value = option.value;
                        item.textContent = option.label;
                        control.appendChild(item);
                    });
                } else {
                    control = document.createElement("input");
                    control.type = "search";
                    control.className = "form-control form-control-sm";
                    control.placeholder = filter.placeholder || "Filtrele";
                }
                control.dataset.columnFilter = String(index);
                control.dataset.columnName = configuredColumns[index]?.name || configuredColumns[index]?.data || String(index);
                control.dataset.tableFilter = element.id;
                control.setAttribute("aria-label", filter.label || filter.placeholder || "Kolon filtresi");
                cell.appendChild(control);
            }
            filterRow.appendChild(cell);
        });
        if (hasColumnFilters) element.tHead?.appendChild(filterRow);

        const table = new DataTable(element, {
            processing: true,
            serverSide,
            deferRender: true,
            searchDelay: 350,
            stateSave: true,
            stateDuration: 60 * 60 * 24 * 30,
            stateSaveCallback: (_, data) => localStorage.setItem(`kirpi_table_${stateKey}`, JSON.stringify(data)),
            stateLoadCallback: () => {
                try { return JSON.parse(localStorage.getItem(`kirpi_table_${stateKey}`) || "null"); }
                catch (_) { return null; }
            },
            ajax: serverSide ? options.ajax : undefined,
            columns: options.columns,
            order: options.order || [],
            orderMulti: true,
            pageLength: options.pageLength || 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            autoWidth: false,
            titleRow: 0,
            paging: enablePaging,
            responsive: enableResponsive ? { details: { type: "inline", target: "tr" } } : false,
            select: enableSelection ? { style: "multi+shift", selector: "td:first-child", headerCheckbox: "select-page" } : false,
            colReorder: options.colReorder === false ? false : { columns: options.reorderColumns || ":not(:first-child):not(:last-child)" },
            fixedHeader: options.fixedHeader === false ? false : { header: true, headerOffset: document.querySelector(".navbar")?.offsetHeight || 0 },
            keys: { columns: ":not(:first-child):not(:last-child)", keys: [9, 13, 37, 38, 39, 40] },
            rowId: options.rowId,
            language,
            layout: {
                top: enableButtons ? toolbar : (options.search === false ? null : "search"),
                topStart: null,
                topEnd: null,
                bottomStart: enablePaging ? ["pageLength", "info"] : "info",
                bottomEnd: enablePaging ? "paging" : null
            }
        });

        if (enableButtons) {
            new DataTable.Buttons(table, { buttons: toolbarButtons(options, stateKey, serverSide) });
            const buttonContainer = table.buttons(0, null).container();
            toolbar.appendChild(buttonContainer instanceof HTMLElement ? buttonContainer : buttonContainer[0]);
            toolbar.closest(".dt-layout-row")?.classList.add("kirpi-table-toolbar");
            toolbarSearch.value = table.search();
            let toolbarSearchTimer = null;
            toolbarSearch.addEventListener("input", () => {
                clearTimeout(toolbarSearchTimer);
                toolbarSearchTimer = setTimeout(() => table.search(toolbarSearch.value).draw(), 300);
            });
            table.on("search", () => {
                if (toolbarSearch.value !== table.search()) toolbarSearch.value = table.search();
            });
        }

        let filterTimer = null;
        const filterColumn = (control) => {
            const name = control.dataset.columnName;
            return name && !/^\d+$/.test(name) ? table.column(`${name}:name`) : table.column(Number(control.dataset.columnFilter));
        };
        const syncColumnFilters = () => {
            configuredColumns.forEach((column, index) => {
                const name = column.name || column.data || String(index);
                const apiColumn = name && !/^\d+$/.test(String(name)) ? table.column(`${name}:name`) : table.column(index);
                const value = apiColumn.search();
                document.querySelectorAll(`[data-table-filter="${element.id}"][data-column-filter="${index}"]`).forEach((control) => {
                    if (control.value !== value) control.value = value;
                });
            });
        };
        const handleColumnFilter = (event) => {
            const control = event.target.closest(`[data-table-filter="${element.id}"][data-column-filter]`);
            if (!control) return;
            if (event.type === "click") {
                event.stopPropagation();
                return;
            }
            if (event.type === "input" && control.tagName === "SELECT") return;
            if (event.type === "change" && control.tagName !== "SELECT") return;
            const column = filterColumn(control);
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => {
                const value = control.value.trim();
                if (column.search() !== value) {
                    column.search(value).draw();
                }
            }, control.tagName === "SELECT" ? 0 : 300);
        };
        document.addEventListener("input", handleColumnFilter);
        document.addEventListener("change", handleColumnFilter);
        document.addEventListener("click", handleColumnFilter);
        table.on("draw column-reorder column-visibility", syncColumnFilters);
        syncColumnFilters();

        element.addEventListener("click", (event) => {
            const action = event.target.closest(".kirpi-row-actions .dropdown-item");
            if (action && window.bootstrap && bootstrap.Dropdown) {
                const actionToggle = action.closest(".kirpi-row-actions")?.querySelector(".js-kirpi-row-menu");
                if (actionToggle) {
                    bootstrap.Dropdown.getOrCreateInstance(actionToggle).hide();
                }
                return;
            }

            const toggle = event.target.closest(".js-kirpi-row-menu");
            if (!toggle || !(window.bootstrap && bootstrap.Dropdown)) return;
            event.preventDefault();
            event.stopPropagation();
            bootstrap.Dropdown.getOrCreateInstance(toggle, { boundary: "viewport", popperConfig: { strategy: "fixed" } }).toggle();
        });

        document.addEventListener("kirpi:theme.changed", () => {
            table.columns.adjust();
            if (enableResponsive && table.responsive) table.responsive.recalc();
        });

        return table;
    };

    const profileOptions = (element) => {
        const profile = element.dataset.kirpiTable || "standard";
        const title = element.dataset.tableTitle || document.querySelector(".page-title")?.textContent?.trim() || document.title;
        const slug = `${window.location.pathname}-${title}`
            .toLocaleLowerCase("tr-TR")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-|-$/g, "");
        const sameTitleIndex = Array.from(document.querySelectorAll(`table[data-table-title="${CSS.escape(title)}"]`)).indexOf(element);
        const id = element.id || `kirpi-table-${slug || "table"}-${Math.max(0, sameTitleIndex)}`;
        element.id = id;
        const base = {
            serverSide: false,
            stateKey: id,
            exportTitle: title,
            select: false,
            order: [],
            pageLength: Number(element.dataset.pageLength || 25),
            exportColumns: ":visible",
            reorderColumns: ":not(:last-child)"
        };

        if (profile === "compact") {
            return { ...base, buttons: false, paging: false, search: false, colReorder: false, responsive: true };
        }
        if (profile === "matrix") {
            return { ...base, buttons: false, paging: false, search: true, colReorder: false, responsive: false };
        }
        if (profile === "report") {
            return { ...base, select: false, paging: true, responsive: true };
        }
        return { ...base, select: element.dataset.selectable === "true", paging: true, responsive: true };
    };

    const enhance = (root = document) => {
        const selector = 'table[data-kirpi-table]:not([data-kirpi-ready="true"])';
        const elements = [
            ...(root.matches?.(selector) ? [root] : []),
            ...(root.querySelectorAll?.(selector) || [])
        ];
        elements.forEach((element) => {
            if (element.querySelector("tbody td[colspan], tbody th[colspan]")) {
                element.dataset.kirpiReady = "empty";
                return;
            }
            element.dataset.kirpiReady = "true";
            try {
                create(element, profileOptions(element));
            } catch (error) {
                element.dataset.kirpiReady = "error";
                console.error("KirpiTable başlatılamadı:", error);
            }
        });
    };

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) enhance(node);
        }));
    });

    const post = async (path, data) => {
        const formData = new FormData();
        Object.entries(data || {}).forEach(([key, value]) => formData.append(key, value));
        formData.append("csrf_token", window.KIRPI_CONFIG?.csrfToken || "");
        const base = (window.KIRPI_CONFIG?.baseUrl || "").replace(/\/$/, "");
        const response = await fetch(`${base}/${String(path).replace(/^\//, "")}`, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: formData
        });
        const result = await response.json().catch(() => ({ status: "error", message: "Sunucu yanıtı okunamadı." }));
        if (!response.ok || result.status === "error") {
            throw new Error(result.message || "İşlem tamamlanamadı.");
        }
        return result;
    };

    const notify = (result) => {
        if (window.toastr) toastr.success(result.message || "İşlem tamamlandı.");
    };

    const notifyError = (error) => {
        if (window.toastr) toastr.error(error?.message || "İşlem tamamlanamadı.");
    };

    window.KirpiTable = { create, enhance, escape, post, notify, notifyError };
    document.addEventListener("DOMContentLoaded", () => {
        enhance(document);
        observer.observe(document.body, { childList: true, subtree: true });
    });
})(window, document);
