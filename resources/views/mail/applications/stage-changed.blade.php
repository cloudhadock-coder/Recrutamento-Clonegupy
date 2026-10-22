<x-mail::message>
# Olá, {{ $candidateName }}!

Temos novidades sobre a sua candidatura para **{{ $jobTitle }}** na **{{ $companyName }}**.

Você avançou para a etapa **{{ $stageName }}**. Em breve a equipe de recrutamento entrará em contato com os próximos passos.

<x-mail::button :url="$applicationsUrl">
Ver minhas candidaturas
</x-mail::button>

Boa sorte!<br>
Equipe {{ $companyName }}
</x-mail::message>
