import { Link } from '@inertiajs/react';

const decode = (label) =>
    label
        .replace('&laquo; Previous', '‹ Sebelumnya')
        .replace('Next &raquo;', 'Berikutnya ›')
        .replace(/&laquo;|&raquo;/g, '');

/** Navigasi halaman dari objek paginator Laravel (`links`). */
export default function Pagination({ links = [] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav aria-label="Paginasi" className="mt-6 flex flex-wrap items-center justify-center gap-1.5">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        className={`rounded-lg border px-3 py-1.5 text-sm ${link.active ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100'}`}
                    >
                        {decode(link.label)}
                    </Link>
                ) : (
                    <span key={index} className="rounded-lg border border-slate-100 bg-slate-50 px-3 py-1.5 text-sm text-slate-500">
                        {decode(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}
