<?php

namespace Tests\Unit;

use App\Support\Privacidad;
use PHPUnit\Framework\TestCase;

class PrivacidadTest extends TestCase
{
    public function test_deja_el_primer_nombre_y_la_inicial_del_apellido(): void
    {
        $this->assertSame('Valentina G.', Privacidad::nombreConInicial('Valentina Gómez'));
        $this->assertSame('Ñusta Ñ.', Privacidad::nombreConInicial('  Ñusta   ñaupari  '));
        $this->assertSame('Grinch', Privacidad::nombreConInicial('Grinch'));
    }
}
