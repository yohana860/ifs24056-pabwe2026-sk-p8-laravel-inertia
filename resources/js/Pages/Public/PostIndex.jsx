import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import CoverThumb from '../../components/CoverThumb';
import { LoginIcon, SearchIcon, UserPlusIcon } from '../../components/Icons';
import Pagination from '../../components/Pagination';
import PublicLayout from '../../Layouts/PublicLayout';
import { categoryDescription, formatDate } from '../../lib/format';

function ArticleCard({ post, categories }) {
    return (
        <article className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
            <Link href={`/articles/${post.slug}`} className="block">
                <CoverThumb post={post} className="h-48 w-full !rounded-none" />
            </Link>
            <div className="flex flex-1 flex-col p-5">
                <span className="mb-2 w-fit rounded-full bg-primary-light px-2.5 py-0.5 text-xs font-semibold text-primary">{categoryDescription(categories, post.category)}</span>
                <h3 className="font-serif text-xl font-bold leading-snug text-slate-900">
                    <Link href={`/articles/${post.slug}`} className="hover:text-primary">
                        {post.title}
                    </Link>
                </h3>
                {post.excerpt && <p className="mt-2 line-clamp-3 text-sm text-slate-600">{post.excerpt}</p>}
                <p className="mt-auto pt-4 text-xs text-slate-500">
                    {post.user?.name} · {formatDate(post.published_at)}
                </p>
            </div>
        </article>
    );
}

export default function PostIndex({ hero, posts, categories, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get('/articles', { search, category }, { preserveState: true });
    };

    const pickCategory = (value) => {
        setCategory(value);
        router.get('/articles', { search, category: value }, { preserveState: true });
    };

    return (
        <PublicLayout>
            <Head title={hero ? 'Beranda' : 'Artikel'} />

            {hero && (
                <section className="bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-600 text-white">
                    <div className="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6">
                        <h1 className="font-serif text-4xl font-bold leading-tight sm:text-5xl">Tempat gagasan menemukan pembacanya</h1>
                        <p className="mx-auto mt-5 max-w-2xl text-lg text-indigo-100">
                            Baca artikel pilihan dari para penulis, atau bergabunglah dan terbitkan tulisanmu sendiri.
                        </p>
                        <div className="mt-8 flex flex-wrap justify-center gap-3">
                            <Link href="/login" className="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-semibold text-indigo-700 hover:bg-indigo-50">
                                <LoginIcon className="h-5 w-5" />
                                Masuk
                            </Link>
                            <Link href="/register" className="inline-flex items-center gap-2 rounded-lg border border-white/60 px-6 py-3 font-semibold text-white hover:bg-white/10">
                                <UserPlusIcon className="h-5 w-5" />
                                Daftar Penulis
                            </Link>
                        </div>
                    </div>
                </section>
            )}

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
                <form onSubmit={submit} className="mb-6 flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1">
                        <SearchIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari judul atau isi artikel..."
                            className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                        />
                    </div>
                    <button type="submit" className="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">
                        Cari
                    </button>
                </form>

                <div className="mb-8 flex flex-wrap gap-2">
                    <button type="button" onClick={() => pickCategory('')} className={`rounded-full border px-4 py-1.5 text-sm ${category === '' ? 'border-primary bg-primary text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'}`}>
                        Semua
                    </button>
                    {categories.map((c) => (
                        <button key={c.value} type="button" onClick={() => pickCategory(c.value)} className={`rounded-full border px-4 py-1.5 text-sm ${category === c.value ? 'border-primary bg-primary text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'}`}>
                            {c.value}
                        </button>
                    ))}
                </div>

                {posts.data.length === 0 ? (
                    <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">Belum ada artikel yang cocok.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post) => (
                            <ArticleCard key={post.id} post={post} categories={categories} />
                        ))}
                    </div>
                )}

                <Pagination links={posts.links} />
            </div>
        </PublicLayout>
    );
}
