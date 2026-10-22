<?php

namespace App\Http\Requests\Panel;

use App\Enums\EmploymentType;
use App\Enums\WorkModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'benefits' => ['nullable', 'string', 'max:5000'],
            'work_model' => ['required', Rule::enum(WorkModel::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'city_id' => [Rule::requiredIf($this->input('work_model') !== WorkModel::Remote->value), 'nullable', 'integer', 'exists:cities,id'],
            'salary_min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'gte:salary_min'],
            'questions' => ['array', 'max:10'],
            'questions.*.id' => ['nullable', 'string', 'max:20'],
            'questions.*.label' => ['required', 'string', 'max:255'],
            'questions.*.type' => ['required', Rule::in(['text', 'yes_no'])],
            'questions.*.required' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'descrição',
            'requirements' => 'requisitos',
            'benefits' => 'benefícios',
            'work_model' => 'modelo de trabalho',
            'employment_type' => 'tipo de contratação',
            'city_id' => 'cidade',
            'salary_min' => 'salário mínimo',
            'salary_max' => 'salário máximo',
            'questions.*.label' => 'pergunta',
        ];
    }

    /**
     * Dados validados, com ids estáveis nas perguntas (as respostas são indexadas por eles).
     *
     * @return array<string, mixed>
     */
    public function jobAttributes(): array
    {
        $data = $this->validated();

        $data['questions'] = array_values(array_map(fn (array $question): array => [
            'id' => $question['id'] ?? 'q_'.Str::lower(Str::random(8)),
            'label' => $question['label'],
            'type' => $question['type'],
            'required' => (bool) ($question['required'] ?? false),
        ], $data['questions'] ?? []));

        if ($data['work_model'] === WorkModel::Remote->value && empty($data['city_id'])) {
            $data['city_id'] = null;
        }

        return $data;
    }
}
