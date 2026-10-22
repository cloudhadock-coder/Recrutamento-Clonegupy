import CityPicker from '@/Components/CityPicker';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import CandidateLayout from '@/Layouts/CandidateLayout';
import { cityLabel, maskCep, maskPhone, onlyDigits } from '@/lib/format';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

const erasureOptions = {
    anonymize: {
        title: 'Anonimizar minha conta',
        description:
            'Removemos seu nome, contatos, currículos e respostas. As empresas continuam vendo apenas que houve uma candidatura anônima, sem nenhum dado seu.',
        button: 'Anonimizar conta',
    },
    delete: {
        title: 'Excluir minha conta',
        description: 'Apagamos sua conta, todas as suas candidaturas e currículos. Esta ação não pode ser desfeita.',
        button: 'Excluir conta',
    },
};

function ProfileForm({ candidate }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: candidate.name,
        email: candidate.email,
        phone: maskPhone(candidate.phone ?? ''),
        linkedin_url: candidate.linkedin_url ?? '',
        cep: maskCep(candidate.cep ?? ''),
        city_id: candidate.city_id,
        state: candidate.state ?? '',
    });

    const [city, setCity] = useState(candidate.city ? { id: candidate.city.id, label: cityLabel(candidate.city) } : null);

    const lookupCep = async () => {
        if (onlyDigits(data.cep).length !== 8) {
            return;
        }

        try {
            const { data: address } = await axios.get(route('lookup.cep', onlyDigits(data.cep)));

            setData((current) => ({ ...current, city_id: address.city_id, state: address.state ?? current.state }));
            setCity(address.city_id ? { id: address.city_id, label: `${address.city_name} - ${address.state}` } : null);
        } catch {
            // CEP não encontrado: o candidato escolhe a cidade manualmente.
        }
    };

    const submit = (e) => {
        e.preventDefault();
        patch(route('candidate.account.update'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid gap-4 rounded-lg bg-white p-6 shadow-sm sm:grid-cols-2">
            <h2 className="font-medium text-gray-900 sm:col-span-2">Meus dados</h2>

            {[
                ['name', 'Nome completo', {}],
                ['email', 'E-mail', { type: 'email' }],
                ['phone', 'Celular', { mask: maskPhone }],
                ['linkedin_url', 'LinkedIn (URL)', { type: 'url' }],
            ].map(([name, label, { mask, ...props }]) => (
                <div key={name}>
                    <InputLabel htmlFor={name} value={label} />
                    <TextInput
                        id={name}
                        value={data[name]}
                        className="mt-1 block w-full"
                        onChange={(e) => setData(name, mask ? mask(e.target.value) : e.target.value)}
                        {...props}
                    />
                    <InputError message={errors[name]} className="mt-2" />
                </div>
            ))}

            <div>
                <InputLabel htmlFor="cep" value="CEP" />
                <TextInput
                    id="cep"
                    value={data.cep}
                    className="mt-1 block w-full"
                    onChange={(e) => setData('cep', maskCep(e.target.value))}
                    onBlur={lookupCep}
                />
                <InputError message={errors.cep} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="city" value="Cidade" />
                <CityPicker
                    id="city"
                    value={city}
                    className="mt-1"
                    onChange={(selected) => {
                        setCity(selected);
                        setData((current) => ({ ...current, city_id: selected?.id ?? null, state: selected?.label.slice(-2) ?? '' }));
                    }}
                />
                <InputError message={errors.city_id} className="mt-2" />
            </div>

            <div className="flex justify-end sm:col-span-2">
                <PrimaryButton disabled={processing}>Salvar</PrimaryButton>
            </div>
        </form>
    );
}

export default function Account({ candidate }) {
    const [mode, setMode] = useState(null);
    const form = useForm({ mode: '', password: '' });

    const open = (selected) => {
        form.reset();
        form.clearErrors();
        form.setData({ mode: selected, password: '' });
        setMode(selected);
    };

    const erase = (e) => {
        e.preventDefault();
        form.delete(route('candidate.account.destroy'), { preserveScroll: true, onFinish: () => form.reset('password') });
    };

    return (
        <CandidateLayout title="Minha conta">
            <Head title="Minha conta" />

            <div className="space-y-6">
                <ProfileForm candidate={candidate} />

                <section className="rounded-lg bg-white p-6 shadow-sm">
                    <h2 className="font-medium text-gray-900">Privacidade e seus dados (LGPD)</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Você pode pedir a remoção dos seus dados a qualquer momento. Escolha como prefere fazer isso.
                    </p>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        {Object.entries(erasureOptions).map(([key, option]) => (
                            <div key={key} className="rounded-md border border-gray-200 p-4">
                                <h3 className="text-sm font-semibold text-gray-900">{option.title}</h3>
                                <p className="mt-1 text-sm text-gray-600">{option.description}</p>
                                <DangerButton className="mt-4" onClick={() => open(key)}>
                                    {option.button}
                                </DangerButton>
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <Modal show={mode !== null} onClose={() => setMode(null)} maxWidth="md">
                {mode && (
                    <form onSubmit={erase} className="p-6">
                        <h2 className="text-lg font-medium text-gray-900">{erasureOptions[mode].title}?</h2>
                        <p className="mt-1 text-sm text-gray-600">{erasureOptions[mode].description}</p>
                        <InputLabel htmlFor="erase-password" value="Confirme sua senha" className="mt-4" />
                        <TextInput
                            id="erase-password"
                            type="password"
                            value={form.data.password}
                            className="mt-1 block w-full"
                            isFocused
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                        <InputError message={form.errors.password ?? form.errors.mode} className="mt-2" />
                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton onClick={() => setMode(null)}>Cancelar</SecondaryButton>
                            <DangerButton disabled={form.processing}>{erasureOptions[mode].button}</DangerButton>
                        </div>
                    </form>
                )}
            </Modal>
        </CandidateLayout>
    );
}
