import CoverThumb from './CoverThumb';
import StatusBadge from './StatusBadge';
import { formatDate } from '../lib/format';

/** Daftar artikel read-only untuk monitoring admin (tanpa tombol ubah/hapus/publikasi). */
export default function ReadOnlyPostList({ posts, showAuthor = true }) {
    return (
        <ul data-testid="readonly-post-list" className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
            {posts.data.length === 0 && <li className="p-10 text-center text-slate-500">Tidak ada artikel.</li>}
            {posts.data.map((post) => (
                <li key={post.id} className="flex items-center gap-4 p-4">
                    <CoverThumb post={post} className="hidden h-14 w-20 sm:flex" />
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-semibold text-slate-900">{post.title}</p>
                        <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            <StatusBadge published={post.is_published} />
                            <span>{post.category}</span>
                            {showAuthor && post.user && <span>Oleh {post.user.name}</span>}
                            <span>{formatDate(post.created_at)}</span>
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}
