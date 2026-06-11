export function toIsoDateTime(date, time = '00:00:00') {
    const d = date instanceof Date
        ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
        : String(date).slice(0, 10)
    const t = time.length === 5 ? `${time}:00` : time
    return `${d}T${t}`
}
