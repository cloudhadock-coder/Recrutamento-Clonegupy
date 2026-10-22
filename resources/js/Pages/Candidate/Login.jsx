import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('candidate.authenticate'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Entrar como candidato" />

            <h1 className="mb-4 text-lg font-semibold text-gray-900">Área do candidato</h1>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="E-mail" />
                    <TextInput
                        id="email"
                        type="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Senha" />
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <label className="mt-4 flex items-center">
                    <Checkbox checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                    <span className="ms-2 text-sm text-gray-600">Lembrar de mim</span>
                </label>

                <div className="mt-4 flex items-center justify-end">
                    <Link href={route('candidate.register')} className="text-sm text-gray-600 underline hover:text-gray-900">
                        Criar conta
                    </Link>
                    <PrimaryButton className="ms-4" disabled={processing}>
                        Entrar
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
