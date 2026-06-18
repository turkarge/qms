document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("qms-events-config");
    if (!el || !window.KirpiTable) return;
    const c = JSON.parse(el.textContent || "{}");
    const payload = (value, type) => {
        if (type !== "display") return value || "";
        const text = String(value || "{}");
        return `<code class="small">${KirpiTable.escape(text.length > 140 ? `${text.slice(0, 140)}...` : text)}</code>`;
    };
    KirpiTable.create(document.getElementById("qms-events-table"), {
        ajax: { url: c.endpoint },
        rowId: "row_key",
        select: false,
        order: [[0, "desc"]],
        columns: [
            {data:"recorded_at",name:"recorded_at"},
            {data:"company_name",name:"company_name"},
            {data:"event_type_name",name:"event_type"},
            {data:"entity_name",name:"entity_type"},
            {data:"actor_name",name:"actor_type"},
            {data:"source_module",name:"source_module"},
            {data:"correlation_id",name:"correlation_id",render:(v,t)=>t==="display"?`<code>${KirpiTable.escape(v || "")}</code>`:v},
            {data:"payload",name:"payload",orderable:false,render:payload}
        ],
        columnFilters: [{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},{placeholder:"Ara"},null],
        exportColumns: [0,1,2,3,4,5,6],
        exportTitle: "QMS Events",
        stateKey: "qms-events",
        fixedHeader: false
    });
});
