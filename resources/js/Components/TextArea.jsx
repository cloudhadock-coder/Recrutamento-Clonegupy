import { forwardRef } from 'react';

export default forwardRef(function TextArea({ className = '', rows = 4, ...props }, ref) {
    return (
        <textarea
            {...props}
            ref={ref}
            rows={rows}
            className={
                'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ' +
                className
            }
        />
    );
});
