import Badge from '@/Components/Badge';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { applicationStatusLabels, cityLabel, formatDateTime, maskPhone } from '@/lib/format';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const eventLabels = {
    applied: 'Candidatura recebida',
    stage_changed: 'Mudança de etapa',
    rejected: 'Reprovada',
    reactivated: 'Reativada',
};

function describeEvent(event) {
    if (event.type === 'stage_changed') {
        return `${event.from_stage?.name ?? '—'} → ${event.to_stage?.name ?? '—'}`;
    }

    if (event.type === 'applied') {
        return event.to_stage ? `Entrou em ${event.to_stage.name}` : null;
    }

    if (event.type === 'rejected') {
        return event.meta?.reason ? `Motivo: ${event.meta.reason}` : null;
    }

    return null;
}

export default function Show({ application, hasResume, canManage }) {
    const { errors } = usePage().props;
    const { candidate, job } = application;
    const [rejecting, setRejecting] = useState(false);
    const rejectForm = useForm({ reason: '' });

    const answers = job.questions
        .map((question) => ({ question, answer: application.answers?.[question.id] }))
        .filter(({ answer }) => answer !== undefined);

    const reject = (e) => {
        e.preventDefault();
        rejectForm.post(route('panel.applications.reject', application.id), {
            preserveScroll: true,
            onSuccess: () => setRejecting(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 className="flex items-center gap-3 text-xl font-semibold leading-tight text-gray-800">
                            {candidate.name}
                            <Badge tone={application.status === 'rejected' ? 'red' : 'green'}>
                                {applicationStatusLabels[application.status]}
                            </Badge>
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            <Link href={route('panel.jobs.show', job.id)} className="hover:underline">
                                {job.title}
                            </Link>{' '}
                            · Etapa atual: <strong>{application.stage.name}</strong>
                        </p>
                    </div>
                    {canManage && (
                        <div className="flex flex-wrap items-center gap-2">
                            {application.status === 'active' ? (
                                <>
                                    <SelectInput
                                        aria-label="Mover para etapa"
                                        value={application.job_stage_id}
                                        options={job.stages.map((stage) => ({ value: stage.id, label: stage.name }))}
                                        className="text-sm"
                                        onChange={(e) =>
                                            router.patch(
                                                route('panel.applications.move', application.id),
                                                { job_stage_id: Number(e.target.value) },
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                    <DangerButton onClick={() => setRejecting(true)}>Reprovar</DangerButton>
                                </>
                            ) : (
                                <Link
                                    href={route('panel.applications.reactivate', application.id)}
                                    method="post"
                                    as="button"
                                    preserveScroll
                                    className="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
                                >
                                    Reativar candidatura
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            }
        >
            <Head title={`${candidate.name} · ${job.title}`} />

            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div className="space-y-6 lg:col-span-2">
                    <InputError message={errors.job_stage_id} />

                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="font-medium text-gray-900">Contato</h3>
                        <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-gray-500">E-mail</dt>
                                <dd className="text-gray-900">{candidate.email}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Telefone</dt>
                                <dd className="text-gray-900">{candidate.phone ? maskPhone(candidate.phone) : '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Cidade</dt>
                                <dd className="text-gray-900">{cityLabel(candidate.city) ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">LinkedIn</dt>
                                <dd className="truncate text-gray-900">
                                    {candidate.linkedin_url ? (
                                        <a href={candidate.linkedin_url} target="_blank" rel="noreferrer" className="text-indigo-600 hover:underline">
                                            {candidate.linkedin_url}
                                        </a>
                                    ) : (
                                        '—'
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="font-medium text-gray-900">Respostas</h3>
                        {answers.length === 0 ? (
                            <p className="mt-2 text-sm text-gray-500">Nenhuma resposta.</p>
                        ) : (
                            <dl className="mt-4 space-y-4 text-sm">
                                {answers.map(({ question, answer }) => (
                                    <div key={question.id}>
                                        <dt className="text-gray-500">{question.label}</dt>
                                        <dd className="whitespace-pre-line text-gray-900">
                                            {question.type === 'yes_no' ? (answer === 'sim' ? 'Sim' : 'Não') : answer}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </section>

                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h3 className="font-medium text-gray-900">Currículo</h3>
                            {hasResume && (
                                <a
                                    href={route('panel.applications.resume', application.id)}
                                    className="text-sm font-medium text-indigo-600 hover:underline"
                                >
                                    Baixar PDF
                                </a>
                            )}
                        </div>
                        {!hasResume ? (
                            <p className="mt-2 text-sm text-gray-500">Currículo removido a pedido do candidato (LGPD).</p>
                        ) : application.resume_text ? (
                            <pre className="mt-4 max-h-96 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-4 font-sans text-sm text-gray-800">
                                {application.resume_text}
                            </pre>
                        ) : (
                            <p className="mt-2 text-sm text-gray-500">O texto do currículo ainda está sendo extraído.</p>
                        )}
                    </section>
                </div>

                <aside className="space-y-6">
                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="font-medium text-gray-900">Histórico</h3>
                        <ol className="mt-4 space-y-4 border-l border-gray-200 pl-4 text-sm">
                            {application.events.map((event) => (
                                <li key={event.id}>
                                    <p className="font-medium text-gray-900">{eventLabels[event.type]}</p>
                                    {describeEvent(event) && <p className="text-gray-600">{describeEvent(event)}</p>}
                                    <p className="text-xs text-gray-400">
                                        {formatDateTime(event.created_at)}
                                        {event.user && ` · ${event.user.name}`}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>

                    <section className="rounded-lg bg-white p-6 text-sm shadow-sm">
                        <h3 className="font-medium text-gray-900">Consentimento LGPD</h3>
                        <p className="mt-2 text-gray-600">
                            Aceito em {formatDateTime(application.consent_at)} (termo versão {application.consent_version}).
                        </p>
                    </section>
                </aside>
            </div>

            <Modal show={rejecting} onClose={() => setRejecting(false)} maxWidth="md">
                <form onSubmit={reject} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Reprovar {candidate.name}?</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        O candidato receberá um e-mail de retorno. O motivo é interno e não é enviado a ele.
                    </p>
                    <TextInput
                        value={rejectForm.data.reason}
                        placeholder="Motivo (opcional)"
                        className="mt-4 block w-full"
                        onChange={(e) => rejectForm.setData('reason', e.target.value)}
                    />
                    <InputError message={rejectForm.errors.reason} className="mt-2" />
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setRejecting(false)}>Cancelar</SecondaryButton>
                        <PrimaryButton disabled={rejectForm.processing}>Reprovar</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
