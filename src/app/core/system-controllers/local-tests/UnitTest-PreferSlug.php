<?php

//El acuñado del slug: atómico, y la tarea que lo hace de golpe. Ver T64.

use News\Mappers\NewsCategoryMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Jobs\PreferSlugsFiller;

/** Subclase de SOLO PRUEBAS: mueve la guarda a una columna que admite nulos. */
class ZzSlugGuardMapper extends NewsCategoryMapper
{
    const SLUG_NAME_FIELD = 'meta';
}

CliActions::make('unit-tests:core/prefer-slug', function ($args) {

    echoTerminal("\e[33m[TEST:preferSlug] El acuñado del slug\e[39m");
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

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    $tabla = NewsCategoryMapper::TABLE;
    $marca = 'zz-slug-' . bin2hex(random_bytes(4));
    $creados = [];

    /** Una fila propia, con el slug a NULL. No se toca ninguna real. */
    $crearFila = function (string $nombre) use ($db, $tabla, &$creados): int {
        $meta = json_encode(['langData' => ['es' => ['name' => $nombre]], 'baseLang' => 'es']);
        $st = $db->prepare("INSERT INTO `{$tabla}` (`preferSlug`, `name`, `iconImage`, `color`, `meta`) VALUES (NULL, ?, '', '#000000', ?)");
        $st->execute([$nombre, $meta]);
        $id = (int) $db->lastInsertId();
        $creados[] = $id;
        return $id;
    };
    $slugEnBase = function (int $id) use ($db, $tabla) {
        $st = $db->prepare("SELECT `preferSlug` FROM `{$tabla}` WHERE id = ?");
        $st->execute([$id]);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    };

    try {

        //──── LA CARRERA ────────────────────────────────────────────────────────────────
        $id = $crearFila($marca . '-carrera');
        $check($slugEnBase($id) === null, 'la fila de prueba nace SIN slug');

        //Dos objetos cargados mientras el slug seguía nulo: es exactamente el estado de dos
        //peticiones simultáneas, sin necesidad de hilos.
        $a = new NewsCategoryMapper($id);
        $b = new NewsCategoryMapper($id);
        $check($a->preferSlug === null && $b->preferSlug === null, 'los dos lo leen nulo, como dos peticiones a la vez');

        $ganoA = NewsCategoryMapper::mintPreferSlugIfMissing($a);
        $ganoB = NewsCategoryMapper::mintPreferSlugIfMissing($b);
        $enBase = $slugEnBase($id);

        $check($ganoA === true && $ganoB === false, 'solo UNO acuña: el segundo no pisa al primero',
            'A=' . var_export($ganoA, true) . ' B=' . var_export($ganoB, true));
        $check($a->preferSlug === $enBase, 'el que ganó coincide con la base');
        $check($b->preferSlug === $enBase, 'el que PERDIÓ relee el del ganador, no se queda con uno inexistente',
            'perdedor=' . (string) json_encode($b->preferSlug) . ' base=' . (string) json_encode($enBase));

        //Y repetir es un no-op.
        $c = new NewsCategoryMapper($id);
        $antes = $slugEnBase($id);
        NewsCategoryMapper::mintPreferSlugIfMissing($c);
        $check($slugEnBase($id) === $antes, 'llamarlo otra vez NO cambia el slug ya acuñado');

        //──── LA GUARDA DEL NOMBRE ──────────────────────────────────────────────────────
        //`name` es NOT NULL: la guarda se prueba con una subclase que apunta a una nulable.
        $idSinNombre = $crearFila($marca . '-sin-nombre');
        $db->prepare("UPDATE `{$tabla}` SET `meta` = NULL WHERE id = ?")->execute([$idSinNombre]);

        $sinNombre = new ZzSlugGuardMapper($idSinNombre);
        $acuno = ZzSlugGuardMapper::mintPreferSlugIfMissing($sinNombre);
        $check($acuno === false && $slugEnBase($idSinNombre) === null,
            'una fila con el campo de nombre a NULL no recibe slug',
            'acuñó=' . var_export($acuno, true) . ' base=' . (string) json_encode($slugEnBase($idSinNombre)));

        //Y con el campo puesto, la misma fila sí lo recibe.
        $db->prepare("UPDATE `{$tabla}` SET `meta` = ? WHERE id = ?")->execute(['{}', $idSinNombre]);
        $conNombre = new ZzSlugGuardMapper($idSinNombre);
        $check(ZzSlugGuardMapper::mintPreferSlugIfMissing($conNombre) === true && $slugEnBase($idSinNombre) !== null,
            'y con el campo puesto, la misma fila SÍ lo recibe');

        //──── LA TAREA DE RELLENO MASIVO ────────────────────────────────────────────────
        $idNulo = $crearFila($marca . '-masivo');
        $idConValor = $crearFila($marca . '-intacto');
        $db->prepare("UPDATE `{$tabla}` SET `preferSlug` = ? WHERE id = ?")->execute(['zz-valor-puesto-a-mano', $idConValor]);

        $antesIntacto = $slugEnBase($idConValor);
        $resumen = PreferSlugsFiller::run($tabla);

        $check($slugEnBase($idNulo) !== null, 'la tarea RELLENA lo que estaba nulo');
        $check($slugEnBase($idConValor) === $antesIntacto, 'y NO TOCA lo que ya tenía valor',
            'antes=' . (string) json_encode($antesIntacto) . ' después=' . (string) json_encode($slugEnBase($idConValor)));
        $check(($resumen['detail'][$tabla] ?? -1) >= 1, 'el resumen cuenta lo que rellenó', (string) json_encode($resumen['detail']));

        //Y los mappers con slug se DESCUBREN, no se enumeran. La cota SALE DEL ARBOL, no de un
        //numero escrito: E3 borra mappers y un `>= 14` cableado falla en cada lote. Ver T139.
        $conSlug = PreferSlugsFiller::mappersWithSlug();
        //Metodo INDEPENDIENTE del que se prueba: se cuentan por TEXTO los mappers que declaran
        //el campo, y el descubrimiento -que va por reflexion- tiene que encontrarlos todos.
        $declarantes = 0;
        $raiz = basepath('app/classes');
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            if (!$archivo->isFile() || $archivo->getExtension() !== 'php') {
                continue;
            }
            $texto = (string) file_get_contents($archivo->getPathname());
            if (str_contains($texto, "'preferSlug' =>") || str_contains($texto, '"preferSlug" =>')) {
                $declarantes++;
            }
        }
        $check($declarantes > 0, 'el censo por texto ve mappers que declaran preferSlug', "{$declarantes} declarantes");
        $check(count($conSlug) >= $declarantes, 'el descubrimiento por reflexión los encuentra todos',
            count($conSlug) . " descubiertos contra {$declarantes} declarados por texto");

    } catch (\Throwable $exception) {
        $check(false, 'la suite corre sin excepciones', $exception->getMessage());
    } finally {
        foreach ($creados as $idCreado) {
            try { $db->prepare("DELETE FROM `{$tabla}` WHERE id = ?")->execute([$idCreado]); } catch (\Throwable $e) { }
        }
    }

    $total = $passed + $failed;
    echoTerminal('');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El acuñado del preferSlug: atómico, y la tarea de relleno masivo.')->setEffects([CliActions::EFFECT_DATABASE])->register();
