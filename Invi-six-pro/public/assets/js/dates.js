(() => {
 'use strict';
 function instant(value) {
  if (!value) return null;
  const text = String(value).replace(' ', 'T');
  const explicit = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(text);
  const date = new Date(text + (explicit ? '' : 'Z'));
  return Number.isFinite(date.getTime()) ? date : null;
 }
 function format(value, mode = 'datetime') {
  if (/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return value.split('-').reverse().join('/');
  const date = instant(value); if (!date) return '—';
  const options = {timeZone:'America/Sao_Paulo',hourCycle:'h23'};
  if (mode !== 'time') Object.assign(options,{day:'2-digit',month:'2-digit',year:'numeric'});
  if (mode !== 'date') Object.assign(options,{hour:'2-digit',minute:'2-digit'});
  return new Intl.DateTimeFormat('pt-BR',options).format(date).replace(', ', ' ');
 }
 window.LobbyDates = {instant,format};
})();
