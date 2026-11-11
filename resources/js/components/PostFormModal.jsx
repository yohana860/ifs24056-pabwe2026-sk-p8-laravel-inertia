import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import FullScreenModal from './FullScreenModal';
import MarkdownEditor from './MarkdownEditor';
import Spinner from './Spinner';

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';

function Field({ label, error, children, htmlFor }) {
    return (
        <div>
            <label htmlFor={htmlFor} className="mb-1 block text-sm font-medium text-slate-700">{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

/** Modal full screen untuk Tambah (post === null) dan Ubah artikel. */
export default function PostFormModal({ open, post, categories, onClose }) {
    const isEdit = post !== null;
    const form = useForm({
        title: post?.title ?? '',
        category: post?.category ?? categories[0].value,
        excerpt: post?.excerpt ?? '',
        content: post?.content ?? '',
        is_published: post?.is_published ?? false,
        cover: null,
    });
    const [removing, setRemoving] = useState(false);

    const submit = () => {
        const options = { forceFormData: true, preserveScroll: true, onSuccess: () => onClose() };

        if (isEdit) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(`/dashboard/posts/${post.id}`, options);
        } else {
            form.post('/dashboard/posts', options);
        }
    };

    const removeCover = () => {
        router.delete(`/dashboard/posts/${post.id}/cover`, {
            preserveScroll: true,
            onStart: () => setRemoving(true),
            onFinish: () => setRemoving(false),
        });
    };

    return (
        <FullScreenModal open={open} title={isEdit ? 'Ubah Artikel' : 'Tambah Artikel'} onClose={onClose} onSubmit={submit} processing={form.processing}>
            <div className="space-y-5">
                <Field label="Judul" htmlFor="post-title" error={form.errors.title}>
                    <input id="post-title" type="text" className={inputClass} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                </Field>

                <div className="grid gap-5 sm:grid-cols-2">
                    <Field label="Kategori" htmlFor="post-category" error={form.errors.category}>
                        <select id="post-category" className={inputClass} value={form.data.category} onChange={(e) => form.setData('category', e.target.value)}>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>{c.value} — {c.description}</option>
                            ))}
                        </select>
                    </Field>
                    <Field label="Status" htmlFor="post-status" error={form.errors.is_published}>
                        <select id="post-status" className={inputClass} value={form.data.is_published ? '1' : '0'} onChange={(e) => form.setData('is_published', e.target.value === '1')}>
                            <option value="0">Draft</option>
                            <option value="1">Published</option>
                        </select>
                    </Field>
                </div>

                <Field label="Ringkasan (opsional, 1-2 kalimat)" htmlFor="post-excerpt" error={form.errors.excerpt}>
                    <textarea id="post-excerpt" rows={2} className={inputClass} value={form.data.excerpt} onChange={(e) => form.setData('excerpt', e.target.value)} />
                </Field>

                <Field label="Cover (JPG/PNG/WEBP, maks 2 MB)" htmlFor="post-cover" error={form.errors.cover}>
                    <input id="post-cover" type="file" accept="image/png,image/jpeg,image/webp" onChange={(e) => form.setData('cover', e.target.files[0] ?? null)} className="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary-light file:px-4 file:py-2 file:font-medium file:text-primary" />
                    {isEdit && post.cover_url && (
                        <div className="mt-3 flex items-center gap-3">
                            <img src={post.cover_url} alt="Cover saat ini" className="h-16 w-24 rounded-lg object-cover" />
                            <button type="button" onClick={removeCover} disabled={removing} className="inline-flex items-center gap-2 text-sm font-medium text-red-600 hover:underline disabled:opacity-60">
                                {removing && <Spinner />}
                                Hapus cover
                            </button>
                        </div>
                    )}
                </Field>

                <Field label="Isi artikel (Markdown)" error={form.errors.content}>
                    <MarkdownEditor value={form.data.content} onChange={(value) => form.setData('content', value)} />
                </Field>
            </div>
        </FullScreenModal>
    );
}
