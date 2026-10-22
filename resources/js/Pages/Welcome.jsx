import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome({ companies }) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Recrutamento e seleção">
                <meta name="description" content="Publique vagas, receba currículos e conduza o processo seletivo em um kanban." />
            </Head>

            <div className="min-h-screen bg-gray-50">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-3">
                        <ApplicationLogo className="h-9 w-auto fill-current text-indigo-600" />
                        <span className="text-lg font-semibold text-gray-900">Recrutamento</span>
                    </div>
                    <nav className="flex items-center gap-4 text-sm">
                        {auth.user ? (
                            <Link href={route('dashboard')} className="font-medium text-indigo-700">
                                Ir para o painel
                            </Link>
                        ) : (
                            <Link href={route('login')} className="text-gray-700 hover:text-gray-900">
                                Entrar como recrutador
                            </Link>
                        )}
                        {auth.candidate ? (
                            <Link href={route('candidate.applications.index')} className="font-medium text-indigo-700">
                                Minhas candidaturas
                            </Link>
                        ) : (
                            <Link href={route('candidate.login')} className="text-gray-700 hover:text-gray-900">
                                Sou candidato
                            </Link>
                        )}
                    </nav>
                </header>

                <section className="mx-auto max-w-6xl px-6 py-16">
                    <h1 className="max-w-3xl text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        Do anúncio da vaga à contratação, num só lugar.
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-gray-600">
                        Sua empresa ganha um portal de carreiras próprio, recebe currículos em PDF e acompanha cada
                        candidato num kanban de etapas, com e-mails automáticos e registro de tudo o que aconteceu.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-4">
                        <Link
                            href={route('register')}
                            className="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-indigo-500"
                        >
                            Cadastrar minha empresa
                        </Link>
                        <Link
                            href={route('candidate.register')}
                            className="rounded-md bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow ring-1 ring-gray-200 hover:bg-gray-50"
                        >
                            Criar conta de candidato
                        </Link>
                    </div>
                </section>

                {companies.length > 0 && (
                    <section className="mx-auto max-w-6xl px-6 pb-20">
                        <h2 className="text-lg font-semibold text-gray-900">Empresas contratando</h2>
                        <ul className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {companies.map((company) => (
                                <li key={company.slug}>
                                    <Link
                                        href={route('portal.jobs.index', company.slug)}
                                        className="block rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300"
                                    >
                                        <p className="font-medium text-gray-900">{company.name}</p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {company.open_jobs_count === 1
                                                ? '1 vaga aberta'
                                                : `${company.open_jobs_count} vagas abertas`}
                                        </p>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
