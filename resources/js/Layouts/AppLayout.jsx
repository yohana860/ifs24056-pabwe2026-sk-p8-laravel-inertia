import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import FlashMessage from '../components/FlashMessage';
import { DocumentIcon, HomeIcon, LogoutIcon, MenuIcon, UserPlusIcon, UsersIcon, XIcon } from '../components/Icons';
import LoadingIndicator from '../components/LoadingIndicator';

function NavItem({ href, icon: Icon, children, active }) {
    return (
        <Link href={href} className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium ${active ? 'bg-white/15 text-white' : 'text-indigo-100 hover:bg-white/10'}`}>
            <Icon className="h-5 w-5" />
            {children}
        </Link>
    );
}

/** Layout area login (penulis & admin) dengan sidebar. */
export default function AppLayout({ title, children }) {
    const { auth, appName } = usePage().props;
    const { url: currentUrl } = usePage();
    const user = auth.user;
    const [open, setOpen] = useState(false);
    const is = (prefix) => currentUrl.startsWith(prefix);

    const sidebar = (
        <div className="flex h-full flex-col bg-indigo-900 p-4 text-white">
            <Link href="/" className="mb-6 flex items-center gap-2 px-2 text-lg font-bold">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-sm font-black text-indigo-900">B</span>
                {appName}
            </Link>

            <nav className="flex-1 space-y-1" aria-label="Navigasi dashboard">
                <p className="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-indigo-300">Penulis</p>
                <NavItem href="/dashboard/posts" icon={DocumentIcon} active={is('/dashboard')}>
                    Artikel Saya
                </NavItem>
                <NavItem href="/profile" icon={UserPlusIcon} active={is('/profile')}>
                    Profil
                </NavItem>

                {user.is_admin && (
                    <>
                        <p className="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-300">Admin</p>
                        <NavItem href="/admin/users" icon={UsersIcon} active={is('/admin/users')}>
                            Pengguna
                        </NavItem>
                        <NavItem href="/admin/posts" icon={DocumentIcon} active={is('/admin/posts')}>
                            Semua Artikel
                        </NavItem>
                    </>
                )}

                <p className="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-300">Publik</p>
                <NavItem href="/" icon={HomeIcon}>
                    Beranda Publik
                </NavItem>
            </nav>

            <div className="mt-4 border-t border-white/10 pt-4">
                <p className="truncate px-2 text-sm font-semibold">{user.name}</p>
                <p className="truncate px-2 text-xs text-indigo-300">@{user.username}</p>
                <button type="button" onClick={() => router.post('/logout')} className="mt-3 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-indigo-100 hover:bg-white/10">
                    <LogoutIcon className="h-4 w-4" />
                    Keluar
                </button>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen">
            <LoadingIndicator />

            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 md:block">{sidebar}</aside>

            {open && (
                <div className="fixed inset-0 z-40 md:hidden">
                    <div className="absolute inset-0 bg-slate-900/50" onClick={() => setOpen(false)} />
                    <div className="relative h-full w-64">
                        {sidebar}
                        <button type="button" onClick={() => setOpen(false)} aria-label="Tutup menu" className="absolute right-2 top-3 rounded-lg p-1.5 text-white hover:bg-white/10">
                            <XIcon />
                        </button>
                    </div>
                </div>
            )}

            <div className="md:pl-64">
                <header className="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur md:hidden">
                    <button type="button" onClick={() => setOpen(true)} aria-label="Buka menu" className="rounded-lg p-1.5 hover:bg-slate-100">
                        <MenuIcon />
                    </button>
                    <span className="font-semibold">{title}</span>
                </header>

                <main className="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                    <FlashMessage />
                    {children}
                </main>
            </div>
        </div>
    );
}
