document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("standards-config");
    if (!el || !window.KirpiTable) return;
    const c = JSON.parse(el.textContent || "{}");
    const status = (v, t) => t === "display" ? `<span class="badge bg-${v === "published" || v === "active" ? "success" : v === "archived" ? "secondary" : "azure"}-lt">${KirpiTable.escape(c.labels[v] || v)}</span>` : v;
    const standardLabel = (row) => `${row.standard_code || ""} ${row.standard_name || ""}`.trim();
    const create = (id, resource, columns, exportColumns) => KirpiTable.create(document.getElementById(id), {
        ajax: { url: `${c.endpoint}?resource=${resource}` },
        rowId: "row_key",
        select: false,
        order: [[0, "asc"]],
        columns,
        columnFilters: columns.map((column) => column.searchable === false ? null : {placeholder:"Ara"}),
        exportColumns,
        exportTitle: `Standards ${resource}`,
        stateKey: `standards-${resource}`,
        fixedHeader: false
    });
    create("standards-table", "standards", [
        {data:"company_name",name:"company_name"},
        {data:"standard_code",name:"standard_code"},
        {data:"standard_name",name:"standard_name"},
        {data:"status",name:"status",render:status}
    ], [0,1,2,3]);
    create("standards-versions-table", "versions", [
        {data:"company_name",name:"company_name"},
        {data:null,name:"standard_code",render:(d,t,r)=>t==="display"?KirpiTable.escape(standardLabel(r)):standardLabel(r)},
        {data:"version_label",name:"version_label"},
        {data:"status",name:"status",render:status}
    ], [0,1,2,3]);
    create("standards-requirements-table", "requirements", [
        {data:"company_name",name:"company_name"},
        {data:null,name:"standard_code",render:(d,t,r)=>t==="display"?KirpiTable.escape(standardLabel(r)):standardLabel(r)},
        {data:"clause_code",name:"clause_code"},
        {data:"requirement_code",name:"requirement_code"},
        {data:"title",name:"title"},
        {data:"status",name:"status",render:status}
    ], [0,1,2,3,4,5]);
    create("standards-controls-table", "controls", [
        {data:"company_name",name:"company_name"},
        {data:null,name:"standard_code",render:(d,t,r)=>t==="display"?KirpiTable.escape(standardLabel(r)):standardLabel(r)},
        {data:"requirement_code",name:"requirement_code"},
        {data:"control_code",name:"control_code"},
        {data:"title",name:"title"},
        {data:"status",name:"status",render:status}
    ], [0,1,2,3,4,5]);
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach((tab) => {
        tab.addEventListener("shown.bs.tab", () => DataTable.tables({visible:true, api:true}).columns.adjust());
    });
});
