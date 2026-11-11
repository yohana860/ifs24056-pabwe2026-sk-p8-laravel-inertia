import { Head, Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AiGeneratorModal from '../../components/AiGeneratorModal';
import ConfirmDialog from '../../components/ConfirmDialog';
import CoverThumb from '../../components/CoverThumb';
import { CheckCircleIcon, ClockIcon, DocumentIcon, DownloadIcon, EyeIcon, PencilIcon, PlusIcon, SearchIcon, SparklesIcon, TrashIcon, UploadIcon } from '../../components/Icons';
import KebabMenu from '../../components/KebabMenu';
import Pagination from '../../components/Pagination';
import PostFormModal from '../../components/PostFormModal';
import Spinner from '../../components/Spinner';
import StatCard from '../../components/StatCard';
import StatusBadge from '../../components/StatusBadge';
import AppLayout from '../../Layouts/AppLayout';
import { formatDate } from '../../lib/format';

export default function Index({ posts, stats, filters, categories, ai }) {
    const [formPost, setFormPost] = useState(undefined); // undefined = tertutup, null = tambah, objek = ubah
    const [aiOpen, setAiOpen] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);
    const [importing, setImporting] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');
    const fileInput = useRef(null);

    const searchPosts = (event) => {
        event.preventDefault();
        router.get('/dashboard/posts', { search }, { preserveState: true });
    };

    const confirmDelete = () => {
        router.delete(`/dashboard/posts/${toDelete.id}`, {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    const togglePublish = (post) => {
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, {
            preserveScroll: true,
            onStart: () => setTogglingId(post.id),
            onFinish: () => setTogglingId(null),
        });
    };

    const importFile = (event) => {
        const file = event.target.files[0];
        if (!file) {
            return;
        }
        router.post('/dashboard/posts/import', { file }, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setImporting(true),
            onFinish: () => {
                setImporting(false);
                fileInput.current.value = '';
            },
        });
    };

    return (
        <AppLayout title="Artikel Saya">
            <Head title="Artikel Saya" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="font-serif text-2xl font-bold text-slate-900">Artikel Saya</h1>
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => setFormPost(null)} className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                        <PlusIcon className="h-4 w-4" />
                        Tambah Artikel
                    </button>
                    <button type="button" onClick={() => setAiOpen(true)} className="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                        <SparklesIcon className="h-4 w-4" />
                        AI Generator
                    </button>
                    <button type="button" onClick={() => fileInput.current.click()} disabled={importing} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60">
                        {importing ? <Spinner /> : <UploadIcon className="h-4 w-4" />}
                        Impor Excel
                    </button>
                    <input ref={fileInput} type="file" accept=".xlsx,.xls" className="hidden" onChange={importFile} />
                    <a href="/dashboard/posts/export" className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <DownloadIcon className="h-4 w-4" />
                        Ekspor Excel
                    </a>
                </div>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard label="Total Artikel" value={stats.total} icon={DocumentIcon} tone="indigo" />
                <StatCard label="Total Selesai" value={stats.published} icon={CheckCircleIcon} tone="emerald" />
                <StatCard label="Belum Selesai" value={stats.draft} icon={ClockIcon} tone="amber" />
            </div>

            <form onSubmit={searchPosts} className="relative mb-4">
                <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari artikel saya..." className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
            </form>

            {/* List view (bukan card) */}
            <ul data-testid="post-list" className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
                {posts.data.length === 0 && <li className="p-10 text-center text-slate-500">Belum ada artikel. Klik "Tambah Artikel" untuk mulai menulis.</li>}

                {posts.data.map((post) => (
                    <li key={post.id} className="flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap">
                        <CoverThumb post={post} className="hidden h-14 w-20 sm:flex" />
                        <div className="min-w-0 flex-1">
                            <Link href={`/dashboard/posts/${post.id}`} className="block truncate font-semibold text-slate-900 hover:text-primary">{post.title}</Link>
                            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                <StatusBadge published={post.is_published} />
                                <span>{post.category}</span>
                                <span>{formatDate(post.created_at)}</span>
                            </div>
                        </div>
                        <button type="button" onClick={() => togglePublish(post)} disabled={togglingId === post.id} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60">
                            {togglingId === post.id && <Spinner className="h-3 w-3" />}
                            {post.is_published ? 'Jadikan Draf' : 'Publikasikan'}
                        </button>
                        <KebabMenu
                            items={[
                                { label: 'Detail', icon: EyeIcon, tone: 'primary', href: `/dashboard/posts/${post.id}` },
                                { label: 'Ubah', icon: PencilIcon, tone: 'primary', onClick: () => setFormPost(post) },
                                { label: 'Hapus', icon: TrashIcon, tone: 'danger', onClick: () => setToDelete(post) },
                            ]}
                        />
                    </li>
                ))}
            </ul>

            <Pagination links={posts.links} />

            {formPost !== undefined && <PostFormModal key={formPost?.id ?? 'new'} open post={formPost} categories={categories} onClose={() => setFormPost(undefined)} />}
            {aiOpen && <AiGeneratorModal open categories={categories} limits={ai} onClose={() => setAiOpen(false)} />}
            <ConfirmDialog open={toDelete !== null} title="Hapus artikel?" message={`Artikel "${toDelete?.title}" akan dihapus permanen.`} processing={deleting} onConfirm={confirmDelete} onCancel={() => setToDelete(null)} />
        </AppLayout>
    );
}
