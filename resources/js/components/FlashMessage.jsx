import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** Notifikasi flash (success/error) dari server; hilang otomatis setelah beberapa detik. */
export default function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(true);
    const message = flash?.success || flash?.error;

    useEffect(() => {
        setVisible(true);
        const timer = setTimeout(() => setVisible(false), 5000);

        return () => clearTimeout(timer);
    }, [flash?.success, flash?.error]);

    if (!message || !visible) {
        return null;
    }

    const isError = Boolean(flash.error);

    return (
        <div role="alert" className={`mb-4 flex items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm ${isError ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`}>
            <span>{message}</span>
            <button type="button" onClick={() => setVisible(false)} className="font-semibold" aria-label="Tutup notifikasi">
                ×
            </button>
        </div>
    );
}
