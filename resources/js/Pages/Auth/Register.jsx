import { Head, Link, useForm } from '@inertiajs/react';
import Spinner from '../../components/Spinner';
import PublicLayout from '../../Layouts/PublicLayout';

const fields = [
    ['name', 'Nama lengkap', 'text', 'name'],
    ['username', 'Username', 'text', 'username'],
    ['email', 'Email', 'email', 'email'],
    ['password', 'Password', 'password', 'new-password'],
    ['password_confirmation', 'Konfirmasi password', 'password', 'new-password'],
];

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({ name: '', username: '', email: '', password: '', password_confirmation: '' });

    const submit = (event) => {
        event.preventDefault();
        post('/register');
    };

    return (
        <PublicLayout>
            <Head title="Daftar" />
            <div className="mx-auto w-full max-w-md px-4 py-16">
                <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h1 className="font-serif text-3xl font-bold text-slate-900">Daftar Penulis</h1>
                    <form onSubmit={submit} className="mt-6 space-y-4">
                        {fields.map(([name, label, type, autoComplete]) => (
                            <div key={name}>
                                <label htmlFor={`register-${name}`} className="mb-1 block text-sm font-medium">{label}</label>
                                <input id={`register-${name}`} name={name} type={type} autoComplete={autoComplete} value={data[name]} onChange={(e) => setData(name, e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                                {errors[name] && <p className="mt-1 text-sm text-red-600">{errors[name]}</p>}
                            </div>
                        ))}
                        <button id="register-submit" type="submit" disabled={processing} className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 font-semibold text-white hover:bg-primary-dark disabled:opacity-60">
                            {processing && <Spinner />}
                            Daftar
                        </button>
                    </form>
                    <p className="mt-6 text-center text-sm text-slate-500">
                        Sudah punya akun?{' '}
                        <Link href="/login" className="font-semibold text-primary underline">Masuk</Link>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
}
