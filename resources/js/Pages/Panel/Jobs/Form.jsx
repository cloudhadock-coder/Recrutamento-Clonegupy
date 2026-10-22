import Checkbox from '@/Components/Checkbox';
import CityPicker from '@/Components/CityPicker';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextArea from '@/Components/TextArea';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { cityLabel } from '@/lib/format';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const questionTypes = [
    { value: 'text', label: 'Resposta aberta' },
    { value: 'yes_no', label: 'Sim ou não' },
];

export default function Form({ job, workModels, employmentTypes }) {
    const isEditing = job !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        title: job?.title ?? '',
        description: job?.description ?? '',
        requirements: job?.requirements ?? '',
        benefits: job?.benefits ?? '',
        work_model: job?.work_model ?? 'presencial',
        employment_type: job?.employment_type ?? 'clt',
        city_id: job?.city_id ?? null,
        salary_min: job?.salary_min ?? '',
        salary_max: job?.salary_max ?? '',
        questions: job?.questions ?? [],
    });

    const [city, setCity] = useState(job?.city ? { id: job.city.id, label: cityLabel(job.city) } : null);

    const updateQuestion = (index, changes) =>
        setData(
            'questions',
            data.questions.map((question, current) => (current === index ? { ...question, ...changes } : question)),
        );

    const submit = (e) => {
        e.preventDefault();

        isEditing ? put(route('panel.jobs.update', job.id)) : post(route('panel.jobs.store'));
    };

    const title = isEditing ? `Editar vaga: ${job.title}` : 'Nova vaga';

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">{title}</h2>}>
            <Head title={title} />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <div>
                        <InputLabel htmlFor="title" value="Título" />
                        <TextInput
                            id="title"
                            value={data.title}
                            className="mt-1 block w-full"
                            isFocused={!isEditing}
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel htmlFor="work_model" value="Modelo de trabalho" />
                            <SelectInput
                                id="work_model"
                                value={data.work_model}
                                options={workModels}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('work_model', e.target.value)}
                            />
                            <InputError message={errors.work_model} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="employment_type" value="Contratação" />
                            <SelectInput
                                id="employment_type"
                                value={data.employment_type}
                                options={employmentTypes}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('employment_type', e.target.value)}
                            />
                            <InputError message={errors.employment_type} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="city"
                                value={data.work_model === 'remoto' ? 'Cidade (opcional)' : 'Cidade'}
                            />
                            <CityPicker
                                id="city"
                                value={city}
                                className="mt-1"
                                onChange={(selected) => {
                                    setCity(selected);
                                    setData('city_id', selected?.id ?? null);
                                }}
                            />
                            <InputError message={errors.city_id} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="salary_min" value="Salário a partir de (R$, opcional)" />
                            <TextInput
                                id="salary_min"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.salary_min}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('salary_min', e.target.value)}
                            />
                            <InputError message={errors.salary_min} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="salary_max" value="Salário até (R$, opcional)" />
                            <TextInput
                                id="salary_max"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.salary_max}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('salary_max', e.target.value)}
                            />
                            <InputError message={errors.salary_max} className="mt-2" />
                        </div>
                    </div>
                </section>

                <section className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    {[
                        ['description', 'Descrição', 8],
                        ['requirements', 'Requisitos', 5],
                        ['benefits', 'Benefícios', 3],
                    ].map(([name, label, rows]) => (
                        <div key={name}>
                            <InputLabel htmlFor={name} value={label} />
                            <TextArea
                                id={name}
                                rows={rows}
                                value={data[name]}
                                className="mt-1 block w-full"
                                onChange={(e) => setData(name, e.target.value)}
                            />
                            <InputError message={errors[name]} className="mt-2" />
                        </div>
                    ))}
                </section>

                <section className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <div>
                        <h3 className="font-medium text-gray-900">Perguntas para o candidato</h3>
                        <p className="text-sm text-gray-500">Respondidas no momento da candidatura.</p>
                    </div>

                    {data.questions.map((question, index) => (
                        <div key={question.id ?? `new-${index}`} className="grid gap-3 rounded-md border border-gray-200 p-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center">
                            <div>
                                <TextInput
                                    value={question.label}
                                    placeholder="Ex.: Qual a sua pretensão salarial?"
                                    className="block w-full"
                                    onChange={(e) => updateQuestion(index, { label: e.target.value })}
                                />
                                <InputError message={errors[`questions.${index}.label`]} className="mt-1" />
                            </div>
                            <SelectInput
                                value={question.type}
                                options={questionTypes}
                                className="text-sm"
                                onChange={(e) => updateQuestion(index, { type: e.target.value })}
                            />
                            <label className="flex items-center gap-2 text-sm text-gray-600">
                                <Checkbox
                                    checked={question.required}
                                    onChange={(e) => updateQuestion(index, { required: e.target.checked })}
                                />
                                Obrigatória
                            </label>
                            <DangerButton
                                type="button"
                                onClick={() => setData('questions', data.questions.filter((_, current) => current !== index))}
                            >
                                Remover
                            </DangerButton>
                        </div>
                    ))}

                    {data.questions.length < 10 && (
                        <SecondaryButton
                            type="button"
                            onClick={() =>
                                setData('questions', [...data.questions, { label: '', type: 'text', required: false }])
                            }
                        >
                            Adicionar pergunta
                        </SecondaryButton>
                    )}
                    <InputError message={errors.questions} />
                </section>

                <div className="flex items-center justify-end gap-4">
                    <Link href={route('panel.jobs.index')} className="text-sm text-gray-600 hover:underline">
                        Cancelar
                    </Link>
                    <PrimaryButton disabled={processing}>{isEditing ? 'Salvar alterações' : 'Criar vaga'}</PrimaryButton>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
