import { Head, useForm, usePage } from '@inertiajs/react';
import Spinner from '../../components/Spinner';
import AppLayout from '../../Layouts/AppLayout';

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';

export default function Edit({ stats }) {
    const { user } = usePage().props.auth;
    const form = useForm({
        name: user.name,
        username: user.username,
        email: user.email,
        current_password: '',
        password: '',
        password_confirmation: '',
        photo: null,
    });

    const submit = (event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, _method: 'put' })).post('/profile', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset('current_password', 'password', 'password_confirmation', 'photo'),
        });
    };

    const field = (name, label, type = 'text') => (
        <div>
            <label htmlFor={`profile-${name}`} className="mb-1 block text-sm font-medium">{label}</label>
            <input id={`profile-${name}`} type={type} className={inputClass} value={form.data[name]} onChange={(e) => form.setData(name, e.target.value)} />
            {form.errors[name] && <p className="mt-1 text-sm text-red-600">{form.errors[name]}</p>}
        </div>
    );

    return (
        <AppLayout title="Profil">
            <Head title="Profil Penulis" />
            <h1 className="mb-6 font-serif text-2xl font-bold">Profil Penulis</h1>

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                {[['Total artikel', stats.total], ['Terbit', stats.published], ['Draf', stats.draft]].map(([label, value]) => (
                    <div key={label} className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="text-2xl font-bold">{value}</p>
                    </div>
                ))}
            </div>

            <form onSubmit={submit} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
                <div className="flex items-center gap-4">
                    {user.profile_photo_url ? (
                        <img src={user.profile_photo_url} alt={user.name} className="h-16 w-16 rounded-full object-cover" />
                    ) : (
                        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-2xl font-bold text-white">{user.name.charAt(0).toUpperCase()}</span>
                    )}
                    <div>
                        <label htmlFor="profile-photo" className="mb-1 block text-sm font-medium">Foto profil</label>
                        <input id="profile-photo" type="file" accept="image/png,image/jpeg,image/webp" onChange={(e) => form.setData('photo', e.target.files[0] ?? null)} className="text-sm" />
                        {form.errors.photo && <p className="mt-1 text-sm text-red-600">{form.errors.photo}</p>}
                    </div>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    {field('name', 'Nama')}
                    {field('username', 'Username')}
                    <div className="sm:col-span-2">{field('email', 'Email', 'email')}</div>
                </div>

                <fieldset className="grid gap-5 border-t border-slate-100 pt-5 sm:grid-cols-3">
                    <legend className="mb-2 text-sm font-semibold text-slate-700">Ganti password (opsional)</legend>
                    {field('current_password', 'Password saat ini', 'password')}
                    {field('password', 'Password baru', 'password')}
                    {field('password_confirmation', 'Konfirmasi password', 'password')}
                </fieldset>

                <button type="submit" disabled={form.processing} className="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark disabled:opacity-60">
                    {form.processing && <Spinner />}
                    Simpan Profil
                </button>
            </form>
        </AppLayout>
    );
}
