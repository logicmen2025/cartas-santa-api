<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de formato para POST /api/cartas y PUT /api/cartas/{id}.
 * Si falla, el manejador global responde 400 con el detalle por campo.
 *
 * Los campos id, estado, comportamiento y fechaRecepcion no se aceptan del
 * cliente: como no están en estas reglas, validated() los descarta.
 */
class GuardarCartaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remitente' => ['required', 'string', 'min:2', 'max:60'],
            'edad' => ['required', $this->noBooleano(...), 'integer', 'between:1,17'],
            'ciudad' => ['required', 'string', 'max:100'],
            'regalos' => ['required', 'array', 'list', 'min:1', 'max:5'],
            'regalos.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
        ];
    }

    public function messages(): array
    {
        return [
            'remitente.required' => 'El remitente es obligatorio.',
            'remitente.string' => 'El remitente debe ser texto.',
            'remitente.min' => 'El remitente debe tener al menos 2 caracteres.',
            'remitente.max' => 'El remitente no puede tener más de 60 caracteres.',
            'edad.required' => 'La edad es obligatoria.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.between' => 'La edad debe estar entre 1 y 17 años.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'ciudad.string' => 'La ciudad debe ser texto.',
            'ciudad.max' => 'La ciudad no puede tener más de 100 caracteres.',
            'regalos.required' => 'Debes pedir al menos un regalo.',
            'regalos.array' => 'Los regalos deben enviarse como una lista de textos.',
            'regalos.list' => 'Los regalos deben enviarse como una lista (arreglo JSON), no como objeto.',
            'regalos.min' => 'Debes pedir al menos un regalo.',
            'regalos.max' => 'Se pueden pedir máximo 5 regalos.',
            'regalos.*.required' => 'Ningún regalo puede ir vacío.',
            'regalos.*.string' => 'Cada regalo debe ser texto.',
            'regalos.*.max' => 'Cada regalo puede tener máximo 100 caracteres.',
            'regalos.*.distinct' => 'El regalo ":input" está repetido; la lista no admite duplicados.',
        ];
    }

    /** true/false pasarían como 1/0 con la regla integer; aquí no se aceptan. */
    private function noBooleano(string $atributo, mixed $valor, Closure $fallar): void
    {
        if (is_bool($valor)) {
            $fallar('La edad debe ser un número entero.');
        }
    }
}
