import { PhotoIcon } from './Icons';

/** Cover artikel; menampilkan placeholder gradien bila belum ada gambar. */
export default function CoverThumb({ post, className = 'h-16 w-24' }) {
    if (post.cover_url) {
        return <img src={post.cover_url} alt={`Cover ${post.title}`} loading="lazy" className={`${className} shrink-0 rounded-lg object-cover`} />;
    }

    return (
        <div className={`${className} flex shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-100 to-violet-200 text-indigo-400`}>
            <PhotoIcon className="h-6 w-6" />
        </div>
    );
}
