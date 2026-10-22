// Fuso fixo para que SSR e navegador renderizem a mesma data (sem erro de hidratação).
const TIME_ZONE = 'America/Sao_Paulo';

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    timeZone: TIME_ZONE,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
    timeZone: TIME_ZONE,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const moneyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

export function formatDate(value) {
    return value ? dateFormatter.format(new Date(value)) : '';
}

export function formatDateTime(value) {
    return value ? dateTimeFormatter.format(new Date(value)) : '';
}

export function formatSalary(min, max) {
    if (min && max) {
        return `${moneyFormatter.format(min)} a ${moneyFormatter.format(max)}`;
    }

    if (min) {
        return `A partir de ${moneyFormatter.format(min)}`;
    }

    if (max) {
        return `Até ${moneyFormatter.format(max)}`;
    }

    return null;
}

export function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

export function maskCnpj(value) {
    return onlyDigits(value)
        .slice(0, 14)
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

export function maskCep(value) {
    return onlyDigits(value)
        .slice(0, 8)
        .replace(/^(\d{5})(\d)/, '$1-$2');
}

export function maskPhone(value) {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length <= 10) {
        return digits.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
    }

    return digits.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
}

export const workModelLabels = {
    presencial: 'Presencial',
    hibrido: 'Híbrido',
    remoto: 'Remoto',
};

export const employmentTypeLabels = {
    clt: 'CLT',
    pj: 'PJ',
    estagio: 'Estágio',
    temporario: 'Temporário',
};

export const jobStatusLabels = {
    draft: 'Rascunho',
    published: 'Publicada',
    paused: 'Pausada',
    closed: 'Encerrada',
};

export const applicationStatusLabels = {
    active: 'Em andamento',
    rejected: 'Reprovada',
};

export function cityLabel(city) {
    return city ? `${city.name} - ${city.state}` : null;
}

export function jobLocation(job) {
    const city = cityLabel(job.city);

    if (job.work_model === 'remoto') {
        return city ? `Remoto (${city})` : 'Remoto';
    }

    return [workModelLabels[job.work_model], city].filter(Boolean).join(' · ');
}
