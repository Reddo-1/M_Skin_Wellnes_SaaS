export function pad(value: number): string {
  return value.toString().padStart(2, '0');
}

export function formatLocalDate(value: Date): string {
  return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
}

//ISO 8601 con el offset local del navegador: preserva la hora de pared para que el back
//(guards de solape/descanso) y FullCalendar interpreten la misma hora local
export function toOffsetIso(value: Date): string {
  const offsetMinutes = -value.getTimezoneOffset();
  const sign = offsetMinutes >= 0 ? '+' : '-';
  const abs = Math.abs(offsetMinutes);
  const stamp = `${formatLocalDate(value)}T${pad(value.getHours())}:${pad(value.getMinutes())}:00`;
  return `${stamp}${sign}${pad(Math.floor(abs / 60))}:${pad(abs % 60)}`;
}
