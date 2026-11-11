import { Head, Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import ConfirmDialog from '../../components/ConfirmDialog';
import { PencilIcon, TrashIcon } from '../../components/Icons';
import MarkdownViewer from '../../components/MarkdownViewer';
import PostFormModal from '../../components/PostFormModal';
import Spinner from '../../components/Spinner';
import StatusBadge from '../../components/StatusBadge';
import AppLayout from '../../Layouts/AppLayout';
import { formatDate } from '../../lib/format';

export default function Show({ post, categories }) {
    const [editing, setEditing] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [busy, setBusy] = useState(null);
    const coverInput = useRef(null);

    const run = (key, method, url, data = {}, options = {}) =>
        router[method](url, data, { preserveScroll: true, ...options, onStart: () => setBusy(key), onFinish: () => setBusy(null) });

    const uploadCover = (event) => {
        const file = event.target.files[0];
        if (file) {
            run('cover', 'post', `/dashboard/posts/${post.id}/cover`, { cover: file }, { forceFormData: true });
        }
    };

    return (
        <AppLayout title={post.title}>
            <Head title={post.title} />

            <Link href="/dashboard/posts" className="text-sm font-medium text-primary hover:underline">← Kembali ke daftar</Link>

            <div className="mt-4 flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h1 className="font-serif text-3xl font-bold text-slate-900">{post.title}</h1>
                    <div className="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <StatusBadge published={post.is_published} />
                        <span>{post.category}</span>
                        <span>Dibuat {formatDate(post.created_at)}</span>
                        {post.published_at && <span>Terbit {formatDate(post.published_at)}</span>}
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => run('publish', 'patch', `/dashboard/posts/${post.id}/publish`)} disabled={busy === 'publish'} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 disabled:opacity-60">
                        {busy === 'publish' && <Spinner />}
                        {post.is_published ? 'Jadikan Draf' : 'Publikasikan'}
                    </button>
                    <button type="button" onClick={() => setEditing(true)} className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                        <PencilIcon className="h-4 w-4" />
                        Ubah
                    </button>
                    <button type="button" onClick={() => setConfirming(true)} className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                        <TrashIcon className="h-4 w-4" />
                        Hapus
                    </button>
                </div>
            </div>

            <div className="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                {post.cover_url ? <img src={post.cover_url} alt={`Cover ${post.title}`} className="max-h-96 w-full rounded-xl object-cover" /> : <p className="py-6 text-center text-sm text-slate-500">Belum ada cover.</p>}
                <div className="mt-3 flex flex-wrap gap-2">
                    <button type="button" onClick={() => coverInput.current.click()} disabled={busy === 'cover'} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50 disabled:opacity-60">
                        {busy === 'cover' && <Spinner className="h-3 w-3" />}
                        {post.cover_url ? 'Ganti cover' : 'Unggah cover'}
                    </button>
                    <input ref={coverInput} type="file" accept="image/png,image/jpeg,image/webp" className="hidden" onChange={uploadCover} />
                    {post.cover_url && (
                        <button type="button" onClick={() => run('cover-del', 'delete', `/dashboard/posts/${post.id}/cover`)} disabled={busy === 'cover-del'} className="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-60">
                            {busy === 'cover-del' && <Spinner className="h-3 w-3" />}
                            Hapus cover
                        </button>
                    )}
                </div>
            </div>

            <article className="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
                <MarkdownViewer content={post.content} />
            </article>

            {editing && <PostFormModal key={post.updated_at} open post={post} categories={categories} onClose={() => setEditing(false)} />}
            <ConfirmDialog open={confirming} title="Hapus artikel?" message="Artikel ini akan dihapus permanen." processing={busy === 'delete'} onCancel={() => setConfirming(false)} onConfirm={() => run('delete', 'delete', `/dashboard/posts/${post.id}`)} />
        </AppLayout>
    );
}
