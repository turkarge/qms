document.addEventListener("DOMContentLoaded", function () {
    const element = document.getElementById("audit-data-table");
    const configElement = document.getElementById("audit-table-config");
    if (!element || !configElement || !window.KirpiTable) return;
    const config = JSON.parse(configElement.textContent || "{}");

    KirpiTable.create(element, {
        ajax: { url: config.endpoint },
        select: false,
        responsive: true,
        order: [[0, "desc"]],
        columns: [
            { data: "id", name: "id" },
            { data: "created_at_display", name: "created_at" },
            { data: "user_display", name: "user_name" },
            { data: "module_key", name: "module_key", render: (value, type) => type === "display" ? `<code>${KirpiTable.escape(value)}</code>` : value },
            { data: "action_key", name: "action_key", render: (value, type) => type === "display" ? `<code>${KirpiTable.escape(value)}</code>` : value },
            { data: "status", name: "status", render: (value, type) => type === "display" ? `<span class="badge ${value === "success" ? "bg-success-lt" : "bg-danger-lt"}">${KirpiTable.escape(value)}</span>` : value },
            { data: "route_path", name: "route_path", render: (value, type, row) => type === "display" ? `<code>${KirpiTable.escape(value || "-")}</code><div class="small text-secondary">${KirpiTable.escape(row.request_method || "")}</div>` : value },
            { data: "ip_address", name: "ip_address", render: (value, type) => type === "display" ? `<code>${KirpiTable.escape(value || "-")}</code>` : value },
            { data: "details_json", name: "details_json", orderable: false, render: (value, type) => type === "display" ? `<details><summary>Gör</summary><pre class="mb-0">${KirpiTable.escape(value || "")}</pre></details>` : value }
        ],
        columnFilters: [
            null,
            { placeholder: "Tarih ara", label: "Tarihe göre filtrele" },
            { placeholder: "Kullanıcı ara", label: "Kullanıcıya göre filtrele" },
            { placeholder: "Modül ara", label: "Modüle göre filtrele" },
            { placeholder: "İşlem ara", label: "İşleme göre filtrele" },
            { type: "select", label: "Duruma göre filtrele", options: [{ value: "", label: config.labels.all }, { value: "success", label: "success" }, { value: "failed", label: "failed" }] },
            { placeholder: "Rota ara", label: "Rotaya göre filtrele" },
            { placeholder: "IP ara", label: "IP adresine göre filtrele" },
            null
        ],
        exportColumns: [0, 1, 2, 3, 4, 5, 6, 7],
        exportTitle: "Audit Log",
        stateKey: "audit-list",
        serverExport: {
            endpoint: config.exportEndpoint,
            filters: (dt) => ({
                module: dt.column("module_key:name").search(),
                action: dt.column("action_key:name").search(),
                status: dt.column("status:name").search(),
                user: dt.column("user_name:name").search(),
                route: dt.column("route_path:name").search(),
                ip: dt.column("ip_address:name").search(),
                date: dt.column("created_at:name").search()
            })
        }
    });
});
