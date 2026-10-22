<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:4f46e5,100:7c3aed&height=200&section=header&text=Recrutamento&fontSize=64&fontColor=ffffff&fontAlignY=36&desc=ATS%20multiempresa%20inspirado%20na%20Gupy&descAlignY=58&descSize=20&animation=fadeIn" alt="Recrutamento" width="100%" />

<a href="#-demonstração">
  <img src="https://readme-typing-svg.demolab.com?font=Inter&weight=600&size=22&duration=2800&pause=900&color=4F46E5&center=true&vCenter=true&width=640&lines=Publique+vagas+no+seu+portal+de+carreiras;Receba+curr%C3%ADculos+em+PDF;Arraste+candidatos+pelo+kanban;Tudo+isolado+por+empresa+%F0%9F%94%92" alt="Publique vagas, receba currículos e arraste candidatos pelo kanban" />
</a>

<br/>

<img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13" />
<img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3" />
<img src="https://img.shields.io/badge/React-18-61DAFB?style=for-the-badge&logo=react&logoColor=black" alt="React 18" />
<img src="https://img.shields.io/badge/Inertia-2-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia 2" />
<img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL 16" />
<img src="https://img.shields.io/badge/Redis-7-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis 7" />
<img src="https://img.shields.io/badge/testes-91%20passando-22c55e?style=for-the-badge&logo=checkmarx&logoColor=white" alt="91 testes passando" />

<br/><br/>

<a href="#-sobre">Sobre</a> •
<a href="#-demonstração">Demonstração</a> •
<a href="#-funcionalidades">Funcionalidades</a> •
<a href="#-arquitetura">Arquitetura</a> •
<a href="#-como-rodar">Como rodar</a> •
<a href="#-testes">Testes</a> •
<a href="#-próximos-passos">Próximos passos</a>

</div>

---

## 💼 Sobre

**Recrutamento** é um ATS (*Applicant Tracking System*) multiempresa feito do zero em Laravel. Cada empresa ganha um portal de carreiras próprio, o candidato se inscreve com currículo em PDF, e o time de RH acompanha cada pessoa num kanban de etapas, do "Inscritos" até o "Contratados".

O projeto nasceu como estudo de um clone da Gupy: mesma ideia, código próprio, foco em boas práticas de Laravel, PostgreSQL e testes.

> [!NOTE]
> Projeto de estudo, sem nenhum vínculo com a Gupy.

---

## 🎬 Demonstração

<div align="center">

**Arrastando uma candidatura de "Inscritos" para "Triagem"**

<img src=".github/assets/kanban.gif" alt="Kanban: card arrastado de Inscritos para Triagem" width="100%" />

<sub>Cada movimento grava um evento no histórico da candidatura e envia um e-mail ao candidato pela fila.</sub>

</div>

### 🖼️ Telas

<table>
  <tr>
    <td width="50%" align="center">
      <img src=".github/assets/portal-vagas.png" alt="Portal de carreiras com a lista de vagas abertas" /><br/>
      <b>Portal de carreiras</b><br/><sub><code>/acme/vagas</code>, com busca por texto, cidade e modelo de trabalho</sub>
    </td>
    <td width="50%" align="center">
      <img src=".github/assets/portal-vaga.png" alt="Página pública de uma vaga" /><br/>
      <b>Página da vaga</b><br/><sub>Descrição, requisitos, benefícios e o botão "Quero me candidatar"</sub>
    </td>
  </tr>
  <tr>
    <td width="50%" align="center">
      <img src=".github/assets/painel-inicio.png" alt="Painel do recrutador com indicadores" /><br/>
      <b>Painel do recrutador</b><br/><sub>Vagas abertas, candidaturas em andamento e as mais recentes</sub>
    </td>
    <td width="50%" align="center">
      <img src=".github/assets/painel-vagas.png" alt="Lista de vagas no painel" /><br/>
      <b>Gestão de vagas</b><br/><sub>Rascunho, publicada ou pausada, com atalhos para kanban e etapas</sub>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <img src=".github/assets/painel-candidatura.png" alt="Detalhe da candidatura com histórico e consentimento LGPD" width="75%" /><br/>
      <b>Detalhe da candidatura</b><br/><sub>Respostas, texto extraído do currículo, histórico de etapas e o consentimento LGPD registrado com data</sub>
    </td>
  </tr>
</table>

---

## ✨ Funcionalidades

<table>
  <tr>
    <td valign="top" width="33%">

### 🏢 Para a empresa
- Cadastro com **CNPJ**: os dados chegam preenchidos pela BrasilAPI
- Endereço automático pelo **CEP** (ViaCEP)
- Papéis de usuário: **admin**, **recrutador** e **gestor**
- Portal de carreiras próprio em `/{slug}/vagas`

</td>
    <td valign="top" width="33%">

### 🧑‍💼 Para o recrutador
- Cria, edita, **publica** e **pausa** vagas
- Etapas padrão em toda vaga nova, todas editáveis
- **Kanban com arrastar e soltar**
- Reprova e reativa candidaturas
- Histórico completo de cada movimento

</td>
    <td valign="top" width="33%">

### 🙋 Para o candidato
- Uma conta só para se candidatar em **várias empresas**
- Upload de currículo em **PDF** e perguntas da vaga
- Candidatura duplicada é bloqueada
- Acompanha as próprias candidaturas
- **Exclui ou anonimiza** a conta (LGPD)

</td>
  </tr>
</table>

**Por baixo do capô:**

| | |
|---|---|
| 🔒 **Multiempresa de verdade** | Toda tabela de negócio tem `company_id`; a trait `BelongsToCompany` aplica um global scope e as Policies fecham o resto. Um teste garante que uma empresa não enxerga dados de outra. |
| 🔎 **Busca sem Elasticsearch** | Coluna `tsvector` gerada em português com índice GIN, mais `pg_trgm` para buscas por trecho. |
| 📄 **Leitura do currículo** | Um job na fila roda o `pdftotext` e guarda o texto do PDF na candidatura. |
| ✉️ **E-mails em fila** | Mudança de etapa e reprovação avisam o candidato. Driver `log` em dev, Resend em produção. |
| ☁️ **Arquivos onde você quiser** | Disco local em dev; S3 ou Cloudflare R2 em produção. |
| ⚡ **Rápido e leve** | Portal renderizado com SSR, listas com paginação por cursor e `preventLazyLoading` ativo fora de produção. |
| 🗺️ **Cidades do IBGE** | Os 5.571 municípios são importados uma vez para uma tabela local. |

---

## 🏗️ Arquitetura

```mermaid
flowchart LR
    subgraph Navegador
        P["🌐 Portal de carreiras<br/>(SSR)"]
        R["🧑‍💼 Painel do recrutador"]
        C["🙋 Área do candidato"]
    end

    subgraph Laravel["Laravel 13 + Inertia"]
        W["Rotas e controllers"]
        Q["Filas (Horizon)"]
    end

    P & R & C --> W
    W --> DB[("PostgreSQL 16<br/>tsvector · pg_trgm · citext")]
    W --> RD[("Redis")]
    W -- currículo PDF --> S[("Storage<br/>local / S3 / R2")]
    RD --> Q
    Q -- pdftotext --> S
    Q -- e-mails --> M["📧 Log / Resend"]
    W -. CNPJ / CEP / cidades .-> API["BrasilAPI · ViaCEP · IBGE"]
```

### Jornada de uma candidatura

```mermaid
sequenceDiagram
    actor Cand as Candidato
    participant Portal
    participant App as Laravel
    participant Fila
    actor RH as Recrutador

    Cand->>Portal: Abre /acme/vagas e escolhe a vaga
    Cand->>App: Envia PDF, respostas e consentimento LGPD
    App->>App: Bloqueia candidatura duplicada
    App->>Fila: Extrair texto do currículo
    RH->>App: Arrasta o card para "Entrevista com RH"
    App->>App: Grava o evento em application_events
    App->>Fila: E-mail de mudança de etapa
    Fila-->>Cand: 📧 "Sua candidatura avançou!"
```

### Modelo de dados

```mermaid
erDiagram
    COMPANIES ||--o{ USERS : "tem"
    COMPANIES ||--o{ JOBS : "publica"
    JOBS ||--o{ JOB_STAGES : "tem etapas"
    JOBS ||--o{ APPLICATIONS : "recebe"
    CANDIDATES ||--o{ APPLICATIONS : "envia"
    JOB_STAGES ||--o{ APPLICATIONS : "está em"
    APPLICATIONS ||--o{ APPLICATION_EVENTS : "registra"
    CITIES ||--o{ JOBS : "localiza"
```

**Etapas padrão de toda vaga nova:** `Inscritos` → `Triagem` → `Entrevista com RH` → `Entrevista técnica` → `Proposta` → `Contratados`

---

## 🧰 Stack

<div align="center">

<img src="https://skillicons.dev/icons?i=laravel,php,react,tailwind,vite,postgres,redis,docker&perline=8" alt="Laravel, PHP, React, Tailwind, Vite, PostgreSQL, Redis, Docker" />

</div>

| Camada | Tecnologias |
|---|---|
| **Back-end** | Laravel 13, PHP 8.3, Laravel Horizon, Pest 4 |
| **Front-end** | Inertia 2, React 18, Tailwind CSS, dnd-kit, Ziggy |
| **Dados** | PostgreSQL 16 (`pg_trgm`, `citext`, `tsvector`), Redis 7 |
| **Integrações** | BrasilAPI (CNPJ), ViaCEP, IBGE Localidades, Resend, S3/R2 |

---

## 🚀 Como rodar

### Pré-requisitos

- PHP 8.3 e Composer
- Node.js e npm
- Docker (para o PostgreSQL e o Redis)
- `pdftotext` (opcional, para ler o texto dos currículos; vem no Poppler ou no Xpdf)

### Passo a passo

```bash
# 1. Clone o projeto
git clone https://github.com/cloudhadock-coder/Recrutamento-Clonegupy.git
cd Recrutamento-Clonegupy

# 2. Instale as dependências
composer install
npm install

# 3. Configure o ambiente
cp .env.example .env
php artisan key:generate

# 4. Suba o PostgreSQL e o Redis
docker compose up -d

# 5. Crie o schema com os dados de exemplo (inclui a importação das cidades do IBGE)
php artisan migrate --seed

# 6. Compile o front-end (cliente + SSR)
npm run build

# 7. Rode a aplicação
php artisan dev
```

Abra **http://localhost:8000** 🎉

> [!TIP]
> As filas rodam no Redis. Para os e-mails saírem e os currículos serem lidos, deixe o `php artisan horizon` rodando. Em dev, os e-mails aparecem em `storage/logs/laravel.log`.

### 🔑 Contas de demonstração

Todas usam a senha **`password`**.

| Perfil | Onde entrar | E-mail |
|---|---|---|
| 👑 Admin da Acme | `/login` | `admin@acme.test` |
| 🧑‍💼 Recrutador da Acme | `/login` | `recrutador@acme.test` |
| 📊 Gestor da Acme | `/login` | `gestor@acme.test` |
| 🏪 Recrutador do Bom Preço | `/login` | `recrutador@bompreco.test` |
| 🙋 Candidata | `/candidato/entrar` | `candidata@example.com` |

Portais públicos: **`/acme/vagas`** e **`/bompreco/vagas`**.

---

## 🧪 Testes

Os testes usam Pest e um banco PostgreSQL separado, o `recrutamento_test`. Crie esse banco uma vez:

```bash
docker compose exec pgsql createdb -U recrutamento recrutamento_test
```

Depois é só rodar:

```bash
php artisan test --compact
```

```
Tests:    1 skipped, 91 passed (321 assertions)
```

O que a suíte cobre:

- 🔒 **Isolamento entre empresas**: um recrutador não abre vaga nem candidatura de outra empresa
- 🚫 **Candidatura duplicada**: bloqueada no controller e também por constraint `unique` no banco
- 🔀 **Mudança de etapa**: grava o evento e coloca o e-mail na fila
- 🧹 **LGPD**: exclusão e anonimização da conta do candidato
- 🧾 **CNPJ**: validação e consulta na BrasilAPI
- 📄 **Currículo**: extração do texto do PDF (pulada se o `pdftotext` não estiver instalado)

---

## 📂 Estrutura

```
app/
├── Http/Controllers/
│   ├── Panel/       # painel do recrutador: vagas, etapas, candidaturas, kanban
│   ├── Portal/      # portal de carreiras público e fluxo de candidatura
│   └── Candidate/   # login, conta e candidaturas do candidato
├── Models/Concerns/BelongsToCompany.php   # global scope multiempresa
├── Policies/        # JobPolicy, ApplicationPolicy
├── Services/        # ApplicationPipeline, CandidateAccountEraser, BrasilApi, ViaCep, IBGE
├── Jobs/            # ExtractResumeText
└── Mail/            # ApplicationStageChanged, ApplicationRejected
resources/js/Pages/
├── Panel/           # Jobs (Index, Form, Kanban, Stages), Applications
├── Portal/          # Index, Show, Apply
└── Candidate/       # Login, Register, Applications, Account
```

---

## 🛣️ Próximos passos

Ideias que ficaram fora do MVP:

- [ ] 🤖 Triagem de currículos com IA (pgvector)
- [ ] 🧠 Testes comportamentais
- [ ] 📝 Admissão digital
- [ ] 📅 Agendamento de entrevistas com Google Calendar
- [ ] 🚄 Laravel Octane

---

<div align="center">

Feito com ☕ e muito `php artisan` por [@cloudhadock-coder](https://github.com/cloudhadock-coder)

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:7c3aed,100:4f46e5&height=110&section=footer" alt="" width="100%" />

</div>
