<?php
/**
 * Clase base opcional para eventos internos de la plataforma.
 *
 * Un evento interno NO es un hook de WordPress: es comunicación entre módulos propios
 * de dnorte-core que no necesita (ni debe) pasar por add_action/do_action. Ver
 * EventDispatcher y docs/Architecture.md → "Hooks vs. eventos".
 *
 * @package DNorteCore\Events
 */

declare(strict_types=1);

namespace DNorteCore\Events;

abstract class Event
{
    public function name(): string
    {
        return static::class;
    }
}
