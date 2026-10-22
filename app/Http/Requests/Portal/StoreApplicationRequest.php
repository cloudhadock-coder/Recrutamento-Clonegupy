<?php

namespace App\Http\Requests\Portal;

use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Cada pergunta da vaga vira uma regra em answers.{id da pergunta}.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Job $job */
        $job = $this->route('job');

        $rules = [
            'resume' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'answers' => ['array'],
            'consent' => ['accepted'],
        ];

        foreach ($job->questions as $question) {
            $rules["answers.{$question['id']}"] = [
                $question['required'] ? 'required' : 'nullable',
                'string',
                $question['type'] === 'yes_no' ? 'in:sim,nao' : 'max:2000',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        /** @var Job $job */
        $job = $this->route('job');

        return [
            'resume' => 'currículo',
            ...collect($job->questions)->mapWithKeys(fn (array $question): array => ["answers.{$question['id']}" => 'resposta'])->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume.mimes' => 'Envie o currículo em PDF.',
            'consent.accepted' => 'Você precisa autorizar o tratamento dos seus dados para se candidatar.',
        ];
    }

    /**
     * Respostas somente das perguntas da vaga, indexadas pelo id da pergunta.
     *
     * @return array<string, string>
     */
    public function answers(): array
    {
        /** @var Job $job */
        $job = $this->route('job');

        return collect($job->questions)
            ->mapWithKeys(fn (array $question): array => [$question['id'] => $this->validated("answers.{$question['id']}")])
            ->filter(fn (?string $answer): bool => $answer !== null && $answer !== '')
            ->all();
    }
}
