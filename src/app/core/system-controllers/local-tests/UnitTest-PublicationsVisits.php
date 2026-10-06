<?php

//Sin base: los mappers son anónimos y la aprobación la fija la prueba. Solo cuenta visita lo que ve el público.

use PiecesPHP\Terminal\CliActions;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

CliActions::make('unit-tests:core/publications-visits', function ($args) {

    echoTerminal("\e[33m[TEST:PublicationsVisits] La vista previa de una publicación no suma visitas\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $publicacion = function (int $status, ?\DateTime $inicio = null, ?\DateTime $fin = null, bool $aprobada = true): PublicationMapper {
        //SIN BASE: sin valor de comparación, el constructor no consulta; la aprobación la pone la prueba, no la tabla.
        $mapper = new class extends PublicationMapper {
            public bool $aprobadaEnPrueba = true;

            public function isApprovedForPublic(): bool
            {
                return $this->aprobadaEnPrueba;
            }
        };
        $mapper->aprobadaEnPrueba = $aprobada;
        $mapper->id = 1;
        $mapper->status = $status;
        $mapper->startDate = $inicio;
        $mapper->endDate = $fin;
        return $mapper;
    };

    //─── 1/2 · countsVisits() ─────────────────────────────────────────────────────────────
    echoTerminal('[1/2] countsVisits() solo es true para lo que ve el público');
    $casos = [
        'borrador' => [$publicacion(PublicationMapper::DRAFT), false],
        'activa, en fechas y aprobada' => [$publicacion(PublicationMapper::ACTIVE), true],
        'activa, en fechas y pendiente de aprobación' => [$publicacion(PublicationMapper::ACTIVE, null, null, false), false],
        'programada (fechas futuras)' => [$publicacion(PublicationMapper::ACTIVE, new \DateTime('+1 day'), new \DateTime('+2 day')), false],
    ];
    foreach ($casos as $nombre => [$mapper, $esperado]) {
        $obtenido = $mapper->countsVisits();
        $check($obtenido === $esperado, "{$nombre}: countsVisits() → " . var_export($esperado, true), 'obtenido ' . var_export($obtenido, true));
    }
    echoTerminal(' ');

    //─── 2/2 · singleView() ───────────────────────────────────────────────────────────────
    echoTerminal('[2/2] singleView() decide la visita con countsVisits(), no con isDraft()');
    $metodo = new \ReflectionMethod(PublicationsPublicController::class, 'singleView');
    $lineas = file((string) $metodo->getFileName());
    $cuerpo = implode('', array_slice(is_array($lineas) ? $lineas : [], $metodo->getStartLine() - 1, $metodo->getEndLine() - $metodo->getStartLine() + 1));
    //POR TOKENS, sin comentarios ni espacios: un comentario que nombre countsVisits() no cuenta.
    $tokens = array_values(array_filter(token_get_all('<?php ' . $cuerpo), fn($t) => !is_array($t) || !in_array($t[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT, \T_OPEN_TAG], true)));
    $texto = fn($t) => is_array($t) ? $t[1] : $t;
    $visita = null;
    foreach ($tokens as $i => $t) {
        if ($texto($t) === 'addVisit' && $i > 0 && $texto($tokens[$i - 1]) === '->') {
            $visita = $i;
            break;
        }
    }
    //La condición del if que envuelve addVisit(): desde el último `if (` anterior hasta su `)` de cierre.
    $condicion = [];
    if ($visita !== null) {
        for ($j = $visita; $j >= 0; $j--) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === \T_IF) {
                $nivel = 0;
                for ($k = $j + 1; $k < $visita; $k++) {
                    $s = $texto($tokens[$k]);
                    $nivel += ($s === '(') - ($s === ')');
                    $condicion[] = $s;
                    if ($nivel === 0) {
                        break;
                    }
                }
                break;
            }
        }
    }
    $condicionTexto = implode('', $condicion);
    $check($visita !== null, 'singleView() llama a addVisit()');
    $check(str_contains($condicionTexto, '->countsVisits()'), 'la condición de addVisit() usa countsVisits()', $condicionTexto);
    $check(!str_contains($condicionTexto, 'isDraft'), 'la condición de addVisit() no usa isDraft()', $condicionTexto);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La vista previa de una publicación no suma visitas: solo cuenta lo que ve el público.')->setEffects([CliActions::EFFECT_NONE])->register();
