import { Head, Link } from '@inertiajs/react';
import { LoginIcon, UserPlusIcon } from '../../components/Icons';
import MarkdownViewer from '../../components/MarkdownViewer';
import PublicLayout from '../../Layouts/PublicLayout';
import { formatDate } from '../../lib/format';

export default function PostShow({ post, related }) {
    return (
        <PublicLayout>
            <Head title={post.title} />

            <article className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
                <Link href="/articles" className="text-sm font-medium text-primary hover:underline">
                    ← Semua artikel
                </Link>
                <span className="mt-6 block w-fit rounded-full bg-primary-light px-3 py-1 text-xs font-semibold text-primary">{post.category}</span>
                <h1 className="mt-3 font-serif text-4xl font-bold leading-tight text-slate-900">{post.title}</h1>
                <p className="mt-3 text-sm text-slate-500">
                    Oleh {post.user?.name} · {formatDate(post.published_at)}
                </p>

                {post.cover_url && <img src={post.cover_url} alt={`Cover ${post.title}`} className="mt-8 w-full rounded-2xl object-cover" />}

                <div className="mt-8">
                    <MarkdownViewer content={post.content} />
                </div>

                <aside className="mt-12 rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 p-8 text-center">
                    <h2 className="font-serif text-2xl font-bold text-slate-900">Suka tulisan ini? Bergabunglah dengan komunitas kami</h2>
                    <p className="mx-auto mt-2 max-w-md text-slate-600">Masuk untuk mulai menulis, atau daftar gratis sebagai penulis baru.</p>
                    <div className="mt-5 flex flex-wrap justify-center gap-3">
                        <Link href="/login" className="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 font-semibold text-white hover:bg-primary-dark">
                            <LoginIcon className="h-5 w-5" />
                            Masuk
                        </Link>
                        <Link href="/register" className="inline-flex items-center gap-2 rounded-lg border border-primary px-5 py-2.5 font-semibold text-primary hover:bg-white">
                            <UserPlusIcon className="h-5 w-5" />
                            Daftar
                        </Link>
                    </div>
                </aside>

                {related.length > 0 && (
                    <section className="mt-12">
                        <h2 className="mb-4 font-serif text-xl font-bold">Artikel terkait</h2>
                        <ul className="space-y-3">
                            {related.map((item) => (
                                <li key={item.id}>
                                    <Link href={`/articles/${item.slug}`} className="font-medium text-primary hover:underline">
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </article>
        </PublicLayout>
    );
}
