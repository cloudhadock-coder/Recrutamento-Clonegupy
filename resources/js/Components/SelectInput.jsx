import { forwardRef } from 'react';

export default forwardRef(function SelectInput(
    { options = [], placeholder, className = '', ...props },
    ref,
) {
    return (
        <select
            {...props}
            ref={ref}
            className={
                'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ' +
                className
            }
        >
            {placeholder !== undefined && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
});
