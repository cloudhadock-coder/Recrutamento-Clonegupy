import PortalLayout from '@/Layouts/PortalLayout';
import { employmentTypeLabels, formatDate, formatSalary, jobLocation } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

function Section({ title, children }) {
    if (!children) {
        return null;
    }

    return (
        <section className="mt-8">
            <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
            <div className="mt-2 whitespace-pre-line text-gray-700">{children}</div>
        </section>
    );
}

export default function Show({ company, job, questionsCount, alreadyApplied }) {
    const salary = formatSalary(job.salary_min, job.salary_max);

    return (
        <PortalLayout company={company}>
            <Head title={`${job.title} · ${company.name}`}>
                <meta name="description" content={job.description.slice(0, 160)} />
            </Head>

            <Link href={route('portal.jobs.index', company.slug)} className="text-sm text-indigo-600 hover:underline">
                ← Todas as vagas
            </Link>

            <article className="mt-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                <header className="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{job.title}</h1>
                        <p className="mt-2 text-gray-600">
                            {jobLocation(job)} · {employmentTypeLabels[job.employment_type]}
                        </p>
                        {salary && <p className="mt-1 text-sm font-medium text-gray-800">{salary}</p>}
                        <p className="mt-1 text-xs text-gray-400">Publicada em {formatDate(job.published_at)}</p>
                    </div>

                    {alreadyApplied ? (
                        <Link
                            href={route('candidate.applications.index')}
                            className="rounded-md bg-green-50 px-5 py-3 text-sm font-semibold text-green-800 ring-1 ring-green-200"
                        >
                            Você já se candidatou ✓
                        </Link>
                    ) : (
                        <Link
                            href={route('portal.apply.create', [company.slug, job.slug])}
                            className="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-indigo-500"
                        >
                            Quero me candidatar
                        </Link>
                    )}
                </header>

                <Section title="Sobre a vaga">{job.description}</Section>
                <Section title="Requisitos">{job.requirements}</Section>
                <Section title="Benefícios">{job.benefits}</Section>

                {!alreadyApplied && (
                    <p className="mt-10 text-sm text-gray-500">
                        Para se candidatar você precisa de uma conta, do currículo em PDF
                        {questionsCount > 0 && ` e responder ${questionsCount === 1 ? '1 pergunta' : `${questionsCount} perguntas`}`}.
                    </p>
                )}
            </article>
        </PortalLayout>
    );
}
