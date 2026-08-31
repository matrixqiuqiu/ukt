export function formatRupiah(value) {
    if (value === null || value === undefined) return 'Rp 0';
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function getAppTimezone() {
    if (typeof document !== 'undefined') {
        const meta = document.querySelector('meta[name="app-timezone"]');
        if (meta?.content) return meta.content;
    }
    return 'Asia/Makassar';
}

function getTimezoneAbbr(tz) {
    const map = { 'Asia/Jakarta': 'WIB', 'Asia/Makassar': 'WITA', 'Asia/Jayapura': 'WIT' };
    return map[tz] || tz;
}

export function formatDate(dateStr, tz) {
    if (!dateStr) return '-';
    const timezone = tz || getAppTimezone();
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: timezone,
        });
    } catch {
        const part = String(dateStr).split('T')[0];
        if (/^\d{4}-\d{2}-\d{2}$/.test(part)) {
            const [y, m, d] = part.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }
        return String(dateStr);
    }
}

export function formatDateTime(dateStr, tz) {
    if (!dateStr) return '-';
    const timezone = tz || getAppTimezone();
    const abbr = getTimezoneAbbr(timezone);
    try {
        return new Date(dateStr).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: timezone,
            hour12: false,
        }) + ' ' + abbr;
    } catch {
        return formatDate(dateStr, timezone);
    }
}
