<?php

namespace App\Http\Requests;

use App\Enums\EstadoCarta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filtros opcionales de GET /api/cartas?estado=&ciudad= */
class ListarCartasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->query('estado'))) {
            $this->merge(['estado' => strtoupper(trim($this->query('estado')))]);
        }
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', 'string', Rule::enum(EstadoCarta::class)],
            'ciudad' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.string' => 'El filtro estado debe ser texto.',
            'estado.enum' => 'El filtro estado debe ser uno de: '.implode(', ', EstadoCarta::valores()).'.',
            'ciudad.string' => 'El filtro ciudad debe ser texto.',
            'ciudad.max' => 'El filtro ciudad no puede tener más de 100 caracteres.',
        ];
    }

    public function estado(): ?EstadoCarta
    {
        $valor = $this->validated('estado');

        return $valor === null ? null : EstadoCarta::from($valor);
    }

    public function ciudad(): ?string
    {
        return $this->validated('ciudad');
    }
}
