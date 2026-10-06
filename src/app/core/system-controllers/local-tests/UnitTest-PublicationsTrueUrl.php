<?php

//La URL vieja de una publicación redirige con un 301 a la verdadera, y nunca la de un borrador ni la de una
//programada: su cabecera Location llevaría el título. Por HTTP. Crea un principal y tres publicaciones zz-.

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/publications-true-url', function ($args) {

    echoTerminal("\e[33m[TEST:PublicationsTrueUrl] La URL vieja redirige a la verdadera, y nunca la de un borrador\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $check($base !== '', 'c1 hay una base HTTP para pedir', $base);
    $check(get_config('lang_by_cookie') === true, 'c2 la instalación lleva el idioma en ?i18n=: es lo que miden los casos');
    if ($failed > 0) {
        return $balance();
    }

    //El camino de una ruta, sin el `http://localhost` del terminal.
    $camino = function (string $nombre, array $parametros): string {
        $url = get_route($nombre, $parametros, true);
        $url = is_string($url) ? $url : '';
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, ?string $jwt = null) use ($base, $cabeceraToken): array {
        $location = null;
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => str_contains($path, '://') ? $path : $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
            CURLOPT_HEADERFUNCTION => function ($h, string $linea) use (&$location): int {
                if (stripos($linea, 'location:') === 0) {
                    $location = trim(substr($linea, 9));
                }
                return strlen($linea);
            },
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'location' => $location];
    };
    //La forma VIEJA de un slug: la función anterior quitaba los dígitos, y los guiones que quedaban se fundían.
    $slugViejo = function (string $slug): string {
        $partes = explode('-', $slug);
        $token = (string) array_pop($partes);
        $legible = trim((string) preg_replace('/-{2,}/', '-', (string) preg_replace('/[0-9]/', '', implode('-', $partes))), '-');
        return "{$legible}-{$token}";
    };
    $tokenDe = fn (string $slug): string => (string) substr($slug, (int) strrpos($slug, '-') + 1);
    //El destino sin la base del servidor (`/vicsen/…/src`), que el terminal no conoce: se compara el resto.
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $destino = function (?string $location) use ($prefijoBase): string {
        if ($location === null) {
            return '';
        }
        $query = (string) parse_url($location, \PHP_URL_QUERY);
        $path = (string) parse_url($location, \PHP_URL_PATH);
        $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        return $path . ($query !== '' ? "?{$query}" : '');
    };

    $prefijo = 'zz-prueba-url-' . bin2hex(random_bytes(3));
    $ids = [];
    $publicaciones = [];

    try {
        //─── Banco ───────────────────────────────────────────────────────────────────────────────
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Autor Url';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids['root'] = (int) $u->id;
        $root = SessionToken::generateToken(['id' => $ids['root']], null, null, false);

        $molde = PublicationCategoryMapper::model();
        $molde->resetAll();
        $molde->select('id')->execute(false, 1);
        $categoria = (int) (((array) $molde->result())[0]->id ?? PublicationCategoryMapper::uncategorizedCategory()->id);
        $crear = function (string $tituloES, ?string $tituloEN, int $estado, ?\DateTime $inicio) use ($prefijo, $categoria, $ids): int {
            $p = new PublicationMapper();
            $p->baseLang = 'es';
            foreach (array_filter(['es' => $tituloES, 'en' => $tituloEN]) as $lang => $titulo) {
                $p->setLangData($lang, 'title', $titulo);
                $p->setLangData($lang, 'content', "Contenido {$lang} de {$prefijo}.");
                $p->setLangData($lang, 'seoDescription', "Descripción {$lang} de {$prefijo}.");
                $p->setLangData($lang, 'mainImage', 'statics/images/open_graph.jpg');
                $p->setLangData($lang, 'thumbImage', 'statics/images/open_graph.jpg');
                $p->setLangData($lang, 'ogImage', '');
            }
            $p->setLangData('es', 'publicDate', new \DateTime('-1 day'));
            $p->setLangData('es', 'startDate', $inicio);
            $p->setLangData('es', 'endDate', null);
            $p->setLangData('es', 'category', $categoria);
            $p->setLangData('es', 'visits', 0);
            $p->setLangData('es', 'author', $ids['root']);
            $p->setLangData('es', 'folder', str_replace('.', '', uniqid()));
            $p->setLangData('es', 'featured', PublicationMapper::UNFEATURED);
            $p->status = $estado;
            //`save()` exige un usuario en sesión; el que firma es el principal de la prueba, y lo firmado por él queda aprobado.
            $usuarioPrevio = get_config('current_user');
            $guardadoPrevio = get_config('pcsphp_current_user_stored');
            try {
                set_config('current_user', (object) ['id' => $ids['root']]);
                set_config('pcsphp_current_user_stored', null);
                $p->save();
            } finally {
                set_config('current_user', $usuarioPrevio);
                set_config('pcsphp_current_user_stored', $guardadoPrevio);
            }
            return (int) $p->id;
        };
        $publicaciones['visible'] = $crear("{$prefijo} Informe anual 2026", "{$prefijo} Annual report 2026", PublicationMapper::ACTIVE, null);
        $publicaciones['borrador'] = $crear("{$prefijo} Borrador secreto 2027", null, PublicationMapper::DRAFT, null);
        $publicaciones['programada'] = $crear("{$prefijo} Programada secreta 2028", null, PublicationMapper::ACTIVE, new \DateTime('+30 days'));
        //La primera petición registra los elementos nuevos en las aprobaciones.
        $pedir('/');
        $slugDe = fn (string $clave, string $lang = 'es'): string => (string) (new PublicationMapper($publicaciones[$clave]))->getSlug($lang);
        $single = fn (string $slug, string $lang = 'es'): string => $camino('publications-single', ['slug' => $slug]) . "?i18n={$lang}";

        //─── a · La publicada ────────────────────────────────────────────────────────────────────
        echoTerminal('[a] La publicada: la canónica responde, y cualquier otra forma va a ella de un salto');
        $verdadero = $slugDe('visible');
        $canonica = $pedir($single($verdadero));
        $check($canonica['status'] === 200, 'a1 CANARIO: la URL verdadera responde 200', "HTTP {$canonica['status']} · {$verdadero}");
        $check(preg_match('/2026-[^-]+$/', $verdadero) === 1, 'a2 y conserva el año del título', $verdadero);
        $vieja = $pedir($single($slugViejo($verdadero)));
        $check($vieja['status'] === 301 && $destino($vieja['location']) === $single($verdadero), 'a3 la forma VIEJA, sin los dígitos, responde 301 a la verdadera', "HTTP {$vieja['status']} → " . (string) $vieja['location']);
        $siguiente = $vieja['location'] !== null ? $pedir((string) $vieja['location']) : ['status' => 0];
        $check($siguiente['status'] === 200, 'a4 y a donde manda responde 200: un solo salto, sin cadenas', "HTTP {$siguiente['status']}");
        $cualquiera = $pedir($single('cualquier-cosa-' . $tokenDe($verdadero)));
        $check($cualquiera['status'] === 301 && $destino($cualquiera['location']) === $single($verdadero), 'a5 un prefijo cualquiera con el token bueno, también 301 a la verdadera', "HTTP {$cualquiera['status']}");
        $verdaderoEN = $slugDe('visible', 'en');
        $viejaEN = $pedir($single($slugViejo($verdaderoEN), 'en'));
        $check($viejaEN['status'] === 301 && $destino($viejaEN['location']) === $single($verdaderoEN, 'en'), 'a6 en inglés, a la verdadera en inglés, y el ?i18n=en viaja', (string) $viejaEN['location']);

        //─── b · Lo que no se enseña no redirige ─────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Un borrador o una programada con el slug equivocado responde como siempre: sin 301 y sin su título');
        foreach (['borrador' => 'Borrador secreto', 'programada' => 'Programada secreta'] as $clave => $titulo) {
            $falso = 'adivinado-' . $tokenDe($slugDe($clave));
            $r = $pedir($single($falso));
            $check($r['status'] !== 301 && $r['status'] !== 200 && $r['location'] === null, "b1 {$clave}: el slug equivocado NO da 301 ni Location", "HTTP {$r['status']}");
            $enSlug = strtolower(str_replace(' ', '-', $titulo));
            $check(!str_contains($r['body'], $titulo) && !str_contains(strtolower($r['body']), $enSlug) && !str_contains(strtolower((string) $r['location']), $enSlug), "b2 {$clave}: y ni el cuerpo ni la cabecera Location traen su título");
        }
        //CANARIO de b: quien SÍ puede ver un borrador recibe el 301. Sin esto, b1 pasaría con la redirección rota.
        $borradorRoot = $pedir($single('adivinado-' . $tokenDe($slugDe('borrador'))), $root);
        $check($borradorRoot['status'] === 301 && $destino($borradorRoot['location']) === $single($slugDe('borrador')), 'b3 CANARIO: el principal, que ve borradores, sí recibe el 301', "HTTP {$borradorRoot['status']}");

        //─── c · La categoría ────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] El listado por categoría, igual');
        $categoriaSlug = (string) (new PublicationCategoryMapper($categoria))->getSlug();
        $porCategoria = fn (string $slug): string => $camino('publications-list-by-category', ['categorySlug' => $slug]) . '?i18n=es';
        $cat = $pedir($porCategoria($categoriaSlug));
        $check($cat['status'] === 200, 'c3 CANARIO: la URL verdadera de la categoría responde 200', "HTTP {$cat['status']}");
        $catOtra = $pedir($porCategoria('otra-cosa-' . $tokenDe($categoriaSlug)));
        $check($catOtra['status'] === 301 && $destino($catOtra['location']) === $porCategoria($categoriaSlug), 'c4 otra forma de la categoría, 301 a la verdadera', "HTTP {$catOtra['status']} → " . (string) $catOtra['location']);

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([PublicationMapper::TABLE => $publicaciones, UsersModel::TABLE => $ids] as $tabla => $idsDeTabla) {
                foreach ($idsDeTabla as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($publicaciones as $id) {
                $pub = PublicationMapper::model();
                $pub->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $pub->delete(['id' => $id])->execute();
            }
            foreach ($ids as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = array_filter($publicaciones, fn ($id) => PublicationMapper::existsByID($id));
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check($quedan === [] && count((array) $usuarios->result()) === 0, 'z1 no queda ninguna publicación ni ningún usuario de la prueba');
    }

    return $balance();

})->setDescription('La URL vieja de una publicación —sin los dígitos, o con otro prefijo— responde 301 a la verdadera de un solo salto, y el idioma viaja; la de una categoría, igual. Un borrador o una programada con el slug equivocado responde como siempre, sin 301 y sin su título. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
