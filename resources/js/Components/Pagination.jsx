import { Link } from '@inertiajs/react';

/**
 * Paginação de paginadores com total (links numerados).
 */
export default function Pagination({ links = [], className = '' }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className={'flex flex-wrap gap-1 ' + className} aria-label="Paginação">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? ''}
                    preserveScroll
                    disabled={!link.url}
                    className={
                        'rounded-md px-3 py-1.5 text-sm ' +
                        (link.active
                            ? 'bg-indigo-600 text-white'
                            : link.url
                              ? 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                              : 'pointer-events-none text-gray-400')
                    }
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </nav>
    );
}

/**
 * Paginação por cursor: apenas "anterior" e "próxima".
 */
export function CursorPagination({ paginator, className = '' }) {
    if (!paginator.prev_page_url && !paginator.next_page_url) {
        return null;
    }

    const button = (url, label) => (
        <Link
            href={url ?? ''}
            preserveScroll
            disabled={!url}
            className={
                'rounded-md px-4 py-2 text-sm ring-1 ring-gray-200 ' +
                (url ? 'bg-white text-gray-700 hover:bg-gray-50' : 'pointer-events-none bg-gray-50 text-gray-400')
            }
        >
            {label}
        </Link>
    );

    return (
        <nav className={'flex justify-between ' + className} aria-label="Paginação">
            {button(paginator.prev_page_url, '← Anteriores')}
            {button(paginator.next_page_url, 'Próximas →')}
        </nav>
    );
}
