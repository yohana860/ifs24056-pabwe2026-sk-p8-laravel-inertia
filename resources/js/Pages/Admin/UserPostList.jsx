import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { DownloadIcon, SearchIcon } from '../../components/Icons';
import Pagination from '../../components/Pagination';
import ReadOnlyPostList from '../../components/ReadOnlyPostList';
import AppLayout from '../../Layouts/AppLayout';

export default function UserPostList({ owner, posts, stats, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get(`/admin/users/${owner.id}/posts`, { search }, { preserveState: true });
    };

    return (
        <AppLayout title={`Artikel ${owner.name}`}>
            <Head title={`Artikel ${owner.name}`} />
            <Link href="/admin/users" className="text-sm font-medium text-primary hover:underline">← Kembali ke pengguna</Link>
            <div className="mb-6 mt-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="font-serif text-2xl font-bold">Artikel {owner.name} <span className="text-sm font-normal text-slate-500">(read-only)</span></h1>
                    <p className="text-sm text-slate-500">@{owner.username} · {stats.total} artikel, {stats.published} terbit, {stats.draft} draf</p>
                </div>
                <a href={`/admin/users/${owner.id}/posts/export`} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
                    <DownloadIcon className="h-4 w-4" />
                    Ekspor ke Excel
                </a>
            </div>
            <form onSubmit={submit} className="relative mb-4">
                <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input type="search" aria-label="Cari artikel pengguna" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari artikel..." className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
            </form>
            <ReadOnlyPostList posts={posts} showAuthor={false} />
            <Pagination links={posts.links} />
        </AppLayout>
    );
}
