(() => {
  const page=document.querySelector('[data-public-booking]'); if(!page)return;
  const form=document.querySelector('[data-public-form]'), service=form.querySelector('[name=servico_id]'), professional=form.querySelector('[name=professional_choice]'), date=form.querySelector('[name=data]'), professionalId=form.querySelector('[name=profissional_id]'), start=form.querySelector('[name=inicio]'), slots=form.querySelector('[data-slots]'), submit=form.querySelector('[data-public-submit]');
  const escapeHtml=(value)=>String(value).replace(/[&<>'"]/g,(char)=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  const resetSlots=(message)=>{slots.innerHTML=`<span class="subtle">${escapeHtml(message)}</span>`;professionalId.value='';start.value='';submit.disabled=true;};
  service.addEventListener('change',async()=>{
    resetSlots('Escolha o profissional e a data.'); professional.disabled=true; professional.innerHTML='<option>Carregando…</option>';
    if(!service.value){professional.innerHTML='<option value="">Escolha o serviço primeiro</option>';return;}
    const response=await fetch(`${page.dataset.professionalsUrl}?servico_id=${encodeURIComponent(service.value)}`,{headers:{Accept:'application/json'}}); const json=await response.json();
    professional.innerHTML='<option value="">Selecione</option><option value="0">Qualquer profissional disponível</option>'+json.data.map((item)=>`<option value="${item.id}">${escapeHtml(item.nome)}${item.especialidade?' · '+escapeHtml(item.especialidade):''}</option>`).join(''); professional.disabled=false;
  });
  const loadSlots=async()=>{
    if(!service.value||professional.value===''||!date.value){resetSlots('Selecione serviço, profissional e data.');return;}
    resetSlots('Consultando a agenda…');
    const query=new URLSearchParams({servico_id:service.value,profissional_id:professional.value,data:date.value}); const response=await fetch(`${page.dataset.availabilityUrl}?${query}`,{headers:{Accept:'application/json'}}); const json=await response.json();
    if(!response.ok){resetSlots(json.error||'Não foi possível consultar agora.');return;}
    if(!json.data.length){resetSlots('Nenhum horário livre nesta data.');return;}
    slots.innerHTML=json.data.map((item)=>`<button type="button" class="slot" data-start="${item.value}" data-professional="${item.profissional_id}"><strong>${item.label}</strong><small style="display:block">${escapeHtml(item.profissional_nome)}</small></button>`).join('');
    slots.querySelectorAll('.slot').forEach((button)=>button.addEventListener('click',()=>{slots.querySelectorAll('.slot').forEach((item)=>item.classList.remove('selected'));button.classList.add('selected');start.value=button.dataset.start;professionalId.value=button.dataset.professional;submit.disabled=false;}));
  };
  professional.addEventListener('change',loadSlots); date.addEventListener('change',loadSlots);
})();

