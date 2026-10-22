const tones = {
    gray: 'bg-gray-100 text-gray-700',
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-100 text-red-700',
    indigo: 'bg-indigo-100 text-indigo-700',
};

export const jobStatusTone = {
    draft: 'gray',
    published: 'green',
    paused: 'yellow',
    closed: 'red',
};

export default function Badge({ tone = 'gray', className = '', children }) {
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${tones[tone]} ${className}`}>
            {children}
        </span>
    );
}
