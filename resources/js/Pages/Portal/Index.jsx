import Pagination from '@/Components/Pagination';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import PortalLayout from '@/Layouts/PortalLayout';
import { employmentTypeLabels, formatDate, jobLocation } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ company, jobs, filters, cities, workModels }) {
    const [term, setTerm] = useState(filters.q ?? '');

    const search = (changes = {}) => {
        const params = Object.fromEntries(
            Object.entries({ ...filters, q: term, ...changes }).filter(([, value]) => value !== '' && value != null),
        );

        router.get(route('portal.jobs.index', company.slug), params, { preserveState: true, preserveScroll: true });
    };

    const hasFilters = Boolean(filters.q || filters.cidade || filters.modelo);

    return (
        <PortalLayout company={company}>
            <Head title={`Vagas na ${company.name}`}>
                <meta name="description" content={`Vagas abertas na ${company.name}. ${company.about ?? ''}`.trim()} />
            </Head>

            <section className="mb-8">
                <h1 className="text-3xl font-bold tracking-tight text-gray-900">Vagas abertas</h1>
                {company.about && <p className="mt-2 max-w-3xl text-gray-600">{company.about}</p>}
            </section>

            <form
                role="search"
                className="mb-6 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-[1fr_200px_160px_auto]"
                onSubmit={(e) => {
                    e.preventDefault();
                    search();
                }}
            >
                <TextInput
                    type="search"
                    value={term}
                    aria-label="Buscar vagas"
                    placeholder="Cargo, tecnologia ou palavra-chave"
                    onChange={(e) => setTerm(e.target.value)}
                />
                <SelectInput
                    aria-label="Cidade"
                    value={filters.cidade ?? ''}
                    placeholder="Todas as cidades"
                    options={cities}
                    onChange={(e) => search({ cidade: e.target.value })}
                />
                <SelectInput
                    aria-label="Modelo de trabalho"
                    value={filters.modelo ?? ''}
                    placeholder="Todos os modelos"
                    options={workModels}
                    onChange={(e) => search({ modelo: e.target.value })}
                />
                <button type="submit" className="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Buscar
                </button>
            </form>

            <div className="mb-4 flex items-center justify-between text-sm text-gray-600">
                <span>{jobs.total === 1 ? '1 vaga encontrada' : `${jobs.total} vagas encontradas`}</span>
                {hasFilters && (
                    <Link href={route('portal.jobs.index', company.slug)} className="text-indigo-600 hover:underline">
                        Limpar filtros
                    </Link>
                )}
            </div>

            {jobs.data.length === 0 ? (
                <p className="rounded-lg bg-white p-10 text-center text-gray-500 ring-1 ring-gray-200">
                    Nenhuma vaga encontrada com esses filtros.
                </p>
            ) : (
                <ul className="space-y-3">
                    {jobs.data.map((job) => (
                        <li key={job.id}>
                            <Link
                                href={route('portal.jobs.show', [company.slug, job.slug])}
                                className="block rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:ring-indigo-300"
                            >
                                <h2 className="text-lg font-semibold text-gray-900">{job.title}</h2>
                                <p className="mt-1 text-sm text-gray-600">
                                    {jobLocation(job)} · {employmentTypeLabels[job.employment_type]}
                                </p>
                                <p className="mt-2 text-xs text-gray-400">Publicada em {formatDate(job.published_at)}</p>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination links={jobs.links} className="mt-6" />
        </PortalLayout>
    );
}
