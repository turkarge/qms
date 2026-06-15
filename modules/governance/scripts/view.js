document.addEventListener("DOMContentLoaded", function () {
    const configEl = document.getElementById("governance-config");
    const resourcesEl = document.getElementById("governance-resources");
    if (!configEl || !resourcesEl || !window.KirpiTable) return;
    const config = JSON.parse(configEl.textContent || "{}");
    const resources = JSON.parse(resourcesEl.textContent || "{}");
    const tables = {};
    const userOptionCache = new WeakMap();
    const syncCompanyUsers = (form) => {
        const companyId = form.querySelector("[data-governance-company]")?.value || "";
        form.querySelectorAll("[data-company-user]").forEach((select) => {
            if (!userOptionCache.has(select)) userOptionCache.set(select, Array.from(select.options).map((option) => ({value:option.value,text:option.textContent,companyIds:option.dataset.companyIds || ""})));
            const selected = select.value;
            const options = userOptionCache.get(select).filter((option) => option.value === "" || option.companyIds.split(",").includes(companyId));
            select.replaceChildren(...options.map((option) => { const element = new Option(option.text, option.value, false, option.value === selected); if(option.companyIds) element.dataset.companyIds=option.companyIds; return element; }));
            if (!Array.from(select.options).some((option) => option.value === selected)) select.value = "";
            select.disabled = companyId === "";
        });
    };
    const definitions = {
        ownerships: [["company_name", "Şirket"], ["subject_type", "Kayıt Türü"], ["subject_title", "Kayıt Başlığı"], ["ownership_type", "Sahiplik Türü"], ["owner_name", "Sorumlu"], ["starts_on", "Başlangıç"], ["ends_on", "Bitiş"], ["status", "Durum"]],
        delegations: [["company_name", "Şirket"], ["from_user_name", "Asıl Sorumlu"], ["to_user_name", "Vekil"], ["starts_on", "Başlangıç"], ["ends_on", "Bitiş"], ["status", "Durum"]]
    };
    const renderStatus = (value, type) => type === "display" ? `<span class="badge bg-${value === "active" ? "success" : value === "expired" ? "warning" : "secondary"}-lt">${KirpiTable.escape(config.labels[value] || value)}</span>` : value;
    Object.keys(definitions).forEach((resource) => {
        const tableEl = document.getElementById(`governance-${resource}-table`); const head = document.querySelector(`[data-governance-head="${resource}"]`); if (!tableEl || !head) return;
        head.innerHTML = definitions[resource].map(([, title]) => `<th>${KirpiTable.escape(title)}</th>`).join("") + '<th class="w-1 text-center"><i class="ti ti-settings"></i></th>';
        const columns = definitions[resource].map(([data]) => ({data, name:data, render:data === "status" ? renderStatus : undefined}));
        columns.push({data:null,name:"actions",orderable:false,searchable:false,render:(_,type,row)=>{if(type!=="display"||!config.permissions[resource])return "";const edit=`<a href="#" class="dropdown-item btn-modal-trigger" data-url="/ajax/governance/form?resource=${resource}&id=${Number(row.id)}" data-size="modal-lg"><i class="ti ti-edit me-2"></i>${KirpiTable.escape(config.labels.edit)}</a>`;const revoke=resource==="delegations"&&row.status!=="revoked"?`<button type="button" class="dropdown-item js-governance-revoke" data-id="${Number(row.id)}"><i class="ti ti-ban me-2"></i>${KirpiTable.escape(config.labels.revoke)}</button>`:"";return `<div class="dropdown kirpi-row-actions"><button class="btn btn-sm btn-icon btn-ghost-secondary js-kirpi-row-menu" type="button"><i class="ti ti-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end">${edit}${revoke}</div></div>`;}});
        tables[resource]=KirpiTable.create(tableEl,{ajax:{url:`${config.endpoint}?resource=${resource}`},select:false,rowId:"row_key",order:[[0,"asc"]],columns,columnFilters:definitions[resource].map(()=>({placeholder:"Ara"})).concat([null]),exportColumns:definitions[resource].map((_,i)=>i),exportTitle:resources[resource].label,stateKey:`governance-${resource}`});
    });
    const newButton=document.getElementById("governance-new-button");document.querySelectorAll('[data-bs-toggle="tab"][data-resource]').forEach((tab)=>tab.addEventListener("shown.bs.tab",()=>{const resource=tab.dataset.resource;newButton.dataset.url=`/ajax/governance/form?resource=${resource}`;newButton.querySelector("span").textContent=resources[resource].new;newButton.hidden=!config.permissions[resource];tables[resource]?.columns.adjust().responsive?.recalc();}));
    document.addEventListener("kirpi:form.success",(event)=>{const result=event.detail?.result||{};const resource=result.data?.resource;const row=result.data?.row;if(result.status!=="success"||!resource||!row||!tables[resource])return;const existing=tables[resource].row(`#${resource}-${Number(row.id)}`);if(existing.any())existing.data(row).draw(false);else tables[resource].row.add(row).draw(false);});
    document.addEventListener("click",async(event)=>{const button=event.target.closest(".js-governance-revoke");if(!button)return;button.disabled=true;try{const result=await KirpiTable.post("governance/actions/revoke",{id:button.dataset.id});KirpiTable.notify(result);tables.delegations.ajax.reload(null,false);}catch(error){KirpiTable.notifyError(error);}finally{button.disabled=false;}});
    document.addEventListener("change",(event)=>{const company=event.target.closest("[data-governance-company]");if(company)syncCompanyUsers(company.form);});
    const observer=new MutationObserver((mutations)=>mutations.forEach((mutation)=>mutation.addedNodes.forEach((node)=>{if(!(node instanceof Element))return;if(node.matches("#governance-form"))syncCompanyUsers(node);node.querySelectorAll?.("#governance-form").forEach(syncCompanyUsers);})));["main-modal-content","secondary-modal-content"].forEach((id)=>{const content=document.getElementById(id);if(content)observer.observe(content,{childList:true,subtree:true});});
});
