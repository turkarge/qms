document.addEventListener("DOMContentLoaded", function () {
    const tableElement = document.getElementById("users-data-table");
    const configElement = document.getElementById("users-table-config");

    if (!tableElement || !configElement || !window.KirpiTable) {
        return;
    }

    const config = JSON.parse(configElement.textContent || "{}");
    const permissions = config.permissions || {};
    const labels = config.labels || {};
    const selectionShell = document.getElementById("users-selection-shell");
    const selectionBar = document.getElementById("users-selection-bar");
    const selectionCount = document.getElementById("users-selection-count");
    const selectionClear = document.getElementById("users-selection-clear");
    const renderActions = (data) => {
        const id = Number(data.id || 0);
        const actions = [];

        if (permissions.edit) {
            actions.push(`<a href="#" class="dropdown-item btn-modal-trigger" data-url="/ajax/users/edit?id=${id}" data-size="modal-lg"><i class="ti ti-edit me-2"></i>${KirpiTable.escape(labels.edit)}</a>`);
        }
        if (permissions.dropSession) {
            actions.push(`<button type="button" class="dropdown-item js-user-row-action" data-action="drop-session" data-id="${id}" data-confirm="${KirpiTable.escape(labels.dropSessionConfirm)}"><i class="ti ti-logout me-2"></i>${KirpiTable.escape(labels.session)}</button>`);
        }
        if (permissions.resetLock) {
            actions.push(`<button type="button" class="dropdown-item js-user-row-action" data-action="reset-lock-key" data-id="${id}" data-confirm="${KirpiTable.escape(labels.resetKeyConfirm)}"><i class="ti ti-key me-2"></i>${KirpiTable.escape(labels.key)}</button>`);
        }

        if (actions.length === 0) {
            return "";
        }

        return `<div class="dropdown kirpi-row-actions"><button class="btn btn-sm btn-icon btn-ghost-secondary js-kirpi-row-menu" type="button" aria-expanded="false" aria-label="İşlemler"><i class="ti ti-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end">${actions.join("")}</div></div>`;
    };

    const table = KirpiTable.create(tableElement, {
        ajax: { url: config.endpoint },
        rowId: "row_key",
        order: [[5, "desc"]],
        columns: [
            { data: null, name: "selection", defaultContent: "", orderable: false, searchable: false, render: DataTable.render.select(), className: "select-checkbox" },
            {
                data: "name",
                name: "name",
                render: function (value, type, row) {
                    if (type !== "display") return value;
                    const avatar = row.avatar_url
                        ? `<span class="avatar avatar-sm me-2" style="background-image:url('${KirpiTable.escape(row.avatar_url)}')"></span>`
                        : `<span class="avatar avatar-sm me-2">${KirpiTable.escape(row.initial)}</span>`;
                    return `<div class="d-flex align-items-center">${avatar}<span class="fw-medium">${KirpiTable.escape(value)}</span></div>`;
                }
            },
            { data: "email", name: "email", render: (value, type) => type === "display" ? `<a class="link-secondary" href="mailto:${KirpiTable.escape(value)}">${KirpiTable.escape(value)}</a>` : value },
            {
                data: "role_name",
                name: "role_name",
                render: function (value, type, row) {
                    if (type !== "display") return value || "";
                    const suffix = row.role_is_active === false ? " <span class=\"badge bg-secondary-lt\">Pasif</span>" : "";
                    return `${KirpiTable.escape(value || "-")}${suffix}`;
                }
            },
            {
                data: "is_active",
                name: "is_active",
                render: function (value, type, row) {
                    if (type !== "display") return value ? 1 : 0;
                    if (!permissions.status) {
                        return value ? `<span class="badge bg-success-lt">${KirpiTable.escape(labels.active)}</span>` : `<span class="badge bg-danger-lt">${KirpiTable.escape(labels.inactive)}</span>`;
                    }
                    return `<label class="form-check form-switch m-0"><input class="form-check-input users-status-switch" type="checkbox" data-id="${Number(row.id)}" ${value ? "checked" : ""} aria-label="Kullanıcı durumunu değiştir"></label>`;
                }
            },
            { data: "created_at_display", name: "created_at" },
            { data: "updated_at_display", name: "updated_at" },
            { data: null, name: "actions", orderable: false, searchable: false, render: (_, type, row) => type === "display" ? renderActions(row) : "" }
        ],
        columnFilters: [
            null,
            { placeholder: "Ad ara", label: "Ada göre filtrele" },
            { placeholder: "E-posta ara", label: "E-postaya göre filtrele" },
            { placeholder: "Rol ara", label: "Role göre filtrele" },
            {
                type: "select",
                label: "Duruma göre filtrele",
                options: [
                    { value: "", label: "Tümü" },
                    { value: "1", label: labels.active },
                    { value: "0", label: labels.inactive }
                ]
            },
            { placeholder: "Tarih ara", label: "Oluşturulma tarihine göre filtrele" },
            { placeholder: "Tarih ara", label: "Güncellenme tarihine göre filtrele" },
            null
        ],
        exportColumns: [1, 2, 3, 4, 5, 6],
        exportTitle: "Kullanıcılar",
        stateKey: "users",
        serverExport: {
            endpoint: config.exportEndpoint,
            filters: (dt) => ({
                name: dt.column("name:name").search(),
                email: dt.column("email:name").search(),
                role: dt.column("role_name:name").search(),
                created_at: dt.column("created_at:name").search(),
                updated_at: dt.column("updated_at:name").search()
            })
        }
    });

    const reload = () => table.ajax.reload(null, false);
    const updateSelection = () => {
        const count = table.rows({ selected: true }).count();
        if (selectionCount) selectionCount.textContent = String(count);
        if (selectionShell) selectionShell.hidden = count === 0;
        if (selectionBar) selectionBar.hidden = false;
    };
    table.on("select deselect draw", updateSelection);
    selectionClear?.addEventListener("click", () => table.rows().deselect());

    tableElement.addEventListener("change", async function (event) {
        const input = event.target.closest(".users-status-switch");
        if (!input) return;
        input.disabled = true;
        const nextStatus = input.checked ? 1 : 0;
        try {
            const result = await KirpiTable.post("users/actions/toggle-status", {
                id: input.dataset.id,
                status: nextStatus
            });
            KirpiTable.notify(result);
            reload();
        } catch (error) {
            input.checked = !input.checked;
            KirpiTable.notifyError(error);
        } finally {
            input.disabled = false;
        }
    });

    tableElement.addEventListener("click", async function (event) {
        const button = event.target.closest(".js-user-row-action");
        if (!button) return;
        event.preventDefault();
        if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) return;
        button.disabled = true;
        try {
            const result = await KirpiTable.post(`users/actions/${button.dataset.action}`, { id: button.dataset.id });
            KirpiTable.notify(result);
            if (result.redirect) {
                window.location.href = result.redirect;
                return;
            }
            reload();
        } catch (error) {
            KirpiTable.notifyError(error);
        } finally {
            button.disabled = false;
        }
    });

    document.addEventListener("kirpi:form.success", function (event) {
        const form = event.detail.form;
        if (["users-create-form", "users-edit-form"].includes(form?.id)) {
            table.ajax.reload(null, false);
        }
    });
});
