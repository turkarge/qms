document.addEventListener("DOMContentLoaded", function () {
    const element = document.getElementById("notifications-data-table");
    const configElement = document.getElementById("notifications-table-config");
    if (!element || !configElement || !window.KirpiTable) return;
    const config = JSON.parse(configElement.textContent || "{}");
    const labels = config.labels || {};
    const optionList = (values, allLabel) => [{ value: "", label: allLabel }, ...(values || []).map((value) => ({ value, label: value }))];

    const table = KirpiTable.create(element, {
        ajax: { url: config.endpoint },
        select: false,
        rowId: "row_key",
        order: [[4, "desc"]],
        columns: [
            { data: "title", name: "title", render: (value, type, row) => type === "display" ? `<div class="fw-medium">${KirpiTable.escape(value)}</div><div class="text-secondary">${KirpiTable.escape(row.message)}</div>${row.template_key ? `<div class="mt-1"><code>${KirpiTable.escape(row.template_key)}</code></div>` : ""}` : value },
            { data: "source_module", name: "source_module", render: (value, type, row) => type === "display" ? `${value ? `<span class="badge bg-blue-lt">${KirpiTable.escape(value)}</span>` : '<span class="text-secondary">-</span>'}${row.entity_type ? `<div class="text-secondary small mt-1">${KirpiTable.escape(row.entity_type)}${row.entity_id ? ` #${Number(row.entity_id)}` : ""}</div>` : ""}` : value },
            { data: "channel", name: "channel" },
            { data: "read_status", name: "read_status", render: (value, type) => type === "display" ? `<span class="badge ${value === "read" ? "bg-success-lt" : "bg-warning-lt"}">${KirpiTable.escape(value === "read" ? labels.read : labels.unread)}</span>` : value },
            { data: "created_at_display", name: "created_at" },
            { data: null, name: "actions", orderable: false, searchable: false, render: (_, type, row) => type === "display" && row.read_status === "unread" ? `<button type="button" class="btn btn-sm btn-icon btn-ghost-secondary js-notification-read" data-id="${Number(row.id)}" title="${KirpiTable.escape(labels.markRead)}" aria-label="${KirpiTable.escape(labels.markRead)}"><i class="ti ti-check"></i></button>` : '<i class="ti ti-check text-success" aria-hidden="true"></i>' }
        ],
        columnFilters: [
            { placeholder: "Başlık veya mesaj ara", label: "Bildirime göre filtrele" },
            { type: "select", label: "Kaynağa göre filtrele", options: optionList(config.sourceModules, "Tüm kaynaklar") },
            { placeholder: "Kanal ara", label: "Kanala göre filtrele" },
            { type: "select", label: "Duruma göre filtrele", options: [{ value: "", label: labels.all }, { value: "unread", label: labels.unread }, { value: "read", label: labels.read }] },
            { placeholder: "Tarih ara", label: "Tarihe göre filtrele" },
            null
        ],
        exportColumns: [0, 1, 2, 3, 4],
        exportTitle: "Bildirimler",
        stateKey: "notifications",
        serverExport: {
            endpoint: config.exportEndpoint,
            filters: (dt) => ({
                search: dt.column("title:name").search(),
                source_module: dt.column("source_module:name").search(),
                status: dt.column("read_status:name").search()
            })
        }
    });

    document.addEventListener("click", async function (event) {
        const button = event.target.closest(".js-notification-read");
        if (!button || !button.closest("#notifications-data-table")) return;
        event.preventDefault();
        event.stopPropagation();
        button.disabled = true;
        try {
            const result = await KirpiTable.post(config.markReadEndpoint, { id: button.dataset.id });
            KirpiTable.notify(result);
            document.querySelectorAll(`.js-notification-item[data-notification-id="${CSS.escape(String(button.dataset.id))}"]`).forEach((item) => {
                window.KirpiCore?.markNotificationItemAsRead(item);
            });
            if (window.KirpiCore?.setNotificationUnreadCount && Number.isFinite(Number(result.unread_count))) {
                window.KirpiCore.setNotificationUnreadCount(Number(result.unread_count));
            }
            table.ajax.reload(null, false);
        } catch (error) {
            KirpiTable.notifyError(error);
        } finally {
            button.disabled = false;
        }
    });

    document.addEventListener("kirpi:form.success", function (event) {
        if (event.detail.form?.id === "notifications-mark-all-read-form") table.ajax.reload(null, false);
    });
});
