import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashMessages from '@/Components/FlashMessages';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, wide = false }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:py-8">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                </Link>
            </div>

            <div
                className={
                    'mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:rounded-lg ' +
                    (wide ? 'sm:max-w-3xl' : 'sm:max-w-md')
                }
            >
                <FlashMessages className="mb-4" />
                {children}
            </div>
        </div>
    );
}
