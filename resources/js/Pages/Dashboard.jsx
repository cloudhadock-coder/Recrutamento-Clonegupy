import Badge from '@/Components/Badge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { applicationStatusLabels, formatDate } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats, recentApplications }) {
    const cards = [
        { label: 'Vagas abertas', value: stats.openJobs, href: route('panel.jobs.index', { status: 'published' }) },
        { label: 'Candidaturas em andamento', value: stats.activeApplications, href: route('panel.applications.index', { status: 'active' }) },
        { label: 'Candidaturas nos últimos 7 dias', value: stats.applicationsLastWeek, href: route('panel.applications.index') },
    ];

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Início</h2>}>
            <Head title="Início" />

            <div className="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <dl className="grid gap-4 sm:grid-cols-3">
                    {cards.map((card) => (
                        <Link key={card.label} href={card.href} className="rounded-lg bg-white p-6 shadow-sm hover:ring-1 hover:ring-indigo-200">
                            <dt className="text-sm text-gray-500">{card.label}</dt>
                            <dd className="mt-2 text-3xl font-semibold text-gray-900">{card.value}</dd>
                        </Link>
                    ))}
                </dl>

                <section className="rounded-lg bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h3 className="font-medium text-gray-900">Candidaturas recentes</h3>
                        <Link href={route('panel.applications.index')} className="text-sm text-indigo-600 hover:underline">
                            Ver todas
                        </Link>
                    </div>
                    {recentApplications.length === 0 ? (
                        <p className="px-6 py-8 text-sm text-gray-500">Nenhuma candidatura ainda.</p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {recentApplications.map((application) => (
                                <li key={application.id}>
                                    <Link
                                        href={route('panel.applications.show', application.id)}
                                        className="flex items-center justify-between px-6 py-3 hover:bg-gray-50"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">{application.candidate.name}</p>
                                            <p className="text-xs text-gray-500">
                                                {application.job.title} · {application.stage.name}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            {application.status === 'rejected' && (
                                                <Badge tone="red">{applicationStatusLabels.rejected}</Badge>
                                            )}
                                            <span className="text-xs text-gray-400">{formatDate(application.created_at)}</span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
