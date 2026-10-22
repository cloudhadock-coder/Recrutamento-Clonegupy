import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashMessages from '@/Components/FlashMessages';
import NavLink from '@/Components/NavLink';
import { Link, usePage } from '@inertiajs/react';

/**
 * Layout da área logada do candidato.
 */
export default function CandidateLayout({ title, children }) {
    const { candidate } = usePage().props.auth;

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="border-b border-gray-200 bg-white">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <div className="flex h-full items-center gap-8">
                        <Link href="/">
                            <ApplicationLogo className="h-8 w-auto fill-current text-gray-800" />
                        </Link>
                        <div className="flex h-full gap-6">
                            <NavLink
                                href={route('candidate.applications.index')}
                                active={route().current('candidate.applications.*')}
                            >
                                Minhas candidaturas
                            </NavLink>
                            <NavLink href={route('candidate.account.edit')} active={route().current('candidate.account.*')}>
                                Minha conta
                            </NavLink>
                        </div>
                    </div>

                    <div className="flex items-center gap-4 text-sm">
                        <span className="hidden text-gray-600 sm:inline">{candidate.name}</span>
                        <Link
                            href={route('candidate.logout')}
                            method="post"
                            as="button"
                            className="text-gray-500 hover:text-gray-700"
                        >
                            Sair
                        </Link>
                    </div>
                </div>
            </nav>

            <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                {title && <h1 className="mb-6 text-2xl font-semibold text-gray-900">{title}</h1>}
                <FlashMessages className="mb-6" />
                {children}
            </main>
        </div>
    );
}
