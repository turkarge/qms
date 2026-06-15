document.addEventListener("DOMContentLoaded", function () {
    const element = document.getElementById("roles-data-table");
    const configElement = document.getElementById("roles-table-config");
    if (!element || !configElement || !window.KirpiTable) return;

    const config = JSON.parse(configElement.textContent || "{}");
    const permissions = config.permissions || {};
    const labels = config.labels || {};
    const renderActions = (row) => {
        const actions = [];
        if (permissions.permissions) {
            actions.push(`<a class="dropdown-item" href="${window.KIRPI_CONFIG.baseUrl}/roles/permissions?id=${Number(row.id)}"><i class="ti ti-shield-check me-2"></i>${KirpiTable.escape(labels.permissions)}</a>`);
        }
        if (permissions.edit) {
            actions.push(`<a href="#" class="dropdown-item btn-modal-trigger" data-url="/ajax/roles/edit?id=${Number(row.id)}" data-size="modal-md"><i class="ti ti-edit me-2"></i>${KirpiTable.escape(labels.edit)}</a>`);
        }
        if (!actions.length) return "";
        return `<div class="dropdown kirpi-row-actions"><button class="btn btn-sm btn-icon btn-ghost-secondary js-kirpi-row-menu" type="button" aria-expanded="false" aria-label="İşlemler"><i class="ti ti-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end">${actions.join("")}</div></div>`;
    };

    const table = KirpiTable.create(element, {
        ajax: { url: config.endpoint },
        select: false,
        rowId: "row_key",
        order: [[0, "asc"]],
        columns: [
            { data: "name", name: "name", render: (value, type, row) => type === "display" ? `<div class="fw-medium">${KirpiTable.escape(value)}</div><div class="text-secondary small">ID: ${Number(row.id)}</div>` : value },
            { data: "is_active", name: "is_active", render: (value, type, row) => {
                if (type !== "display") return value ? 1 : 0;
                if (!permissions.status) return value ? `<span class="badge bg-success-lt">${KirpiTable.escape(labels.active)}</span>` : `<span class="badge bg-danger-lt">${KirpiTable.escape(labels.inactive)}</span>`;
                return `<label class="form-check form-switch m-0"><input class="form-check-input roles-status-switch" type="checkbox" data-id="${Number(row.id)}" ${value ? "checked" : ""} aria-label="Rol durumunu değiştir"></label>`;
            } },
            { data: "user_count", name: "user_count" },
            { data: "permission_count", name: "permission_count" },
            { data: null, name: "actions", orderable: false, searchable: false, render: (_, type, row) => type === "display" ? renderActions(row) : "" }
        ],
        columnFilters: [
            { placeholder: "Rol ara", label: "Role göre filtrele" },
            { type: "select", label: "Duruma göre filtrele", options: [{ value: "", label: "Tümü" }, { value: "1", label: labels.active }, { value: "0", label: labels.inactive }] },
            null,
            null,
            null
        ],
        exportColumns: [0, 1, 2, 3],
        exportTitle: "Roller",
        stateKey: "roles",
        serverExport: {
            endpoint: config.exportEndpoint,
            filters: (dt) => ({ search: dt.column("name:name").search(), status: dt.column("is_active:name").search() })
        }
    });

    element.addEventListener("change", async function (event) {
        const input = event.target.closest(".roles-status-switch");
        if (!input) return;
        input.disabled = true;
        try {
            const result = await KirpiTable.post("roles/actions/toggle-status", { id: input.dataset.id, status: input.checked ? 1 : 0 });
            KirpiTable.notify(result);
            table.ajax.reload(null, false);
        } catch (error) {
            input.checked = !input.checked;
            KirpiTable.notifyError(error);
        } finally {
            input.disabled = false;
        }
    });

    document.addEventListener("kirpi:form.success", function (event) {
        if (["roles-create-form", "roles-edit-form"].includes(event.detail.form?.id)) table.ajax.reload(null, false);
    });
});
