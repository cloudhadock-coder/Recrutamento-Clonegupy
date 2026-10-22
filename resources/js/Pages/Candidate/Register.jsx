import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { maskPhone } from '@/lib/format';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('candidate.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const fields = [
        ['name', 'Nome completo', { autoComplete: 'name', isFocused: true }],
        ['email', 'E-mail', { type: 'email', autoComplete: 'username' }],
        ['phone', 'Celular (opcional)', { autoComplete: 'tel', inputMode: 'tel' }],
        ['password', 'Senha', { type: 'password', autoComplete: 'new-password' }],
        ['password_confirmation', 'Confirme a senha', { type: 'password', autoComplete: 'new-password' }],
    ];

    return (
        <GuestLayout>
            <Head title="Criar conta de candidato" />

            <h1 className="text-lg font-semibold text-gray-900">Crie sua conta</h1>
            <p className="mb-4 text-sm text-gray-600">Uma única conta para se candidatar a vagas de qualquer empresa.</p>

            <form onSubmit={submit} className="space-y-4">
                {fields.map(([name, label, props]) => (
                    <div key={name}>
                        <InputLabel htmlFor={name} value={label} />
                        <TextInput
                            id={name}
                            value={data[name]}
                            className="mt-1 block w-full"
                            onChange={(e) => setData(name, name === 'phone' ? maskPhone(e.target.value) : e.target.value)}
                            {...props}
                        />
                        <InputError message={errors[name]} className="mt-2" />
                    </div>
                ))}

                <div className="flex items-center justify-end">
                    <Link href={route('candidate.login')} className="text-sm text-gray-600 underline hover:text-gray-900">
                        Já tenho conta
                    </Link>
                    <PrimaryButton className="ms-4" disabled={processing}>
                        Criar conta
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
