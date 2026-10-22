import FlashMessages from '@/Components/FlashMessages';
import { Link, usePage } from '@inertiajs/react';

/**
 * Layout do portal de carreiras público de uma empresa.
 */
export default function PortalLayout({ company, children }) {
    const { candidate } = usePage().props.auth;

    return (
        <div className="flex min-h-screen flex-col bg-gray-50">
            <header className="border-b border-gray-200 bg-white">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                    <Link href={route('portal.jobs.index', company.slug)} className="text-xl font-bold text-gray-900">
                        {company.name}
                        <span className="ms-2 text-sm font-normal text-gray-500">Trabalhe conosco</span>
                    </Link>

                    <nav className="flex items-center gap-4 text-sm">
                        {candidate ? (
                            <>
                                <Link href={route('candidate.applications.index')} className="text-gray-700 hover:text-indigo-700">
                                    Minhas candidaturas
                                </Link>
                                <Link
                                    href={route('candidate.logout')}
                                    method="post"
                                    as="button"
                                    className="text-gray-500 hover:text-gray-700"
                                >
                                    Sair
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link href={route('candidate.login')} className="text-gray-700 hover:text-indigo-700">
                                    Entrar
                                </Link>
                                <Link
                                    href={route('candidate.register')}
                                    className="rounded-md bg-indigo-600 px-3 py-1.5 font-medium text-white hover:bg-indigo-500"
                                >
                                    Criar conta
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6">
                <FlashMessages className="mb-6" />
                {children}
            </main>

            <footer className="border-t border-gray-200 bg-white py-6 text-center text-xs text-gray-500">
                {company.name} · Portal de carreiras
            </footer>
        </div>
    );
}
