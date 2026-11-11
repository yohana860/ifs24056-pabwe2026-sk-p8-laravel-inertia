const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export function formatDate(value) {
    if (!value) {
        return '-';
    }

    return dateFormatter.format(new Date(value));
}

/** Ambil token XSRF dari cookie untuk request fetch() ke endpoint JSON. */
export function xsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function categoryDescription(categories, value) {
    return categories.find((c) => c.value === value)?.description ?? value;
}
