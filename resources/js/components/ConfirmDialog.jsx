import Spinner from './Spinner';

/** Dialog konfirmasi kecil (khusus hapus) dengan spinner pada tombol Hapus. */
export default function ConfirmDialog({ open, title, message, confirmLabel = 'Hapus', processing = false, onConfirm, onCancel }) {
    if (!open) {
        return null;
    }

    return (
        <div role="alertdialog" aria-modal="true" aria-label={title} className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4">
            <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
                <p className="mt-2 text-sm text-slate-600">{message}</p>
                <div className="mt-6 flex justify-end gap-3">
                    <button type="button" onClick={onCancel} disabled={processing} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" onClick={onConfirm} disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-60">
                        {processing && <Spinner />}
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
