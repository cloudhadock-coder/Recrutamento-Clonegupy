<x-mail::message>
# Olá, {{ $candidateName }}

Agradecemos muito o seu interesse na vaga **{{ $jobTitle }}** na **{{ $companyName }}** e o tempo dedicado ao processo seletivo.

Depois de uma análise cuidadosa, decidimos seguir com outros perfis nesta vaga. Essa decisão não diminui a sua trajetória, e esperamos encontrar você em outras oportunidades.

<x-mail::button :url="$portalUrl">
Ver outras vagas
</x-mail::button>

Um abraço,<br>
Equipe {{ $companyName }}
</x-mail::message>
