import Badge from '@/Components/Badge';
import { CursorPagination } from '@/Components/Pagination';
import CandidateLayout from '@/Layouts/CandidateLayout';
import { formatDate } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

export default function Applications({ applications }) {
    return (
        <CandidateLayout title="Minhas candidaturas">
            <Head title="Minhas candidaturas" />

            {applications.data.length === 0 ? (
                <div className="rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm">
                    Você ainda não se candidatou a nenhuma vaga.
                </div>
            ) : (
                <ul className="space-y-3">
                    {applications.data.map((application) => {
                        const { job } = application;
                        const company = job.company;

                        return (
                            <li key={application.id} className="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        {job.status === 'published' ? (
                                            <Link
                                                href={route('portal.jobs.show', [company.slug, job.slug])}
                                                className="font-semibold text-gray-900 hover:text-indigo-700"
                                            >
                                                {job.title}
                                            </Link>
                                        ) : (
                                            <span className="font-semibold text-gray-900">{job.title}</span>
                                        )}
                                        <p className="text-sm text-gray-600">{company.trade_name ?? company.name}</p>
                                    </div>
                                    {application.status === 'rejected' ? (
                                        <Badge tone="red">Processo encerrado</Badge>
                                    ) : (
                                        <Badge tone="indigo">{application.stage.name}</Badge>
                                    )}
                                </div>
                                <p className="mt-3 text-xs text-gray-400">
                                    Enviada em {formatDate(application.created_at)} · Consentimento LGPD registrado em{' '}
                                    {formatDate(application.consent_at)}
                                </p>
                            </li>
                        );
                    })}
                </ul>
            )}

            <CursorPagination paginator={applications} className="mt-6" />
        </CandidateLayout>
    );
}
