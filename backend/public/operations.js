document.querySelector('#print-document')?.addEventListener('click',()=>window.print());

const items=document.querySelector('#items');
let next=items?.children.length??0;
document.querySelector('#add-item')?.addEventListener('click',()=>{
    if(items.children.length>=50)return;
    const row=document.querySelector('#item-template').content.cloneNode(true);
    row.querySelectorAll('[data-field]').forEach(input=>input.name=`items[${next}][${input.dataset.field}]`);
    next++;
    items.appendChild(row);
});
items?.addEventListener('click',event=>{
    if(event.target.matches('.remove-item')&&items.children.length>1)event.target.closest('.item-row').remove();
});

const hazards=document.querySelector('#hazards');
const riskDescription=(likelihood,severity)=>{
    const index=`${likelihood}${severity}`;
    if(['5A','5B','5C','4A','4B','3A'].includes(index))return'Intolerable';
    if(['3E','2D','2E','1B','1C','1D','1E'].includes(index))return'Acceptable';
    return'Tolerable';
};
const updateRisk=row=>{
    const likelihood=row.querySelector('[data-likelihood]')?.value;
    const severity=row.querySelector('[data-severity]')?.value;
    if(!likelihood||!severity)return;
    row.querySelector('[data-risk-index]').textContent=`${likelihood}${severity}`;
    row.querySelector('[data-risk-description]').textContent=riskDescription(likelihood,severity);
};
const reindexHazards=()=>{
    hazards?.querySelectorAll('[data-hazard-row]').forEach((row,index)=>{
        row.querySelector('[data-risk-number]').textContent=index+1;
        row.querySelectorAll('[name]').forEach(input=>{
            input.name=input.name.replace(/hazards\[\d+\]/,`hazards[${index}]`);
        });
    });
};
document.querySelector('#add-hazard')?.addEventListener('click',()=>{
    if(hazards.children.length>=15)return;
    const fragment=document.querySelector('#hazard-template').content.cloneNode(true);
    const row=fragment.querySelector('[data-hazard-row]');
    const index=hazards.children.length;
    row.querySelector('[data-risk-number]').textContent=index+1;
    row.querySelectorAll('[data-hazard-field]').forEach(input=>input.name=`hazards[${index}][${input.dataset.hazardField}]`);
    hazards.appendChild(fragment);
});
hazards?.addEventListener('click',event=>{
    if(event.target.matches('.remove-hazard')&&hazards.children.length>1){
        event.target.closest('[data-hazard-row]').remove();
        reindexHazards();
    }
});
hazards?.addEventListener('change',event=>{
    if(event.target.matches('[data-likelihood],[data-severity]'))updateRisk(event.target.closest('[data-hazard-row]'));
});
hazards?.querySelectorAll('[data-hazard-row]').forEach(updateRisk);
