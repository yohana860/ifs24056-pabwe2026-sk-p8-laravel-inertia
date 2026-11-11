import { useEffect } from 'react';
import { XIcon } from './Icons';
import Spinner from './Spinner';

/**
 * Modal Tambah/Ubah FULL SCREEN.
 * - Header: latar kontras (indigo gelap), Footer: latar kontras berbeda (slate gelap).
 * - Tombol Batal & Simpan berada di sisi KANAN footer.
 */
export default function FullScreenModal({
    open,
    title,
    onClose,
    onSubmit,
    processing = false,
    submitLabel = 'Simpan',
    submitDisabled = false,
    children,
}) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKey = (event) => {
            if (event.key === 'Escape' && !processing) {
                onClose();
            }
        };
        window.addEventListener('keydown', onKey);

        return () => {
            document.body.style.overflow = previous;
            window.removeEventListener('keydown', onKey);
        };
    }, [open, processing, onClose]);

    if (!open) {
        return null;
    }

    const submit = (event) => {
        event.preventDefault();
        onSubmit();
    };

    return (
        <div data-testid="fullscreen-modal" role="dialog" aria-modal="true" aria-label={title} className="fixed inset-0 z-50 flex h-dvh w-screen flex-col bg-white">
            <form onSubmit={submit} className="flex h-full min-h-0 flex-col" noValidate>
                <header data-testid="modal-header" className="flex shrink-0 items-center justify-between gap-4 bg-indigo-700 px-4 py-4 text-white sm:px-8">
                    <h2 className="truncate text-lg font-semibold">{title}</h2>
                    <button type="button" onClick={onClose} disabled={processing} aria-label="Tutup" className="rounded-lg p-1.5 hover:bg-white/15 disabled:opacity-50">
                        <XIcon />
                    </button>
                </header>

                <div className="min-h-0 flex-1 overflow-y-auto overflow-x-hidden bg-slate-50 px-4 py-6 sm:px-8">
                    <div className="mx-auto w-full max-w-5xl">{children}</div>
                </div>

                <footer data-testid="modal-footer" className="flex shrink-0 flex-wrap items-center justify-end gap-3 bg-slate-800 px-4 py-4 sm:px-8">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        className="rounded-lg border border-slate-500 px-5 py-2 text-sm font-medium text-slate-100 hover:bg-slate-700 disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={processing || submitDisabled}
                        className="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-400 disabled:opacity-60"
                    >
                        {processing && <Spinner />}
                        {submitLabel}
                    </button>
                </footer>
            </form>
        </div>
    );
}
