import { router } from '@inertiajs/react';
import { useState } from 'react';
import { xsrfToken } from '../lib/format';
import FullScreenModal from './FullScreenModal';
import Spinner from './Spinner';

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';

const setBusy = (value) => window.dispatchEvent(new CustomEvent('app:busy', { detail: value }));

/** Generator artikel OpenAI: API key hanya ada di server; browser memanggil endpoint Laravel. */
export default function AiGeneratorModal({ open, categories, limits, onClose }) {
    const [topic, setTopic] = useState('');
    const [category, setCategory] = useState('');
    const [quantity, setQuantity] = useState(limits.min);
    const [items, setItems] = useState([]);
    const [selected, setSelected] = useState([]);
    const [error, setError] = useState('');
    const [generating, setGenerating] = useState(false);
    const [saving, setSaving] = useState(false);

    const quantityNumber = Number(quantity);
    const quantityInvalid = !Number.isInteger(quantityNumber) || quantityNumber < limits.min || quantityNumber > limits.max;

    const generate = async () => {
        setError('');

        if (topic.trim().length < 3) {
            setError('Topik minimal 3 karakter.');
            return;
        }
        if (quantityInvalid) {
            setError(`Jumlah artikel harus antara ${limits.min} dan ${limits.max}.`);
            return;
        }

        setGenerating(true);
        setBusy(true);

        try {
            const response = await fetch('/dashboard/posts/ai-generate', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ topic, quantity: quantityNumber, category: category || null }),
            });
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstValidation = body.errors ? Object.values(body.errors)[0]?.[0] : null;
                throw new Error(firstValidation || body.message || 'Gagal menghasilkan artikel.');
            }

            setItems(body.items);
            setSelected(body.items.map((_, index) => index));
        } catch (e) {
            setError(e.message || 'Terjadi kesalahan jaringan.');
        } finally {
            setGenerating(false);
            setBusy(false);
        }
    };

    const toggle = (index) => setSelected((current) => (current.includes(index) ? current.filter((i) => i !== index) : [...current, index]));

    const save = () => {
        router.post(
            '/dashboard/posts/ai-save',
            { items: selected.map((index) => items[index]) },
            {
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                onSuccess: () => onClose(),
            },
        );
    };

    return (
        <FullScreenModal open={open} title="AI Generator Artikel" onClose={onClose} onSubmit={save} processing={saving} submitLabel={`Simpan ${selected.length} sebagai draf`} submitDisabled={selected.length === 0}>
            <div className="space-y-5">
                <div className="grid gap-5 sm:grid-cols-3">
                    <div className="sm:col-span-3">
                        <label htmlFor="ai-topic" className="mb-1 block text-sm font-medium">Topik</label>
                        <input id="ai-topic" type="text" className={inputClass} value={topic} onChange={(e) => setTopic(e.target.value)} placeholder="Contoh: tips belajar pemrograman untuk pemula" />
                    </div>
                    <div className="sm:col-span-2">
                        <label htmlFor="ai-category" className="mb-1 block text-sm font-medium">Kategori (opsional)</label>
                        <select id="ai-category" className={inputClass} value={category} onChange={(e) => setCategory(e.target.value)}>
                            <option value="">Biarkan AI memilih</option>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>{c.value}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="ai-quantity" className="mb-1 block text-sm font-medium">Jumlah artikel ({limits.min}-{limits.max})</label>
                        <input id="ai-quantity" type="number" min={limits.min} max={limits.max} step={1} className={inputClass} value={quantity} onChange={(e) => setQuantity(e.target.value)} />
                    </div>
                </div>

                <button type="button" onClick={generate} disabled={generating} className="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark disabled:opacity-60">
                    {generating && <Spinner />}
                    {generating ? 'Menghasilkan...' : 'Generate AI'}
                </button>

                {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</p>}

                {items.length > 0 && (
                    <ul className="space-y-3">
                        {items.map((item, index) => (
                            <li key={index} className="rounded-xl border border-slate-200 bg-white p-4">
                                <label className="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" className="mt-1" checked={selected.includes(index)} onChange={() => toggle(index)} />
                                    <span>
                                        <span className="block font-semibold text-slate-900">{item.title}</span>
                                        <span className="text-xs text-primary">{item.category}</span>
                                        {item.excerpt && <span className="mt-1 block text-sm text-slate-600">{item.excerpt}</span>}
                                    </span>
                                </label>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </FullScreenModal>
    );
}
