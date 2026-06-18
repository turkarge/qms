document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("qms-relationships-config");
    if (!el || !window.KirpiTable) return;
    const c = JSON.parse(el.textContent || "{}");
    const optionCache = new WeakMap();
    const status = (v, t) => t === "display" ? `<span class="badge bg-${v === "active" ? "success" : v === "archived" ? "secondary" : "azure"}-lt">${KirpiTable.escape(c.labels[v] || v)}</span>` : v;
    const entityLabel = (prefix, row) => `${row[`${prefix}_code`] || ""} - ${row[`${prefix}_title`] || ""}`;
    const renderActions = (_, t, r) => {
        if (t !== "display") return "";
        const edit = c.canManage ? `<a href="#" class="dropdown-item btn-modal-trigger" data-url="/ajax/qms_relationships/form?id=${Number(r.id)}" data-size="modal-lg"><i class="ti ti-edit me-2"></i>${KirpiTable.escape(c.labels.edit)}</a>` : "";
        const archive = c.canArchive && r.status !== "archived" ? `<button class="dropdown-item js-qms-relationship-archive" data-id="${Number(r.id)}"><i class="ti ti-archive me-2"></i>${KirpiTable.escape(c.labels.archive)}</button>` : "";
        return edit || archive ? `<div class="dropdown kirpi-row-actions"><button class="btn btn-sm btn-icon btn-ghost-secondary js-kirpi-row-menu" type="button"><i class="ti ti-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end">${edit}${archive}</div></div>` : "";
    };
    const table = KirpiTable.create(document.getElementById("qms-relationships-table"), {
        ajax: { url: c.endpoint }, rowId: "row_key", select: false, order: [[0, "asc"]],
        columns: [
            {data:"company_name",name:"company_name"},
            {data:null,name:"source_title",render:(d,t,r)=>t==="display"?KirpiTable.escape(entityLabel("source", r)):entityLabel("source", r)},
            {data:null,name:"target_title",render:(d,t,r)=>t==="display"?KirpiTable.escape(entityLabel("target", r)):entityLabel("target", r)},
            {data:"relationship_type_name",name:"relationship_type_name"},
            {data:"relationship_kind",name:"relationship_kind"},
            {data:"status",name:"status",render:status},
            {data:null,name:"actions",orderable:false,searchable:false,render:renderActions}
        ],
        columnFilters: [{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},null],
        exportColumns: [0,1,2,3,4,5], exportTitle: "QMS Relationships", stateKey: "qms-relationships"
    });
    const filterEntities = (select, companyId) => {
        if (!optionCache.has(select)) optionCache.set(select, Array.from(select.options).map((o) => ({ value: o.value, text: o.textContent, companyId: o.dataset.companyId || "" })));
        const selected = select.value;
        const items = optionCache.get(select).filter((o) => o.value === "" || o.companyId === companyId);
        select.replaceChildren(...items.map((o) => { const e = new Option(o.text, o.value, false, o.value === selected); if (o.companyId) e.dataset.companyId = o.companyId; return e; }));
        if (!Array.from(select.options).some((o) => o.value === selected)) select.value = "";
        select.disabled = companyId === "";
    };
    const syncForm = (form) => {
        const companyId = form.querySelector("[data-qms-relationship-company]")?.value || "";
        form.querySelectorAll("[data-company-entity]").forEach((select) => filterEntities(select, companyId));
    };
    document.addEventListener("click", async (e) => {
        const b = e.target.closest(".js-qms-relationship-archive"); if (!b) return; b.disabled = true;
        try { const r = await KirpiTable.post("qms_relationships/actions/archive", {id:b.dataset.id}); KirpiTable.notify(r); table.ajax.reload(null,false); }
        catch (error) { KirpiTable.notifyError(error); } finally { b.disabled = false; }
    });
    document.addEventListener("kirpi:form.success", (event) => {
        const result = event.detail?.result || {}; const row = result.data?.row;
        if (result.status !== "success" || result.data?.resource !== "relationships" || !row) return;
        const existing = table.row(`#relationships-${Number(row.id)}`);
        if (existing.any()) existing.data(row).draw(false); else table.row.add(row).draw(false);
    });
    document.addEventListener("change", (event) => { const company = event.target.closest("[data-qms-relationship-company]"); if (company) syncForm(company.form); });
    const observer = new MutationObserver((mutations) => mutations.forEach((m) => m.addedNodes.forEach((node) => { if (!(node instanceof Element)) return; if (node.matches("#qms-relationships-form")) syncForm(node); node.querySelectorAll?.("#qms-relationships-form").forEach(syncForm); })));
    ["main-modal-content", "secondary-modal-content"].forEach((id) => { const content = document.getElementById(id); if (content) observer.observe(content, {childList:true, subtree:true}); });
});
