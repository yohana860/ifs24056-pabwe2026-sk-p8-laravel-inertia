import { Link, router, usePage } from '@inertiajs/react';
import FlashMessage from '../components/FlashMessage';
import { HomeIcon, LoginIcon, LogoutIcon, UserPlusIcon } from '../components/Icons';
import LoadingIndicator from '../components/LoadingIndicator';

/**
 * Layout halaman publik: TANPA sidebar, Sticky Top Navbar, tata letak membaca leluasa,
 * dan footer editorial. Seluruh halaman publik menyediakan tautan Login & Register.
 */
export default function PublicLayout({ children }) {
    const { auth, appName } = usePage().props;
    const user = auth.user;

    return (
        <div className="flex min-h-screen flex-col">
            <LoadingIndicator />

            <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
                <nav className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6" aria-label="Navigasi utama">
                    <div className="flex items-center gap-6">
                        <Link href="/" className="flex items-center gap-2 text-lg font-bold tracking-tight text-slate-900">
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-sm font-black text-white">B</span>
                            {appName}
                        </Link>
                        <div className="hidden items-center gap-1 sm:flex">
                            <Link href="/" className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                Beranda
                            </Link>
                            <Link href="/articles" className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                Artikel
                            </Link>
                        </div>
                    </div>

                    {user ? (
                        <div className="flex items-center gap-2">
                            <Link
                                href={user.is_admin ? '/admin/users' : '/dashboard/posts'}
                                className="flex items-center gap-2 rounded-full border border-slate-200 py-1 pl-1 pr-3 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >
                                {user.profile_photo_url ? (
                                    <img src={user.profile_photo_url} alt={user.name} className="h-7 w-7 rounded-full object-cover" />
                                ) : (
                                    <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">{user.name.charAt(0).toUpperCase()}</span>
                                )}
                                <span className="hidden sm:inline">{user.is_admin ? 'Panel Admin' : 'Dashboard Penulis'}</span>
                            </Link>
                            <button type="button" onClick={() => router.post('/logout')} aria-label="Keluar" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                                <LogoutIcon />
                            </button>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2">
                            <Link href="/login" className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                <LoginIcon className="h-4 w-4" />
                                Masuk
                            </Link>
                            <Link href="/register" className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                                <UserPlusIcon className="h-4 w-4" />
                                Daftar
                            </Link>
                        </div>
                    )}
                </nav>
            </header>

            <main className="flex-1">
                <div className="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6">
                    <FlashMessage />
                </div>
                {children}
            </main>

            <footer className="mt-16 bg-slate-900 text-slate-300">
                <div className="mx-auto grid w-full max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-2">
                    <div>
                        <p className="font-serif text-2xl font-bold text-white">{appName}</p>
                        <p className="mt-3 max-w-md text-sm leading-relaxed text-slate-400">
                            Ruang menulis dan membaca gagasan. Bagikan tulisanmu, temukan perspektif baru, dan tumbuh bersama komunitas penulis.
                        </p>
                    </div>
                    <nav aria-label="Navigasi footer" className="flex flex-wrap items-start gap-x-8 gap-y-3 text-sm md:justify-end">
                        <Link href="/" className="inline-flex items-center gap-1.5 hover:text-white">
                            <HomeIcon className="h-4 w-4" />
                            Beranda Publik
                        </Link>
                        <Link href="/login" className="inline-flex items-center gap-1.5 hover:text-white">
                            <LoginIcon className="h-4 w-4" />
                            Halaman Login
                        </Link>
                        <Link href="/register" className="inline-flex items-center gap-1.5 hover:text-white">
                            <UserPlusIcon className="h-4 w-4" />
                            Halaman Register
                        </Link>
                    </nav>
                </div>
                <div className="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
                    © {new Date().getFullYear()} {appName}. Seluruh hak cipta dilindungi.
                </div>
            </footer>
        </div>
    );
}
