(() => {
  const root = document.documentElement;
  const saved = localStorage.getItem('agenda-theme');
  if (saved) root.dataset.theme = saved;
  document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('agenda-theme', root.dataset.theme);
  }));
  document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => button.addEventListener('click', () => document.getElementById('sidebar')?.classList.toggle('open')));
  document.querySelectorAll('[data-dismiss]').forEach((button) => button.addEventListener('click', () => button.parentElement.remove()));
  document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
    const input = button.parentElement.querySelector('input'); input.type = input.type === 'password' ? 'text' : 'password';
  }));
  document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  document.querySelectorAll('[data-role-select]').forEach((select) => {
    const link = select.form.querySelector('[data-professional-link]');
    const sync = () => { link.hidden = select.value !== 'profissional'; link.querySelector('select').required = select.value === 'profissional'; };
    select.addEventListener('change', sync); sync();
  });

  const base = document.body.dataset.baseUrl || '';
  const calendarEl = document.querySelector('[data-calendar]');
  if (calendarEl && window.FullCalendar) {
    const filters = document.querySelector('[data-calendar-filters]');
    const formatEventTime = (date) => new Intl.DateTimeFormat('pt-BR', {hour:'2-digit', minute:'2-digit', hour12:false}).format(date);
    const renderCalendarEvent = (info) => {
      const props = info.event.extendedProps;
      const content = document.createElement('div');
      content.className = 'calendar-event-content';

      const heading = document.createElement('div');
      heading.className = 'calendar-event-heading';
      const time = document.createElement('span');
      time.className = 'calendar-event-time';
      time.textContent = info.event.end
        ? `${formatEventTime(info.event.start)}–${formatEventTime(info.event.end)}`
        : formatEventTime(info.event.start);
      const status = document.createElement('span');
      status.className = `calendar-event-status status-${props.status}`;
      status.textContent = props.statusLabel;
      heading.append(time, status);

      const client = document.createElement('strong');
      client.className = 'calendar-event-client';
      client.textContent = props.cliente;
      const details = document.createElement('span');
      details.className = 'calendar-event-details';
      details.textContent = `${props.servico} · ${props.profissional}`;
      content.append(heading, client, details);
      return {domNodes:[content]};
    };
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: window.innerWidth < 700 ? 'timeGridDay' : 'timeGridWeek', locale: 'pt-br', height: 'auto', nowIndicator: true,
      slotMinTime: '06:00:00', slotMaxTime: '22:00:00', slotDuration: '00:15:00', slotLabelInterval: '00:30:00', allDaySlot: false,
      eventMinHeight: 54, eventShortHeight: 54, eventDisplay: 'block', displayEventTime: false, dayMaxEvents: true,
      headerToolbar: {left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay'},
      buttonText: {today:'Hoje',month:'Mês',week:'Semana',day:'Dia'},
      events: (info, success, failure) => {
        const query = new URLSearchParams({start:info.startStr,end:info.endStr});
        filters?.querySelectorAll('select').forEach((field) => { if (field.value) query.set(field.name, field.value); });
        fetch(`${base}/api/calendario?${query}`, {headers:{Accept:'application/json'}}).then((r)=>r.ok?r.json():Promise.reject(r)).then(success).catch(failure);
      },
      eventContent: renderCalendarEvent,
      eventClick: (info) => { window.location.href = `${base}/agendamentos/${encodeURIComponent(info.event.id)}`; },
      eventDidMount: (info) => {
        const props = info.event.extendedProps;
        info.el.title = `${props.cliente} · ${props.servico} com ${props.profissional} · ${props.statusLabel}`;
        info.el.setAttribute('aria-label', info.el.title);
      }
    });
    calendar.render();
    filters?.querySelectorAll('select').forEach((field) => field.addEventListener('change', () => calendar.refetchEvents()));
  }

  const appointmentForm = document.querySelector('[data-appointment-form]');
  if (appointmentForm) {
    const service = appointmentForm.querySelector('[name=servico_id]');
    const professional = appointmentForm.querySelector('[name=profissional_id]');
    const date = appointmentForm.querySelector('[name=data]');
    const slot = appointmentForm.querySelector('[name=inicio]');
    const load = async () => {
      slot.innerHTML = '<option value="">Carregando horários…</option>'; slot.disabled = true;
      if (!service.value || !professional.value || !date.value) { slot.innerHTML='<option value="">Selecione serviço, profissional e data</option>'; return; }
      const q = new URLSearchParams({servico_id:service.value,profissional_id:professional.value,data:date.value});
      const response=await fetch(`${base}/api/disponibilidade?${q}`,{headers:{Accept:'application/json'}}); const json=await response.json();
      slot.innerHTML = json.data?.length ? '<option value="">Escolha um horário</option>'+json.data.map((item)=>`<option value="${item.value}">${item.label} — ${item.end}</option>`).join('') : '<option value="">Nenhum horário livre</option>'; slot.disabled=false;
    };
    [service,professional,date].forEach((field)=>field?.addEventListener('change',load));
    const newClient=appointmentForm.querySelector('[data-new-client]'); const client=appointmentForm.querySelector('[name=cliente_id]');
    client?.addEventListener('change',()=>{newClient.hidden=Boolean(client.value);newClient.querySelectorAll('input').forEach((input)=>input.required=!client.value&&input.name!=='cliente_email');});
  }
})();
