document.addEventListener("DOMContentLoaded", function () {
    const configEl = document.getElementById("organization-config");
    const resourcesEl = document.getElementById("organization-resources");
    if (!configEl || !resourcesEl || !window.KirpiTable) return;
    const config = JSON.parse(configEl.textContent || "{}");
    const resources = JSON.parse(resourcesEl.textContent || "{}");
    const permissions = config.permissions || {};
    const labels = config.labels || {};
    const tables = {};
    const definitions = {
        companies: [
            ["company_code", "Şirket Kodu"], ["company_name", "Şirket Adı"], ["legal_name", "Ticari Unvan"], ["status", "Durum"], ["updated_at_display", "Güncellenme"]
        ],
        units: [
            ["unit_code", "Birim Kodu"], ["unit_name", "Birim Adı"], ["unit_type", "Tür"], ["company_name", "Şirket"], ["parent_name", "Üst Birim"], ["status", "Durum"]
        ],
        positions: [
            ["position_code", "Pozisyon Kodu"], ["position_name", "Pozisyon Adı"], ["company_name", "Şirket"], ["department_name", "Departman"], ["status", "Durum"]
        ],
        assignments: [
            ["user_name", "Kullanıcı"], ["company_name", "Şirket"], ["unit_name", "Birim"], ["position_name", "Pozisyon"], ["scope_mode", "Kapsam"], ["status", "Durum"]
        ]
    };
    const canEdit = (resource) => resource === "assignments" ? permissions.assign : permissions.edit;
    const renderStatus = (value, type, row, resource) => {
        if (type !== "display") return value;
        if (!permissions.status) return `<span class="badge bg-${value === "active" ? "success" : "secondary"}-lt">${KirpiTable.escape(labels[value] || value)}</span>`;
        return `<label class="form-check form-switch m-0"><input class="form-check-input js-organization-status" type="checkbox" data-resource="${resource}" data-id="${Number(row.id)}" ${value === "active" ? "checked" : ""}></label>`;
    };
    const renderActions = (row, resource) => !canEdit(resource) ? "" : `<div class="dropdown kirpi-row-actions"><button class="btn btn-sm btn-icon btn-ghost-secondary js-kirpi-row-menu" type="button"><i class="ti ti-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end"><a href="#" class="dropdown-item btn-modal-trigger" data-url="/ajax/organization/form?resource=${resource}&id=${Number(row.id)}" data-size="modal-lg"><i class="ti ti-edit me-2"></i>${KirpiTable.escape(labels.edit)}</a></div></div>`;
    Object.keys(definitions).forEach((resource) => {
        const tableEl = document.getElementById(`organization-${resource}-table`);
        const head = document.querySelector(`[data-organization-head="${resource}"]`);
        if (!tableEl || !head) return;
        head.innerHTML = definitions[resource].map(([, title]) => `<th>${KirpiTable.escape(title)}</th>`).join("") + `<th class="w-1 text-center"><i class="ti ti-settings"></i></th>`;
        const columns = definitions[resource].map(([data]) => ({
            data, name: data.replace("_display", ""),
            render: data === "status" ? (value, type, row) => renderStatus(value, type, row, resource) :
                data === "unit_type" ? (value, type) => type === "display" ? KirpiTable.escape(labels[value] || value) : value :
                data === "scope_mode" ? (value, type) => type === "display" ? KirpiTable.escape(value.replaceAll("_", " ")) : value : undefined
        }));
        columns.push({ data: null, name: "actions", orderable: false, searchable: false, render: (_, type, row) => type === "display" ? renderActions(row, resource) : "" });
        tables[resource] = KirpiTable.create(tableEl, {
            ajax: { url: `${config.endpoint}?resource=${resource}` }, select: false, rowId: "row_key", order: [[0, "asc"]], columns,
            columnFilters: definitions[resource].map(([data]) => data === "status" ? { type: "select", options: [{value:"",label:"Tümü"},{value:"active",label:labels.active},{value:"inactive",label:labels.inactive}] } : { placeholder: "Ara" }).concat([null]),
            exportColumns: definitions[resource].map((_, i) => i), exportTitle: resources[resource].label, stateKey: `organization-${resource}`,
            serverExport: permissions.export ? { endpoint: `${config.exportEndpoint}?resource=${resource}`, filters: (dt) => ({ search: dt.search() }) } : undefined
        });
        tableEl.addEventListener("change", async (event) => {
            const input = event.target.closest(".js-organization-status"); if (!input) return; input.disabled = true;
            try { const result = await KirpiTable.post("organization/actions/toggle-status", {resource: input.dataset.resource, id: input.dataset.id, status: input.checked ? "active" : "inactive"}); KirpiTable.notify(result); tables[resource].ajax.reload(null, false); }
            catch (error) { input.checked = !input.checked; KirpiTable.notifyError(error); } finally { input.disabled = false; }
        });
    });
    const newButton = document.getElementById("organization-new-button");
    document.querySelectorAll('[data-bs-toggle="tab"][data-resource]').forEach((tab) => tab.addEventListener("shown.bs.tab", () => {
        const resource = tab.dataset.resource; const item = resources[resource];
        newButton.dataset.url = `/ajax/organization/form?resource=${resource}`; newButton.querySelector("span").textContent = item.new;
        newButton.hidden = resource === "assignments" ? !permissions.assign : !permissions.create;
        tables[resource]?.columns.adjust().responsive?.recalc();
    }));
    document.addEventListener("kirpi:form.success", (event) => {
        const result = event.detail?.result || {};
        const resource = result.data?.resource;
        const row = result.data?.row;
        if (result.status !== "success" || !resource || !row || !tables[resource]) return;
        const table = tables[resource];
        const existing = table.row(`#${resource}-${Number(row.id)}`);
        if (existing.any()) existing.data(row).draw(false);
        else table.row.add(row).draw(false);
        window.setTimeout(() => table.ajax.reload(null, false), 300);
    });
});
