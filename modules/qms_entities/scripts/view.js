document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("qms-entities-config");
    if (!el || !window.KirpiTable) return;
    const c = JSON.parse(el.textContent || "{}");
    const optionCache = new WeakMap();
    const status = (v, t) => t === "display" ? `<span class="badge bg-${v === "active" ? "success" : v === "archived" ? "secondary" : "azure"}-lt">${KirpiTable.escape(c.labels[v] || v)}</span>` : v;
    const filterOptions = (select, companyId, attr) => {
        if (!optionCache.has(select)) optionCache.set(select, Array.from(select.options).map((o) => ({ value: o.value, text: o.textContent, companyId: o.dataset.companyId || "", companyIds: o.dataset.companyIds || "" })));
        const selected = select.value;
        const source = optionCache.get(select);
        const items = source.filter((o) => o.value === "" || (attr === "companyIds" ? o.companyIds.split(",").includes(companyId) : o.companyId === companyId));
        select.replaceChildren(...items.map((o) => { const e = new Option(o.text, o.value, false, o.value === selected); if (o.companyId) e.dataset.companyId = o.companyId; if (o.companyIds) e.dataset.companyIds = o.companyIds; return e; }));
        if (!Array.from(select.options).some((o) => o.value === selected)) select.value = "";
        select.disabled = companyId === "";
    };
    const syncForm = (form) => {
        const companyId = form.querySelector("[data-qms-entity-company]")?.value || "";
        form.querySelectorAll("[data-company-unit]").forEach((select) => filterOptions(select, companyId, "companyId"));
        form.querySelectorAll("[data-company-user]").forEach((select) => filterOptions(select, companyId, "companyIds"));
    };
    const renderActions = (_, t, r) => {
        if (t !== "display") return "";
        const edit = c.canEdit ? `<a href="#" class="dropdown-item btn-modal-trigger" data-url="/ajax/qms_entities/form?id=${Number(r.id)}" data-size="modal-lg"><i class="ti ti-edit me-2"></i>${KirpiTable.escape(c.labels.edit)}</a>` : "";
        const archive = c.canArchive && r.status !== "archived" ? `<button class="dropdown-item js-qms-entity-archive" data-id="${Number(r.id)}"><i class="ti ti-archive me-2"></i>${KirpiTable.escape(c.labels.archive)}</button>` : "";
        return edit || archive ? `<div class="dropdown kirpi-row-actions"><button class="btn btn-sm btn-icon btn-ghost-secondary js-kirpi-row-menu" type="button"><i class="ti ti-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end">${edit}${archive}</div></div>` : "";
    };
    const entities = KirpiTable.create(document.getElementById("qms-entities-table"), {
        ajax: { url: `${c.endpoint}?resource=entities` }, rowId: "row_key", select: false, order: [[0, "asc"]],
        columns: [{data:"entity_code",name:"entity_code"},{data:"entity_type_name",name:"entity_type_name"},{data:"title",name:"title"},{data:"company_name",name:"company_name"},{data:"owner_name",name:"owner_name"},{data:"status",name:"status",render:status},{data:null,name:"actions",orderable:false,searchable:false,render:renderActions}],
        columnFilters: [{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},null], exportColumns: [0,1,2,3,4,5], exportTitle: "QMS Entities", stateKey: "qms-entities", fixedHeader: false
    });
    const typeActions = (_, t, r) => t !== "display" || !c.canEdit ? "" : `<a href="#" class="btn btn-sm btn-icon btn-ghost-secondary btn-modal-trigger" data-url="/ajax/qms_entities/type-form?company_id=${Number(r.company_id)}&entity_type=${encodeURIComponent(r.entity_type)}" data-size="modal-lg"><i class="ti ti-edit"></i></a>`;
    const types = KirpiTable.create(document.getElementById("qms-entity-types-table"), {
        ajax: { url: `${c.endpoint}?resource=types` }, select: false, order: [[0, "asc"]],
        columns: [{data:"company_name",name:"company_name"},{data:"entity_type",name:"entity_type"},{data:"display_name",name:"display_name"},{data:"owner_module",name:"owner_module"},{data:"entity_prefix",name:"entity_prefix"},{data:"template",name:"template"},{data:"is_numbered",name:"is_numbered",render:(v,t)=>t==="display" ? (Number(v)===1 ? "Evet" : "Hayır") : v},{data:null,name:"actions",orderable:false,searchable:false,render:typeActions}],
        columnFilters: Array(7).fill({placeholder:"Ara"}).concat([null]), exportColumns: [0,1,2,3,4,5,6], exportTitle: "QMS Entity Types", stateKey: "qms-entity-types", fixedHeader: false
    });
    document.addEventListener("click", async (e) => {
        const b = e.target.closest(".js-qms-entity-archive"); if (!b) return; b.disabled = true;
        try { const r = await KirpiTable.post("qms_entities/actions/archive", {id:b.dataset.id}); KirpiTable.notify(r); entities.ajax.reload(null,false); }
        catch (error) { KirpiTable.notifyError(error); } finally { b.disabled = false; }
    });
    document.addEventListener("kirpi:form.success", (event) => {
        const result = event.detail?.result || {}; const row = result.data?.row;
        if (result.status === "success" && result.data?.resource === "types") { types.ajax.reload(null, false); return; }
        if (result.status !== "success" || result.data?.resource !== "entities" || !row) return;
        const existing = entities.row(`#entities-${Number(row.id)}`);
        if (existing.any()) existing.data(row).draw(false); else entities.row.add(row).draw(false);
    });
    document.addEventListener("change", (event) => { const company = event.target.closest("[data-qms-entity-company]"); if (company) syncForm(company.form); });
    const observer = new MutationObserver((mutations) => mutations.forEach((m) => m.addedNodes.forEach((node) => { if (!(node instanceof Element)) return; if (node.matches("#qms-entities-form")) syncForm(node); node.querySelectorAll?.("#qms-entities-form").forEach(syncForm); })));
    ["main-modal-content", "secondary-modal-content"].forEach((id) => { const content = document.getElementById(id); if (content) observer.observe(content, {childList:true, subtree:true}); });
});
