import CityPicker from '@/Components/CityPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { maskCep, maskCnpj, maskPhone, onlyDigits } from '@/lib/format';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

/**
 * Cadastro de empresa: o CNPJ é consultado na BrasilAPI para preencher os dados.
 */
export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        cnpj: '',
        name: '',
        trade_name: '',
        slug: '',
        email: '',
        phone: '',
        cep: '',
        street: '',
        number: '',
        complement: '',
        neighborhood: '',
        city_id: null,
        state: '',
        admin_name: '',
        admin_email: '',
        password: '',
        password_confirmation: '',
    });

    const [city, setCity] = useState(null);
    const [lookup, setLookup] = useState({ loading: false, message: null, found: false });

    const lookupCnpj = async (cnpj = data.cnpj) => {
        if (onlyDigits(cnpj).length !== 14) {
            setLookup({ loading: false, message: 'Informe os 14 dígitos do CNPJ.', found: false });
            return;
        }

        setLookup({ loading: true, message: null, found: false });

        try {
            const { data: company } = await axios.get(route('lookup.cnpj', onlyDigits(cnpj)));

            setData((current) => ({
                ...current,
                name: company.name ?? '',
                trade_name: company.trade_name ?? '',
                slug: current.slug || company.slug,
                email: company.email ?? '',
                phone: maskPhone(company.phone ?? ''),
                cep: maskCep(company.cep ?? ''),
                street: company.street ?? '',
                number: company.number ?? '',
                complement: company.complement ?? '',
                neighborhood: company.neighborhood ?? '',
                city_id: company.city_id,
                state: company.state ?? '',
            }));
            setCity(company.city_id ? { id: company.city_id, label: `${company.city_name} - ${company.state}` } : null);
            setLookup({ loading: false, message: 'Dados preenchidos pela Receita Federal. Confira antes de continuar.', found: true });
        } catch (error) {
            setLookup({
                loading: false,
                message: error.response?.data?.message ?? 'Não foi possível consultar o CNPJ.',
                found: false,
            });
        }
    };

    const lookupCep = async () => {
        if (onlyDigits(data.cep).length !== 8) {
            return;
        }

        try {
            const { data: address } = await axios.get(route('lookup.cep', onlyDigits(data.cep)));

            setData((current) => ({
                ...current,
                street: address.street ?? current.street,
                neighborhood: address.neighborhood ?? current.neighborhood,
                city_id: address.city_id,
                state: address.state ?? current.state,
            }));
            setCity(address.city_id ? { id: address.city_id, label: `${address.city_name} - ${address.state}` } : null);
        } catch {
            // CEP não encontrado: o usuário preenche manualmente.
        }
    };

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const field = (name, label, props = {}) => (
        <div className={props.wrapperClassName ?? ''}>
            <InputLabel htmlFor={name} value={label} />
            <TextInput
                id={name}
                name={name}
                value={data[name]}
                className="mt-1 block w-full"
                onChange={(e) => setData(name, props.mask ? props.mask(e.target.value) : e.target.value)}
                {...props.input}
            />
            <InputError message={errors[name]} className="mt-2" />
        </div>
    );

    return (
        <GuestLayout wide>
            <Head title="Cadastrar empresa" />

            <h1 className="text-xl font-semibold text-gray-900">Cadastre sua empresa</h1>
            <p className="mt-1 text-sm text-gray-600">
                Informe o CNPJ e buscamos os dados da empresa automaticamente.
            </p>

            <form onSubmit={submit} className="mt-6 space-y-8">
                <section>
                    <InputLabel htmlFor="cnpj" value="CNPJ" />
                    <div className="mt-1 flex gap-2">
                        <TextInput
                            id="cnpj"
                            name="cnpj"
                            value={data.cnpj}
                            placeholder="00.000.000/0000-00"
                            className="block w-full"
                            isFocused
                            inputMode="numeric"
                            onChange={(e) => {
                                const value = maskCnpj(e.target.value);
                                setData('cnpj', value);
                                if (onlyDigits(value).length === 14) {
                                    lookupCnpj(value);
                                }
                            }}
                        />
                        <SecondaryButton type="button" onClick={() => lookupCnpj()} disabled={lookup.loading}>
                            {lookup.loading ? 'Buscando…' : 'Buscar'}
                        </SecondaryButton>
                    </div>
                    {lookup.message && (
                        <p className={'mt-2 text-sm ' + (lookup.found ? 'text-green-700' : 'text-amber-700')}>{lookup.message}</p>
                    )}
                    <InputError message={errors.cnpj} className="mt-2" />
                </section>

                <section className="grid gap-4 sm:grid-cols-2">
                    <h2 className="font-medium text-gray-900 sm:col-span-2">Dados da empresa</h2>
                    {field('name', 'Razão social', { wrapperClassName: 'sm:col-span-2' })}
                    {field('trade_name', 'Nome fantasia')}
                    <div>
                        <InputLabel htmlFor="slug" value="Endereço do portal de vagas" />
                        <div className="mt-1 flex rounded-md shadow-sm">
                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">
                                /
                            </span>
                            <input
                                id="slug"
                                name="slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                                className="block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <p className="mt-1 text-xs text-gray-500">Suas vagas ficarão em /{data.slug || 'sua-empresa'}/vagas</p>
                        <InputError message={errors.slug} className="mt-2" />
                    </div>
                    {field('email', 'E-mail da empresa', { input: { type: 'email' } })}
                    {field('phone', 'Telefone', { mask: maskPhone })}
                    <div>
                        <InputLabel htmlFor="cep" value="CEP" />
                        <TextInput
                            id="cep"
                            name="cep"
                            value={data.cep}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('cep', maskCep(e.target.value))}
                            onBlur={lookupCep}
                        />
                        <InputError message={errors.cep} className="mt-2" />
                    </div>
                    {field('street', 'Logradouro')}
                    {field('number', 'Número')}
                    {field('complement', 'Complemento')}
                    {field('neighborhood', 'Bairro')}
                    <div>
                        <InputLabel htmlFor="city" value="Cidade" />
                        <CityPicker
                            id="city"
                            value={city}
                            className="mt-1"
                            onChange={(selected) => {
                                setCity(selected);
                                setData((current) => ({
                                    ...current,
                                    city_id: selected?.id ?? null,
                                    state: selected?.label.slice(-2) ?? current.state,
                                }));
                            }}
                        />
                        <InputError message={errors.city_id} className="mt-2" />
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2">
                    <h2 className="font-medium text-gray-900 sm:col-span-2">Seu acesso (administrador)</h2>
                    {field('admin_name', 'Seu nome', { input: { autoComplete: 'name' } })}
                    {field('admin_email', 'Seu e-mail', { input: { type: 'email', autoComplete: 'username' } })}
                    {field('password', 'Senha', { input: { type: 'password', autoComplete: 'new-password' } })}
                    {field('password_confirmation', 'Confirme a senha', { input: { type: 'password', autoComplete: 'new-password' } })}
                </section>

                <div className="flex items-center justify-end">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Já tenho cadastro
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Cadastrar empresa
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
