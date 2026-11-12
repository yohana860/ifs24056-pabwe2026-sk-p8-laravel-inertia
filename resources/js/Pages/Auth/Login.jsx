import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Spinner from '../../components/Spinner';
import PublicLayout from '../../Layouts/PublicLayout';

/**
 * Form login memakai POST HTML biasa (navigasi penuh), bukan XHR Inertia, agar sesi/cookie
 * sudah tersimpan saat halaman berikutnya dibuka (penting untuk robot uji seperti Delcom Grading).
 */
export default function Login({ login = '' }) {
    const { errors, csrf_token } = usePage().props;
    const [processing, setProcessing] = useState(false);

    return (
        <PublicLayout>
            <Head title="Masuk" />
            <div className="mx-auto w-full max-w-md px-4 py-16">
                <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h1 className="font-serif text-3xl font-bold text-slate-900">Masuk</h1>
                    <p className="mt-1 text-sm text-slate-500">Gunakan username atau email Anda.</p>

                    <form method="post" action="/login" onSubmit={() => setProcessing(true)} className="mt-6 space-y-4">
                        <input type="hidden" name="_token" value={csrf_token} />
                        <div>
                            <label htmlFor="login-username" className="mb-1 block text-sm font-medium">Username / Email</label>
                            <input id="login-username" name="login" type="text" autoComplete="username" defaultValue={login} required className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                            {errors.login && <p className="mt-1 text-sm text-red-600">{errors.login}</p>}
                        </div>
                        <div>
                            <label htmlFor="login-password" className="mb-1 block text-sm font-medium">Password</label>
                            <input id="login-password" name="password" type="password" autoComplete="current-password" required className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                            {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                        </div>
                        <label htmlFor="login-remember" className="flex items-center gap-2 text-sm text-slate-600">
                            <input id="login-remember" name="remember" value="1" type="checkbox" />
                            Ingat saya
                        </label>
                        <button id="login-submit" type="submit" disabled={processing} className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 font-semibold text-white hover:bg-primary-dark disabled:opacity-60">
                            {processing && <Spinner />}
                            Masuk
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-slate-500">
                        Belum punya akun?{' '}
                        <Link href="/register" className="font-semibold text-primary hover:underline">Daftar</Link>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
}
