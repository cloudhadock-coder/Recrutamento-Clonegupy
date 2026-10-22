import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextArea from '@/Components/TextArea';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Apply({ company, job, consentVersion }) {
    const { data, setData, post, processing, errors, progress } = useForm({
        resume: null,
        answers: Object.fromEntries(job.questions.map((question) => [question.id, ''])),
        consent: false,
    });

    const setAnswer = (id, value) => setData('answers', { ...data.answers, [id]: value });

    const submit = (e) => {
        e.preventDefault();
        post(route('portal.apply.store', [company.slug, job.slug]), { forceFormData: true });
    };

    return (
        <PortalLayout company={company}>
            <Head title={`Candidatura: ${job.title}`} />

            <Link href={route('portal.jobs.show', [company.slug, job.slug])} className="text-sm text-indigo-600 hover:underline">
                ← Voltar para a vaga
            </Link>

            <form onSubmit={submit} className="mt-4 space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                <h1 className="text-2xl font-bold text-gray-900">Candidatura para {job.title}</h1>

                <InputError message={errors.application} className="rounded-md bg-red-50 p-3" />

                <div>
                    <InputLabel htmlFor="resume" value="Currículo (PDF, até 5 MB)" />
                    <input
                        id="resume"
                        type="file"
                        accept="application/pdf,.pdf"
                        className="mt-1 block w-full text-sm text-gray-700 file:me-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                        onChange={(e) => setData('resume', e.target.files[0] ?? null)}
                    />
                    {progress && (
                        <progress value={progress.percentage} max="100" className="mt-2 w-full">
                            {progress.percentage}%
                        </progress>
                    )}
                    <InputError message={errors.resume} className="mt-2" />
                </div>

                {job.questions.map((question) => (
                    <div key={question.id}>
                        <InputLabel htmlFor={question.id}>
                            {question.label}
                            {question.required && <span className="text-red-600"> *</span>}
                        </InputLabel>
                        {question.type === 'yes_no' ? (
                            <div className="mt-2 flex gap-6 text-sm">
                                {[
                                    ['sim', 'Sim'],
                                    ['nao', 'Não'],
                                ].map(([value, label]) => (
                                    <label key={value} className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name={question.id}
                                            value={value}
                                            checked={data.answers[question.id] === value}
                                            onChange={() => setAnswer(question.id, value)}
                                            className="border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        ) : (
                            <TextArea
                                id={question.id}
                                rows={3}
                                value={data.answers[question.id]}
                                className="mt-1 block w-full"
                                onChange={(e) => setAnswer(question.id, e.target.value)}
                            />
                        )}
                        <InputError message={errors[`answers.${question.id}`]} className="mt-2" />
                    </div>
                ))}

                <div className="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                    <label className="flex items-start gap-3">
                        <Checkbox
                            className="mt-0.5"
                            checked={data.consent}
                            onChange={(e) => setData('consent', e.target.checked)}
                        />
                        <span>
                            Autorizo a <strong>{company.name}</strong> a tratar meus dados pessoais e o meu currículo para
                            este processo seletivo, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018). Posso
                            excluir ou anonimizar minha conta a qualquer momento em “Minha conta”.
                            <span className="block text-xs text-gray-500">Termo de consentimento, versão {consentVersion}.</span>
                        </span>
                    </label>
                    <InputError message={errors.consent} className="mt-2" />
                </div>

                <div className="flex justify-end">
                    <PrimaryButton disabled={processing}>Enviar candidatura</PrimaryButton>
                </div>
            </form>
        </PortalLayout>
    );
}
