import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { DownloadIcon, EyeIcon, SearchIcon } from '../../components/Icons';
import Pagination from '../../components/Pagination';
import AppLayout from '../../Layouts/AppLayout';

export default function UserList({ users, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get('/admin/users', { search }, { preserveState: true });
    };

    return (
        <AppLayout title="Pengguna">
            <Head title="Pengguna" />
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="font-serif text-2xl font-bold">Pengguna</h1>
                <Link href="/admin/posts" className="text-sm font-medium text-primary hover:underline">Lihat semua artikel →</Link>
            </div>

            <form onSubmit={submit} className="relative mb-4">
                <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama, username, atau email..." className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
            </form>

            <ul className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
                {users.data.map((user) => (
                    <li key={user.id} className="flex flex-wrap items-center gap-4 p-4">
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-semibold">{user.name} <span className="font-normal text-slate-500">@{user.username}</span></p>
                            <p className="truncate text-xs text-slate-500">{user.email} · {user.role} · {user.posts_count} artikel ({user.published_posts_count} terbit)</p>
                        </div>
                        <Link href={`/admin/users/${user.id}/posts`} className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-primary hover:bg-primary-light">
                            <EyeIcon className="h-4 w-4" />
                            Artikel
                        </Link>
                        <a href={`/admin/users/${user.id}/posts/export`} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium hover:bg-slate-50">
                            <DownloadIcon className="h-4 w-4" />
                            Excel
                        </a>
                    </li>
                ))}
            </ul>
            <Pagination links={users.links} />
        </AppLayout>
    );
}
