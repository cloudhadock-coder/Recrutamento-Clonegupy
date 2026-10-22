import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

/**
 * Autocomplete de cidades (tabela local importada do IBGE).
 * `value` é { id, label } ou null; `onChange` recebe o mesmo formato.
 */
export default function CityPicker({ id, value, onChange, placeholder = 'Digite a cidade', className = '' }) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState([]);
    const [open, setOpen] = useState(false);
    const container = useRef(null);

    useEffect(() => {
        if (term.trim().length < 2) {
            setResults([]);
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(() => {
            axios
                .get(route('lookup.cities'), { params: { q: term }, signal: controller.signal })
                .then((response) => {
                    setResults(response.data);
                    setOpen(true);
                })
                .catch(() => {});
        }, 250);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [term]);

    useEffect(() => {
        const close = (event) => {
            if (!container.current?.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', close);

        return () => document.removeEventListener('mousedown', close);
    }, []);

    if (value) {
        return (
            <div className={'flex items-center justify-between rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm ' + className}>
                <span>{value.label}</span>
                <button type="button" onClick={() => onChange(null)} className="text-gray-500 hover:text-red-600">
                    Trocar
                </button>
            </div>
        );
    }

    return (
        <div ref={container} className={'relative ' + className}>
            <input
                id={id}
                type="text"
                value={term}
                autoComplete="off"
                placeholder={placeholder}
                onChange={(event) => setTerm(event.target.value)}
                onFocus={() => results.length && setOpen(true)}
                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            {open && results.length > 0 && (
                <ul className="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black/5">
                    {results.map((city) => (
                        <li key={city.id}>
                            <button
                                type="button"
                                className="block w-full px-3 py-2 text-left hover:bg-indigo-50"
                                onClick={() => {
                                    onChange(city);
                                    setTerm('');
                                    setOpen(false);
                                }}
                            >
                                {city.label}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
