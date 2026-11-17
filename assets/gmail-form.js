function formatAppPassword(value) {
  return (value.replace(/\s/g, '').match(/.{1,4}/g) || []).join(' ');
}
if (typeof document !== 'undefined') {
  const input = document.getElementById('gmail-app-password');
  input.addEventListener('input', () => {
    const rawPosition = input.value.slice(0, input.selectionStart).replace(/\s/g, '').length;
    input.value = formatAppPassword(input.value);
    const position = Math.min(input.value.length, rawPosition + Math.max(0, Math.floor((rawPosition - 1) / 4)));
    input.setSelectionRange(position, position);
  });
  document.getElementById('gmail-show-password').addEventListener('click', event => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    event.currentTarget.textContent = visible ? 'Hide' : 'Show';
    event.currentTarget.setAttribute('aria-pressed', String(visible));
  });
  document.querySelector('.gmail-enable input').addEventListener('change', event => {
    document.querySelector('.gmail-switch-state').textContent = event.target.checked ? 'On' : 'Off';
  });
}
if (typeof module !== 'undefined') module.exports = { formatAppPassword };
