import Badge, { jobStatusTone } from '@/Components/Badge';
import Pagination from '@/Components/Pagination';
import SelectInput from '@/Components/SelectInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate, jobLocation, jobStatusLabels } from '@/lib/format';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ jobs, filters, statuses, canCreate }) {
    const { auth } = usePage().props;
    const canManage = auth.user.can_manage_hiring;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Vagas</h2>
                    {canCreate && (
                        <Link
                            href={route('panel.jobs.create')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Nova vaga
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Vagas" />

            <div className="mx-auto max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center gap-3">
                    <label htmlFor="status" className="text-sm text-gray-600">
                        Status
                    </label>
                    <SelectInput
                        id="status"
                        value={filters.status ?? ''}
                        options={statuses}
                        placeholder="Todos"
                        className="text-sm"
                        onChange={(e) =>
                            router.get(route('panel.jobs.index'), e.target.value ? { status: e.target.value } : {}, {
                                preserveState: true,
                            })
                        }
                    />
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th className="px-6 py-3">Vaga</th>
                                <th className="px-6 py-3">Status</th>
                                <th className="px-6 py-3">Candidaturas</th>
                                <th className="px-6 py-3">Publicada em</th>
                                <th className="px-6 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {jobs.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-6 py-10 text-center text-gray-500">
                                        Nenhuma vaga encontrada.
                                    </td>
                                </tr>
                            )}
                            {jobs.data.map((job) => (
                                <tr key={job.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <Link href={route('panel.jobs.show', job.id)} className="font-medium text-gray-900 hover:text-indigo-700">
                                            {job.title}
                                        </Link>
                                        <p className="text-xs text-gray-500">{jobLocation(job)}</p>
                                    </td>
                                    <td className="px-6 py-4">
                                        <Badge tone={jobStatusTone[job.status]}>{jobStatusLabels[job.status]}</Badge>
                                    </td>
                                    <td className="px-6 py-4 text-gray-700">
                                        {job.active_applications_count} em andamento
                                        <span className="text-gray-400"> / {job.applications_count} no total</span>
                                    </td>
                                    <td className="px-6 py-4 text-gray-500">{formatDate(job.published_at) || '—'}</td>
                                    <td className="space-x-3 whitespace-nowrap px-6 py-4 text-right">
                                        <Link href={route('panel.jobs.show', job.id)} className="text-indigo-600 hover:underline">
                                            Kanban
                                        </Link>
                                        {canManage && (
                                            <>
                                                <Link href={route('panel.jobs.edit', job.id)} className="text-gray-600 hover:underline">
                                                    Editar
                                                </Link>
                                                <Link href={route('panel.jobs.stages.index', job.id)} className="text-gray-600 hover:underline">
                                                    Etapas
                                                </Link>
                                                {job.status === 'published' ? (
                                                    <Link
                                                        href={route('panel.jobs.pause', job.id)}
                                                        method="post"
                                                        as="button"
                                                        preserveScroll
                                                        className="text-amber-700 hover:underline"
                                                    >
                                                        Pausar
                                                    </Link>
                                                ) : (
                                                    <Link
                                                        href={route('panel.jobs.publish', job.id)}
                                                        method="post"
                                                        as="button"
                                                        preserveScroll
                                                        className="text-green-700 hover:underline"
                                                    >
                                                        Publicar
                                                    </Link>
                                                )}
                                            </>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination links={jobs.links} />
            </div>
        </AuthenticatedLayout>
    );
}
