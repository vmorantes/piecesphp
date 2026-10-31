<?php

//Una categoría de formularios o un tipo de documento borrados (inactivos) no impiden su nombre ni salen en las listas; y
//borrar una categoría de noticias borra su icono, si es suyo. En proceso; siembra zz-prueba- y lo retira.
//@areas src/app/classes/Forms/
//@areas src/app/classes/News/

use Forms\Categories\Controllers\CategoriesController;
use Forms\Categories\Mappers\CategoriesMapper;
use Forms\DocumentTypes\Controllers\DocumentTypesController;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use News\Controllers\NewsCategoryController;
use News\Mappers\NewsCategoryMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/forms-categories-status', function ($args) {

    echoTerminal("\e[33m[TEST:FormsCategoriesStatus] Lo borrado no estorba: nombres, listas e iconos\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };
    $lanza = function (callable $llamada): string {
        try {
            $llamada();
            return '';
        } catch (\Throwable $e) {
            return get_class($e);
        }
    };

    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        $check(false, 'p0 hay conexión a la base');
        return $balance();
    }
    $prefijo = 'zz-prueba-estado-' . bin2hex(random_bytes(3));
    $usuario = 0;
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $carpetaIconos = append_to_path_system((string) get_config('upload_dir'), 'news-categories');
    $archivosCreados = [];
    $peticion = fn (): RequestRoute => new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-borrar'), new Headers(), [], [], (new StreamFactory())->createStream(''));

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Estado';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuario = (int) $u->id;
        set_config('current_user', (object) ['id' => $usuario]);
        set_config('pcsphp_current_user_stored', null);

        //──── a/b. Categorías de formularios y tipos de documento ──────────────────────────────
        $casos = [
            'a' => ['categoría', CategoriesMapper::class, 'categoryName', CategoriesController::class],
            'b' => ['tipo de documento', DocumentTypesMapper::class, 'documentTypeName', DocumentTypesController::class],
        ];
        foreach ($casos as $letra => [$cosa, $mapper, $columna, $controlador]) {
            echoTerminal("[{$letra}] {$cosa}: crear, borrar y volver a crear con el mismo nombre");
            $nombre = "{$prefijo}-{$letra}";
            $crear = function () use ($mapper, $nombre): int {
                $m = new $mapper();
                if ($m instanceof CategoriesMapper) {
                    $m->categoryName = $nombre;
                } elseif ($m instanceof DocumentTypesMapper) {
                    $m->documentTypeName = $nombre;
                }
                $m->folder = $nombre;
                $m->save();
                return (int) $m->id;
            };
            $primera = $crear();
            $check($primera > 0, "{$letra}1 se crea", (string) $primera);
            $check($lanza($crear) !== '', "{$letra}2 CANARIO: con la primera activa, el mismo nombre se rechaza");
            $borrado = (new $controlador())->toDelete($peticion(), new ResponseRoute(), ['id' => $primera]);
            $estado = (int) $database->query('SELECT status FROM `' . $mapper::TABLE . "` WHERE id = {$primera}")->fetchColumn();
            $check($estado === $mapper::STATUS_INACTIVE, "{$letra}3 borrarla la deja inactiva (borrado lógico)", "estado {$estado}, HTTP " . $borrado->getStatusCode());
            $check($mapper::existsByName($nombre) === false, "{$letra}4 DISCRIMINANTE: existsByName() ya no la cuenta");
            $segunda = 0;
            $clase = $lanza(function () use ($crear, &$segunda) {
                $segunda = $crear();
            });
            $check($clase === '' && $segunda > 0 && $segunda !== $primera, "{$letra}5 y el mismo nombre se vuelve a crear", $clase);
            $opciones = $mapper::allForSelect();
            $check(array_key_exists((string) $segunda, $opciones) && !array_key_exists((string) $primera, $opciones), "{$letra}6 allForSelect(): la activa sí, la borrada no");
            $todas = (string) json_encode($controlador::_all(1, 5000));
            $check(str_contains($todas, '"id":' . $segunda) || str_contains($todas, '"id":"' . $segunda . '"'), "{$letra}7 CANARIO: la ruta -ajax-all trae la activa");
            $check(!str_contains($todas, '"id":' . $primera . ',') && !str_contains($todas, '"id":"' . $primera . '"'), "{$letra}8 y NO la borrada");
            echoTerminal(' ');
        }

        //──── c. El icono de una categoría de noticias ─────────────────────────────────────────
        echoTerminal('[c] Borrar una categoría de noticias borra su icono, si es suyo');
        $relativo = fn (string $ruta): string => trim(str_replace(basepath(), '', $ruta), \DIRECTORY_SEPARATOR);
        $icono = function (string $sufijo) use ($carpetaIconos, &$archivosCreados): string {
            $carpeta = append_to_path_system($carpetaIconos, 'zz-prueba-' . bin2hex(random_bytes(4)));
            $archivo = "{$carpeta}/{$sufijo}.png";
            $creado = (is_dir($carpeta) || mkdir($carpeta, 0755, true)) && file_put_contents($archivo, 'zz') !== false;
            $archivosCreados[] = $carpeta;
            return $creado ? $archivo : '';
        };
        $categoria = function (string $sufijo, string $iconImage) use ($database, $prefijo): int {
            $database->prepare('INSERT INTO `' . NewsCategoryMapper::TABLE . '` (name, iconImage, color, meta) VALUES (?, ?, ?, ?)')
                ->execute(["{$prefijo}-{$sufijo}", $iconImage, '#000000', '{}']);
            return (int) $database->lastInsertId();
        };
        $borrar = fn (int $id): array => (array) json_decode((string) (new NewsCategoryController())->toDelete($peticion(), new ResponseRoute(), ['id' => $id])->getBody(), true);

        $propio = $icono('propio');
        $ajeno = $icono('ajeno');
        $compartido = $icono('compartido');
        $check($propio !== '' && $ajeno !== '' && $compartido !== '', 'c0 los iconos de la prueba se crean en la carpeta del módulo', $carpetaIconos);
        $conPropio = $categoria('c-propio', $relativo($propio));
        $conAjeno = $categoria('c-ajeno', $relativo($ajeno));
        $conCompartido1 = $categoria('c-compartido-1', $relativo($compartido));
        $conCompartido2 = $categoria('c-compartido-2', $relativo($compartido));
        $conOmision = $categoria('c-omision', NewsCategoryMapper::DEFAULT_ICON);

        $r = $borrar($conPropio);
        $check(($r['success'] ?? null) === true, 'c1 CANARIO: la categoría se borra', (string) json_encode($r));
        $check(!file_exists($propio) && !is_dir(dirname($propio)), 'c2 DISCRIMINANTE: su icono ya no está, ni su carpeta');
        $check(file_exists($ajeno), 'c3 el icono de otra categoría sigue');
        $borrar($conCompartido1);
        $check(file_exists($compartido), 'c4 un icono que otra categoría usa no se borra');
        $omision = basepath(NewsCategoryMapper::DEFAULT_ICON);
        $existiaOmision = file_exists($omision);
        $borrar($conOmision);
        $check(file_exists($omision) === $existiaOmision, 'c5 el icono por omisión, compartido, NUNCA se borra: ' . NewsCategoryMapper::DEFAULT_ICON);
        $fuera = $categoria('c-fuera', 'statics/images/news/zz-no-es-de-la-carpeta.png');
        $r = $borrar($fuera);
        $check(($r['success'] ?? null) === true, 'c6 un icono fuera de la carpeta del módulo no se toca y el borrado sigue siendo un éxito');
        unset($conAjeno, $conCompartido2);
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ([CategoriesMapper::TABLE => 'categoryName', DocumentTypesMapper::TABLE => 'documentTypeName', NewsCategoryMapper::TABLE => 'name'] as $tabla => $columna) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE {$columna} LIKE ?")->execute(["{$prefijo}%"]);
            }
            foreach ($archivosCreados as $carpeta) {
                remove_directory($carpeta);
            }
            if ($usuario > 0) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $usuario, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $usuario])->execute();
                $database->exec('DELETE FROM `' . UsersModel::TABLE . "` WHERE id = {$usuario}");
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        foreach ([CategoriesMapper::TABLE => 'categoryName', DocumentTypesMapper::TABLE => 'documentTypeName', NewsCategoryMapper::TABLE => 'name'] as $tabla => $columna) {
            $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE {$columna} LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        }
        foreach ($archivosCreados as $carpeta) {
            $quedan += is_dir($carpeta) ? 1 : 0;
        }
        $check($quedan === 0, 'z1 no queda ningún usuario, categoría, tipo ni icono de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Una categoría de formularios o un tipo de documento borrados no impiden su nombre ni salen en allForSelect() ni en -ajax-all; borrar una categoría de noticias borra su icono si es suyo y nadie más lo usa, y nunca el de por omisión.')->setEffects([CliActions::EFFECT_DATABASE])->register();
