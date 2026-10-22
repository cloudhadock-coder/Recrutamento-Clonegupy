import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FlashMessages({ className = '' }) {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(true);

    useEffect(() => setVisible(true), [flash?.success, flash?.error]);

    if (!visible || (!flash?.success && !flash?.error)) {
        return null;
    }

    const isError = Boolean(flash.error);

    return (
        <div
            role={isError ? 'alert' : 'status'}
            className={
                'flex items-start justify-between gap-4 rounded-md px-4 py-3 text-sm ' +
                (isError
                    ? 'bg-red-50 text-red-800 ring-1 ring-red-200'
                    : 'bg-green-50 text-green-800 ring-1 ring-green-200') +
                ' ' +
                className
            }
        >
            <span>{flash.error ?? flash.success}</span>
            <button
                type="button"
                onClick={() => setVisible(false)}
                className="font-semibold opacity-60 hover:opacity-100"
                aria-label="Fechar"
            >
                ×
            </button>
        </div>
    );
}
