(() => {
  const toast = message => {
    document.querySelector('.page-toast')?.remove();
    const node = document.createElement('div');
    node.className = 'page-toast'; node.setAttribute('role', 'status'); node.textContent = message;
    document.body.append(node); window.setTimeout(() => node.remove(), 3800);
  };
  const filterTable = (table, query = '', severity = '') => {
    table?.querySelectorAll('tbody tr').forEach(row => {
      row.hidden = !row.textContent.toLowerCase().includes(query.trim().toLowerCase()) || (severity && !row.lastElementChild.textContent.trim().includes(severity));
    });
  };
  const globalSearch = document.getElementById('event-search');
  globalSearch?.addEventListener('input', () => filterTable(document.querySelector('#log-table, #events-table'), globalSearch.value));
  document.querySelectorAll('.table-search').forEach(input => {
    const table = document.getElementById(input.dataset.table);
    input.addEventListener('input', () => { if (table) table.dataset.query = input.value; filterTable(table, input.value, table?.dataset.severity || ''); });
  });
  document.querySelectorAll('[data-filter-severity]').forEach(select => {
    const table = document.getElementById(select.dataset.filterSeverity);
    select.addEventListener('change', () => { if (table) table.dataset.severity = select.value; filterTable(table, table?.dataset.query || '', select.value); });
  });
  document.querySelectorAll('[data-show]').forEach(button => button.addEventListener('click', () => {
    const form = document.getElementById(button.dataset.show);
    form?.classList.toggle('hidden');
    form?.querySelector('input')?.focus();
  }));
  document.querySelectorAll('[data-message]').forEach(button => button.addEventListener('click', () => toast(button.dataset.message)));
  document.querySelectorAll('.inline-form').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault(); toast('This is a prototype. Changes are not saved until the database module is connected.'); form.reset(); form.classList.add('hidden');
  }));
  document.querySelectorAll('.switch input').forEach(input => input.addEventListener('change', () => toast('Rule state changed for this preview only; it is not saved.')));
  document.querySelectorAll('[data-export]').forEach(button => button.addEventListener('click', () => {
    const table = document.getElementById(button.dataset.export);
    if (!table) return;
    const csv = [...table.rows].map(row => [...row.cells].map(cell => {
      let value = cell.innerText.trim().replace(/\s+/g, ' ');
      if (/^\s*[=+\-@]/.test(value)) value = "'" + value;
      return `"${value.replace(/"/g, '""')}"`;
    }).join(',')).join('\r\n');
    const url = URL.createObjectURL(new Blob([csv], {type: 'text/csv;charset=utf-8'}));
    const link = document.createElement('a'); link.href = url; link.download = `${button.dataset.export}.csv`; link.click(); URL.revokeObjectURL(url);
  }));
  document.getElementById('refresh')?.addEventListener('click', event => {
    const button = event.currentTarget;
    button.disabled = true; button.querySelector('span').textContent = 'Updated';
    document.getElementById('updated-at').textContent = new Intl.DateTimeFormat([], {hour: 'numeric', minute: '2-digit'}).format(new Date());
    toast('Preview refreshed. Live portal data is not connected.');
    window.setTimeout(() => { button.disabled = false; button.querySelector('span').textContent = 'Refresh'; }, 1200);
  });
})();
