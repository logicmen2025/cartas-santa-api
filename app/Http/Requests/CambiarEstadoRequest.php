<?php

namespace App\Http\Requests;

use App\Enums\EstadoCarta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Body de PATCH /api/cartas/{id}/estado: { "estado": "APROBADA" } */
class CambiarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('estado'))) {
            $this->merge(['estado' => strtoupper(trim($this->input('estado')))]);
        }
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::enum(EstadoCarta::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debes indicar el nuevo estado.',
            'estado.string' => 'El estado debe ser texto.',
            'estado.enum' => 'El estado debe ser uno de: '.implode(', ', EstadoCarta::valores()).'.',
        ];
    }

    public function nuevoEstado(): EstadoCarta
    {
        return EstadoCarta::from($this->validated('estado'));
    }
}
