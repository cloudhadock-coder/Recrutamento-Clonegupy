<?php

namespace Database\Seeders;

use App\Enums\ApplicationEventType;
use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkModel;
use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\Candidate;
use App\Models\City;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Dados de exemplo: 2 empresas com recrutadores, vagas, candidatos e candidaturas em várias etapas.
 *
 * Todos os usuários e candidatos usam a senha "password".
 */
class DemoSeeder extends Seeder
{
    private const SAO_PAULO = 3550308;

    private const CAMPINAS = 3509502;

    private const CURITIBA = 4106902;

    private const BELO_HORIZONTE = 3106200;

    public function run(): void
    {
        $acme = Company::create([
            'name' => 'Acme Tecnologia Ltda', 'trade_name' => 'Acme Tecnologia', 'slug' => 'acme', 'cnpj' => '11222333000181',
            'email' => 'contato@acme.test', 'phone' => '1133334444', 'cep' => '01310100', 'street' => 'Avenida Paulista',
            'number' => '1000', 'neighborhood' => 'Bela Vista', 'city_id' => self::SAO_PAULO, 'state' => 'SP',
            'about' => 'Software para o varejo brasileiro desde 2010. Somos 300 pessoas em times remotos e híbridos.',
        ]);

        $bomPreco = Company::create([
            'name' => 'Supermercados Bom Preço S.A.', 'trade_name' => 'Bom Preço', 'slug' => 'bompreco', 'cnpj' => '45997418000153',
            'email' => 'rh@bompreco.test', 'phone' => '4133335555', 'cep' => '80020000', 'street' => 'Rua XV de Novembro',
            'number' => '500', 'neighborhood' => 'Centro', 'city_id' => self::CURITIBA, 'state' => 'PR',
            'about' => 'Rede de supermercados com 40 lojas no Sul e Sudeste.',
        ]);

        $this->seedUsers($acme);
        $this->seedUsers($bomPreco);

        $acmeJobs = collect([
            $this->job($acme, 'Desenvolvedor(a) Back-end PHP Pleno', WorkModel::Remote, null, 'Laravel, PostgreSQL, filas com Redis e testes automatizados.', JobStatus::Published),
            $this->job($acme, 'Desenvolvedor(a) Front-end React Sênior', WorkModel::Hybrid, self::SAO_PAULO, 'React, TypeScript, design systems e acessibilidade.', JobStatus::Published),
            $this->job($acme, 'Analista de Dados Júnior', WorkModel::Hybrid, self::CAMPINAS, 'SQL, Python e Power BI para apoiar o time comercial.', JobStatus::Published),
            $this->job($acme, 'Product Designer', WorkModel::Remote, null, 'Pesquisa com usuários, Figma e prototipação.', JobStatus::Published),
            $this->job($acme, 'Engenheiro(a) DevOps', WorkModel::Onsite, self::SAO_PAULO, 'Kubernetes, Terraform e observabilidade.', JobStatus::Paused),
            $this->job($acme, 'Estágio em Desenvolvimento', WorkModel::Hybrid, self::CAMPINAS, 'Lógica de programação e vontade de aprender.', JobStatus::Draft, EmploymentType::Internship),
        ]);

        $bomPrecoJobs = collect([
            $this->job($bomPreco, 'Operador(a) de Caixa', WorkModel::Onsite, self::CURITIBA, 'Atendimento ao cliente e disponibilidade para escala 6x1.', JobStatus::Published),
            $this->job($bomPreco, 'Gerente de Loja', WorkModel::Onsite, self::BELO_HORIZONTE, 'Experiência com gestão de equipes no varejo alimentar.', JobStatus::Published),
            $this->job($bomPreco, 'Analista de RH', WorkModel::Hybrid, self::CURITIBA, 'Recrutamento e seleção, departamento pessoal e eSocial.', JobStatus::Published),
            $this->job($bomPreco, 'Repositor(a) de Mercadorias', WorkModel::Onsite, self::CURITIBA, 'Organização e controle de validade.', JobStatus::Paused),
        ]);

        $jobsWithApplications = $acmeJobs->merge($bomPrecoJobs)->filter(fn (Job $job): bool => $job->published_at !== null);

        $candidates = Candidate::factory()->count(24)->create();

        $candidates->first()->update(['name' => 'Maria Candidata', 'email' => 'candidata@example.com']);

        foreach ($candidates as $index => $candidate) {
            foreach ($jobsWithApplications->shuffle()->take(($index % 3) + 1) as $job) {
                $this->apply($candidate, $job);
            }
        }
    }

    private function seedUsers(Company $company): void
    {
        $logins = ['admin' => UserRole::Admin, 'recrutador' => UserRole::Recruiter, 'gestor' => UserRole::Manager];

        foreach ($logins as $login => $role) {
            User::factory()->for($company)->create([
                'email' => "{$login}@{$company->slug}.test",
                'role' => $role,
            ]);
        }
    }

    private function job(Company $company, string $title, WorkModel $workModel, ?int $cityId, string $requirements, JobStatus $status, EmploymentType $employmentType = EmploymentType::Clt): Job
    {
        $job = Job::create([
            'company_id' => $company->id,
            'city_id' => $cityId !== null && City::query()->whereKey($cityId)->exists() ? $cityId : null,
            'title' => $title,
            'description' => "Estamos procurando uma pessoa para a posição de {$title} na {$company->displayName()}. "
                .'Você vai trabalhar com um time colaborativo, com autonomia e foco em resultado para nossos clientes.',
            'requirements' => $requirements,
            'benefits' => 'Vale-refeição, plano de saúde e odontológico, Gympass e participação nos lucros.',
            'work_model' => $workModel,
            'employment_type' => $employmentType,
            'salary_min' => fake()->randomElement([2500, 4000, 6500, 9000]),
            'questions' => [
                ['id' => 'q_pretensao', 'label' => 'Qual a sua pretensão salarial?', 'type' => 'text', 'required' => true],
                ['id' => 'q_inicio', 'label' => 'Tem disponibilidade para início imediato?', 'type' => 'yes_no', 'required' => true],
            ],
        ]);

        if ($status !== JobStatus::Draft) {
            $job->forceFill(['status' => $status, 'published_at' => now()->subDays(fake()->numberBetween(1, 25))])->save();
        }

        return $job->load('stages');
    }

    private function apply(Candidate $candidate, Job $job): void
    {
        $stages = $job->stages;
        $stageIndex = fake()->randomElement([0, 0, 0, 1, 1, 2, 2, 3, 4, 5]);
        $appliedAt = now()->subDays(fake()->numberBetween(1, 20));
        $rejected = $stageIndex > 0 && fake()->boolean(20);

        $resumePath = "resumes/{$job->company_id}/seed-{$candidate->id}-{$job->id}.pdf";
        $resumeLines = [
            $candidate->name,
            $candidate->email.' | '.$candidate->phone,
            '',
            'Objetivo: '.$job->title,
            'Experiência: '.fake()->numberBetween(1, 10).' anos em '.fake()->randomElement(['varejo', 'tecnologia', 'atendimento', 'logística']).'.',
            'Formação: '.fake()->randomElement(['Administração', 'Ciência da Computação', 'Ensino médio completo', 'Design']).'.',
        ];
        Storage::disk(config('filesystems.resumes'))->put($resumePath, ResumePdf::make($resumeLines));

        $application = Application::create([
            'company_id' => $job->company_id,
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'job_stage_id' => $stages[$stageIndex]->id,
            'answers' => ['q_pretensao' => 'R$ '.fake()->numberBetween(2, 12).'.000', 'q_inicio' => fake()->randomElement(['sim', 'nao'])],
            'resume_path' => $resumePath,
            'resume_original_name' => 'curriculo.pdf',
            'consent_at' => $appliedAt,
            'consent_version' => Application::CONSENT_VERSION,
            'consent_ip' => '127.0.0.1',
            'stage_entered_at' => $appliedAt->copy()->addDays($stageIndex),
        ]);

        $application->forceFill([
            'created_at' => $appliedAt,
            'resume_text' => implode("\n", $resumeLines),
            'resume_text_extracted_at' => $appliedAt,
            ...($rejected ? ['status' => ApplicationStatus::Rejected, 'rejected_at' => $appliedAt->copy()->addDays($stageIndex + 1)] : []),
        ])->save();

        $recruiterId = User::query()->where('company_id', $job->company_id)->where('role', UserRole::Recruiter)->value('id');

        $events = [[ApplicationEventType::Applied, null, $stages[0]->id, $appliedAt]];

        for ($step = 1; $step <= $stageIndex; $step++) {
            $events[] = [ApplicationEventType::StageChanged, $stages[$step - 1]->id, $stages[$step]->id, $appliedAt->copy()->addDays($step)];
        }

        if ($rejected) {
            $events[] = [ApplicationEventType::Rejected, $stages[$stageIndex]->id, null, $appliedAt->copy()->addDays($stageIndex + 1)];
        }

        foreach ($events as [$type, $fromStageId, $toStageId, $createdAt]) {
            $event = new ApplicationEvent([
                'company_id' => $job->company_id,
                'application_id' => $application->id,
                'user_id' => $type === ApplicationEventType::Applied ? null : $recruiterId,
                'type' => $type,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStageId,
            ]);
            $event->created_at = $createdAt;
            $event->save();
        }
    }
}
