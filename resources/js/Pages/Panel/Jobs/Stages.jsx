import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

function StageRow({ job, stage, index, total, onMove }) {
    const [name, setName] = useState(stage.name);
    const changed = name.trim() !== stage.name;

    return (
        <li className="flex flex-wrap items-center gap-3 px-6 py-3">
            <span className="w-6 text-sm text-gray-400">{index + 1}.</span>
            <TextInput value={name} onChange={(e) => setName(e.target.value)} className="min-w-0 flex-1 text-sm" />
            <span className="w-28 text-xs text-gray-500">
                {stage.applications_count === 1 ? '1 candidatura' : `${stage.applications_count} candidaturas`}
            </span>
            {changed && (
                <PrimaryButton
                    type="button"
                    onClick={() =>
                        router.patch(route('panel.jobs.stages.update', [job.id, stage.id]), { name }, { preserveScroll: true })
                    }
                >
                    Salvar
                </PrimaryButton>
            )}
            <SecondaryButton type="button" disabled={index === 0} onClick={() => onMove(index, -1)} aria-label="Subir">
                ↑
            </SecondaryButton>
            <SecondaryButton type="button" disabled={index === total - 1} onClick={() => onMove(index, 1)} aria-label="Descer">
                ↓
            </SecondaryButton>
            <DangerButton
                type="button"
                disabled={total <= 1 || stage.applications_count > 0}
                title={stage.applications_count > 0 ? 'Mova as candidaturas antes de excluir' : undefined}
                onClick={() => router.delete(route('panel.jobs.stages.destroy', [job.id, stage.id]), { preserveScroll: true })}
            >
                Excluir
            </DangerButton>
        </li>
    );
}

export default function Stages({ job, stages }) {
    const { errors } = usePage().props;
    const form = useForm({ name: '' });

    const move = (index, direction) => {
        const ids = stages.map((stage) => stage.id);
        [ids[index], ids[index + direction]] = [ids[index + direction], ids[index]];

        router.put(route('panel.jobs.stages.reorder', job.id), { stage_ids: ids }, { preserveScroll: true });
    };

    const addStage = (e) => {
        e.preventDefault();
        form.post(route('panel.jobs.stages.store', job.id), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Etapas: {job.title}</h2>
                    <div className="flex gap-4 text-sm">
                        <Link href={route('panel.jobs.edit', job.id)} className="text-gray-600 hover:underline">
                            Editar vaga
                        </Link>
                        <Link href={route('panel.jobs.show', job.id)} className="text-indigo-600 hover:underline">
                            Abrir kanban
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Etapas: ${job.title}`} />

            <div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <p className="text-sm text-gray-600">
                    As etapas viram as colunas do kanban. Toda candidatura nova entra na primeira etapa.
                </p>

                <InputError message={errors.stage} />

                <ul className="divide-y divide-gray-100 rounded-lg bg-white shadow-sm">
                    {stages.map((stage, index) => (
                        <StageRow
                            key={`${stage.id}-${stage.name}`}
                            job={job}
                            stage={stage}
                            index={index}
                            total={stages.length}
                            onMove={move}
                        />
                    ))}
                </ul>

                <form onSubmit={addStage} className="flex items-start gap-3 rounded-lg bg-white p-6 shadow-sm">
                    <div className="flex-1">
                        <TextInput
                            value={form.data.name}
                            placeholder="Nome da nova etapa (ex.: Teste prático)"
                            className="block w-full"
                            onChange={(e) => form.setData('name', e.target.value)}
                        />
                        <InputError message={form.errors.name} className="mt-2" />
                    </div>
                    <PrimaryButton disabled={form.processing}>Adicionar etapa</PrimaryButton>
                </form>

                {job.status !== 'published' && (
                    <div className="flex items-center justify-between rounded-lg bg-indigo-50 p-6">
                        <p className="text-sm text-indigo-900">Tudo pronto? Publique a vaga para ela aparecer no portal de carreiras.</p>
                        <Link
                            href={route('panel.jobs.publish', job.id)}
                            method="post"
                            as="button"
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Publicar vaga
                        </Link>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
