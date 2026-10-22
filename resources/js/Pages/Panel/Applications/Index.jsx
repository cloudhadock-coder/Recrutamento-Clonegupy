import Badge from '@/Components/Badge';
import { CursorPagination } from '@/Components/Pagination';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { applicationStatusLabels, formatDate } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ applications, filters, jobs, statuses }) {
    const [term, setTerm] = useState(filters.q ?? '');

    const applyFilters = (changes) => {
        const params = Object.fromEntries(
            Object.entries({ ...filters, q: term, ...changes }).filter(([, value]) => value !== '' && value !== null && value !== undefined),
        );

        router.get(route('panel.applications.index'), params, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Candidaturas</h2>}>
            <Head title="Candidaturas" />

            <div className="mx-auto max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <form
                    className="flex flex-wrap items-center gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        applyFilters({});
                    }}
                >
                    <TextInput
                        value={term}
                        placeholder="Buscar candidato por nome ou e-mail"
                        className="w-72 text-sm"
                        onChange={(e) => setTerm(e.target.value)}
                    />
                    <SelectInput
                        value={filters.job ?? ''}
                        placeholder="Todas as vagas"
                        options={jobs.map((job) => ({ value: job.id, label: job.title }))}
                        className="text-sm"
                        onChange={(e) => applyFilters({ job: e.target.value })}
                    />
                    <SelectInput
                        value={filters.status ?? ''}
                        placeholder="Todos os status"
                        options={statuses}
                        className="text-sm"
                        onChange={(e) => applyFilters({ status: e.target.value })}
                    />
                    <button type="submit" className="rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700">
                        Filtrar
                    </button>
                </form>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th className="px-6 py-3">Candidato</th>
                                <th className="px-6 py-3">Vaga</th>
                                <th className="px-6 py-3">Etapa</th>
                                <th className="px-6 py-3">Status</th>
                                <th className="px-6 py-3">Inscrição</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {applications.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-6 py-10 text-center text-gray-500">
                                        Nenhuma candidatura encontrada.
                                    </td>
                                </tr>
                            )}
                            {applications.data.map((application) => (
                                <tr key={application.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-3">
                                        <Link
                                            href={route('panel.applications.show', application.id)}
                                            className="font-medium text-gray-900 hover:text-indigo-700"
                                        >
                                            {application.candidate.name}
                                        </Link>
                                        <p className="text-xs text-gray-500">{application.candidate.email}</p>
                                    </td>
                                    <td className="px-6 py-3">
                                        <Link href={route('panel.jobs.show', application.job.id)} className="text-gray-700 hover:underline">
                                            {application.job.title}
                                        </Link>
                                    </td>
                                    <td className="px-6 py-3 text-gray-700">{application.stage.name}</td>
                                    <td className="px-6 py-3">
                                        <Badge tone={application.status === 'rejected' ? 'red' : 'green'}>
                                            {applicationStatusLabels[application.status]}
                                        </Badge>
                                    </td>
                                    <td className="px-6 py-3 text-gray-500">{formatDate(application.created_at)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <CursorPagination paginator={applications} />
            </div>
        </AuthenticatedLayout>
    );
}
