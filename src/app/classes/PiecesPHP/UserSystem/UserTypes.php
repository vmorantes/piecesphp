<?php

/**
 * UserTypes.php
 */

namespace PiecesPHP\UserSystem;

use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * UserTypes.
 *
 * El registro de los tipos de usuario: cuáles hay, cómo se llaman, su prioridad y lo que cada uno
 * necesita. Los de fábrica son las constantes CORE_*; el proyecto añade, ajusta o retira en
 * `src/app/config/user-types.php` (ADR 0078), que se lee al primer uso. Con un error en el archivo
 * no se aplica nada de él: rigen los de fábrica y el error queda en errors().
 *
 * Las listas `TYPES_*` y `ARE_AUTO_APPROVAL` de `UsersModel` son alias en desuso de estas constantes:
 * una constante no puede leer el archivo del proyecto, y estos métodos sí podrán.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class UserTypes
{

    /**
     * Las etiquetas de los tipos de fábrica, que son también los nombres de sus roles.
     *
     * Google Play (50) queda fuera a propósito: no tiene rol ni se ofrece en ningún formulario.
     *
     * @var array<int,string>
     */
    const CORE_LABELS = [
        UsersModel::TYPE_USER_ROOT => 'Principal',
        UsersModel::TYPE_USER_ADMIN_GRAL => 'Administrador general',
        UsersModel::TYPE_USER_ADMIN_ORG => 'Administrador de organización',
        UsersModel::TYPE_USER_GENERAL => 'Usuario general',
        UsersModel::TYPE_USER_INSTITUCIONAL => 'Institucional',
        UsersModel::TYPE_USER_COMUNICACIONES => 'Comunicaciones',
    ];

    /**
     * La prioridad de cada tipo de fábrica: un tipo tiene autoridad sobre los de prioridad igual o menor.
     *
     * @var array<int,int>
     */
    const CORE_PRIORITY = [
        UsersModel::TYPE_USER_ROOT => 500,
        UsersModel::TYPE_USER_ADMIN_GRAL => 400,
        UsersModel::TYPE_USER_ADMIN_ORG => 1,
        UsersModel::TYPE_USER_GENERAL => 1,
        UsersModel::TYPE_USER_INSTITUCIONAL => 100,
        UsersModel::TYPE_USER_COMUNICACIONES => 2,
        UsersModel::TYPE_USER_GOOGLE_PLAY => -50,
    ];

    /**
     * Los tipos que no tienen autoridad sobre otros usuarios de su mismo tipo.
     *
     * @var int[]
     */
    const CORE_NO_AUTHORITY_OVER_SAME_TYPE = [
        UsersModel::TYPE_USER_ADMIN_ORG,
    ];

    /**
     * Los tipos que no pertenecen a ninguna organización.
     *
     * @var int[]
     */
    const CORE_NO_ORGANIZATION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
        UsersModel::TYPE_USER_GOOGLE_PLAY,
    ];

    /**
     * Los tipos que deberían tener perfil.
     *
     * @var int[]
     */
    const CORE_PROFILE = [
        UsersModel::TYPE_USER_ADMIN_ORG,
        UsersModel::TYPE_USER_GENERAL,
    ];

    /**
     * Los tipos que pueden entrar con un proveedor externo.
     *
     * @var int[]
     */
    const CORE_EXTERNAL_LOGIN = [
        UsersModel::TYPE_USER_GENERAL,
        UsersModel::TYPE_USER_GOOGLE_PLAY,
    ];

    /**
     * Los tipos que no pasan por aprobación.
     *
     * @var int[]
     */
    const CORE_AUTO_APPROVAL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
        UsersModel::TYPE_USER_INSTITUCIONAL,
        UsersModel::TYPE_USER_COMUNICACIONES,
    ];

    /**
     * Los tipos que un proyecto no puede retirar.
     *
     * @var int[]
     */
    const PROTECTED = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];

    /**
     * Los códigos que un proyecto no puede usar para un tipo propio: el de sistema y el de Google Play.
     *
     * @var int[]
     */
    const RESERVED = [
        UsersModel::TYPE_USER_SYSTEM,
        UsersModel::TYPE_USER_GOOGLE_PLAY,
    ];

    /**
     * Las claves que admite un tipo propio en `user-types.php`.
     */
    const TYPE_KEYS = ['id', 'label', 'translations', 'description', 'descriptionTranslations', 'priority', 'organization', 'profile', 'externalLogin', 'authorityOverSameType', 'autoApproval', 'creatable', 'permissions', 'routes'];

    /**
     * Las únicas que admite un tipo de fábrica: lo demás lo usa el código del framework.
     */
    const CORE_TYPE_KEYS = ['id', 'label', 'translations', 'description', 'descriptionTranslations', 'creatable'];

    /**
     * Las tres listas de rutas de `roles.php` con las que puede empezar un tipo propio.
     */
    const SEGMENTS = ['generals', 'administratives', 'superiors'];

    /**
     * La prioridad de root: ningún tipo propio llega a ella.
     */
    const PRIORITY_LIMIT = 500;

    /**
     * Desde aquí, un tipo propio queda al nivel de la administración general: se avisa.
     */
    const PRIORITY_WARNING = 400;

    /**
     * El idioma en que se escriben las etiquetas y las descripciones (la guía «Los tipos de usuario»).
     */
    const LABEL_LANG = 'es';

    /**
     * Qué módulo asigna cada tipo retirable, para avisar si se retira con el módulo encendido (ADR 0078 §3.2).
     *
     * @var array<int,array<string,string>> tipo => constante del módulo => qué hace con él
     */
    const MODULE_ASSIGNMENTS = [
        UsersModel::TYPE_USER_GENERAL => [
            'API_MODULE' => 'el alta por API lo da por omisión',
            'DATA_IMPORT_EXPORT_MODULE' => 'la importación de usuarios solo crea este tipo',
        ],
        UsersModel::TYPE_USER_ADMIN_ORG => [
            'API_MODULE' => 'el alta por API lo da a quien crea su organización',
            'ORGANIZATIONS_MODULE' => 'las organizaciones lo usan como administrador',
        ],
        UsersModel::TYPE_USER_INSTITUCIONAL => [
            'PUBLICATIONS_MODULE' => 'es autor elegible en Publicaciones',
        ],
        UsersModel::TYPE_USER_COMUNICACIONES => [
            'PUBLICATIONS_MODULE' => 'es autor elegible en Publicaciones',
        ],
    ];

    /**
     * Los tipos efectivos, en su orden, con sus errores y avisos; null hasta el primer uso.
     *
     * @var array{types:array<int,array{label:?string,priority:int,organization:bool,profile:bool,externalLogin:bool,authorityOverSameType:bool,autoApproval:bool,creatable:bool,permissions:string,routes:string[],translations:array<string,string>,description:?string,descriptionTranslations:array<string,string>}>,retired:int[],errors:string[],warnings:string[]}|null
     */
    private static ?array $state = null;

    /**
     * El archivo del proyecto.
     *
     * @return string
     */
    public static function file(): string
    {
        return dirname(__DIR__, 3) . '/config/user-types.php';
    }

    /**
     * Aplica un contenido como si fuera el del archivo, con las mismas reglas. Solo para las suites.
     *
     * @param array<mixed> $data
     * @return void
     */
    public static function fromArray(array $data): void
    {
        self::$state = self::build($data);
    }

    /**
     * Olvida lo aplicado: el próximo uso vuelve a leer el archivo. Solo para las suites.
     *
     * @return void
     */
    public static function reload(): void
    {
        self::$state = null;
    }

    /**
     * Los errores del archivo. Con alguno, no se aplica nada de él: rigen los de fábrica.
     *
     * @return string[]
     */
    public static function errors(): array
    {
        return self::state()['errors'];
    }

    /**
     * Lo que se aplica pero conviene revisar (una prioridad desde 400).
     *
     * @return string[]
     */
    public static function warnings(): array
    {
        return self::state()['warnings'];
    }

    /**
     * Los códigos retirados por el archivo.
     *
     * @return int[]
     */
    public static function retired(): array
    {
        return self::state()['retired'];
    }

    /**
     * Los códigos de los tipos con etiqueta, en su orden.
     *
     * @return int[]
     */
    public static function codes(): array
    {
        return array_keys(self::labels());
    }

    /**
     * Código => etiqueta, sin traducir: la etiqueta es también el nombre del rol.
     *
     * @return array<int,string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::state()['types'] as $code => $type) {
            if ($type['label'] !== null) {
                $labels[$code] = $type['label'];
            }
        }
        return $labels;
    }

    /**
     * Código => prioridad, también de los tipos sin etiqueta.
     *
     * @return array<int,int>
     */
    public static function priorities(): array
    {
        return array_map(fn(array $type): int => $type['priority'], self::state()['types']);
    }

    /**
     * La prioridad de un tipo, o null si no tiene.
     *
     * @param int $type
     * @return int|null
     */
    public static function priority(int $type): ?int
    {
        return self::priorities()[$type] ?? null;
    }

    /**
     * Si el tipo pertenece a una organización. Un tipo desconocido, sí: es lo seguro.
     *
     * @param int $type
     * @return bool
     */
    public static function requiresOrganization(int $type): bool
    {
        return self::state()['types'][$type]['organization'] ?? true;
    }

    /**
     * Si el tipo debería tener perfil.
     *
     * @param int $type
     * @return bool
     */
    public static function shouldHaveProfile(int $type): bool
    {
        return in_array($type, self::profileTypes(), true);
    }

    /**
     * Los tipos que deberían tener perfil.
     *
     * @return int[]
     */
    public static function profileTypes(): array
    {
        return array_keys(array_filter(self::state()['types'], fn(array $type): bool => $type['profile']));
    }

    /**
     * Si el tipo puede entrar con un proveedor externo.
     *
     * @param int $type
     * @return bool
     */
    public static function hasExternalLogin(int $type): bool
    {
        return self::state()['types'][$type]['externalLogin'] ?? false;
    }

    /**
     * Si el tipo tiene autoridad sobre otros usuarios de su mismo tipo.
     *
     * @param int $type
     * @return bool
     */
    public static function hasAuthorityOverSameType(int $type): bool
    {
        return self::state()['types'][$type]['authorityOverSameType'] ?? true;
    }

    /**
     * Si el tipo no pasa por aprobación.
     *
     * @param int $type
     * @return bool
     */
    public static function isAutoApproval(int $type): bool
    {
        return self::state()['types'][$type]['autoApproval'] ?? false;
    }

    /**
     * Si el tipo se ofrece al crear un usuario. Uno sin etiqueta (sin rol), nunca.
     *
     * @param int $type
     * @return bool
     */
    public static function isCreatable(int $type): bool
    {
        $entry = self::state()['types'][$type] ?? null;
        return $entry !== null && $entry['label'] !== null && $entry['creatable'];
    }

    /**
     * La descripción declarada en el archivo, sin traducir, o null: entonces la pantalla usa la suya.
     *
     * @param int $type
     * @return string|null
     */
    public static function description(int $type): ?string
    {
        return self::state()['types'][$type]['description'] ?? null;
    }

    /**
     * Si el tipo no se puede retirar.
     *
     * @param int $type
     * @return bool
     */
    public static function isProtected(int $type): bool
    {
        return in_array($type, self::PROTECTED, true);
    }

    /**
     * Las entradas de `$config['roles']['types']`: cada tipo con etiqueta, con su nombre de rol y las rutas de su
     * segmento (más las suyas, si las declara). Sin el módulo de organizaciones, el administrador de organización no
     * tiene rol.
     *
     * Las claves son las posiciones de antes (los de fábrica, de 0 a 6 en el orden de sus prioridades; los propios,
     * detrás): no se reindexan.
     *
     * @param array{generals:string[],administratives:string[],superiors:string[]} $segments
     * @return array<int,array{code:int,name:string,all:bool,allowed_routes:string[]}>
     */
    public static function roles(array $segments): array
    {
        $withOrganizations = defined('CRITICAL_CONSTANTS') && is_array(CRITICAL_CONSTANTS) && (bool) (CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE'] ?? false);

        //La posición de cada uno de fábrica es fija, aunque se retire otro; los propios van detrás, en su orden.
        $corePositions = array_flip(array_keys(self::CORE_PRIORITY));
        $nextPosition = count(self::CORE_PRIORITY);
        $roles = [];
        foreach (self::state()['types'] as $code => $type) {
            $index = $corePositions[$code] ?? $nextPosition++;
            if ($type['label'] === null) {
                continue;
            }
            if ($code === UsersModel::TYPE_USER_ADMIN_ORG && !$withOrganizations) {
                continue;
            }
            $routes = $segments[$type['permissions']];
            if ($type['routes'] !== []) {
                $routes = array_values(array_unique(array_merge($routes, $type['routes'])));
            }
            $roles[$index] = [
                'code' => $code,
                'name' => $type['label'],
                'all' => false,
                'allowed_routes' => $routes,
            ];
        }
        return $roles;
    }

    /**
     * Pone las traducciones declaradas: las etiquetas y las descripciones, en el grupo de los usuarios. Se llama en
     * `final-configurations.php`, después de los inyectores de idioma.
     *
     * @return void
     */
    public static function injectTranslations(): void
    {
        foreach (self::state()['types'] as $type) {
            if ($type['label'] !== null) {
                foreach ($type['translations'] as $lang => $text) {
                    Config::addLangMessage($lang, UsersModel::LANG_GROUP, $type['label'], $text);
                }
            }
            if ($type['description'] !== null) {
                foreach ($type['descriptionTranslations'] as $lang => $text) {
                    Config::addLangMessage($lang, UsersModel::LANG_GROUP, $type['description'], $text);
                }
            }
        }
    }

    /**
     * Las etiquetas y descripciones declaradas que no tienen traducción a alguno de esos idiomas (el de las etiquetas
     * no cuenta). Es un aviso: sin traducción, se ven en español.
     *
     * @param string[] $langs
     * @return string[]
     */
    public static function missingTranslations(array $langs): array
    {
        $missing = [];
        foreach (self::state()['types'] as $code => $type) {
            $relabeled = $type['label'] !== null && ($type['label'] !== (self::CORE_LABELS[$code] ?? null));
            foreach ($langs as $lang) {
                if ($lang === self::LABEL_LANG) {
                    continue;
                }
                if ($relabeled && !isset($type['translations'][$lang])) {
                    $missing[] = "el tipo {$code}: la etiqueta «{$type['label']}» no tiene traducción a «{$lang}»";
                }
                if ($type['description'] !== null && !isset($type['descriptionTranslations'][$lang])) {
                    $missing[] = "el tipo {$code}: la descripción no tiene traducción a «{$lang}»";
                }
            }
        }
        return $missing;
    }

    /**
     * Los tipos retirados que un módulo encendido sigue asignando. Se evalúa cuando ya existen las constantes de los
     * módulos (después del arranque): un aviso para root y un fallo de `verify-integrity`, nunca una negativa al arrancar.
     *
     * @return string[]
     */
    public static function moduleConflicts(): array
    {
        $conflicts = [];
        foreach (self::retired() as $code) {
            foreach (self::MODULE_ASSIGNMENTS[$code] ?? [] as $constant => $what) {
                if (defined($constant) && constant($constant) === true) {
                    $conflicts[] = "el tipo {$code} está retirado y {$constant} está encendido: {$what}";
                }
            }
        }
        return $conflicts;
    }

    /**
     * Lo efectivo, leído del archivo la primera vez y memorizado.
     *
     * @return array{types:array<int,array{label:?string,priority:int,organization:bool,profile:bool,externalLogin:bool,authorityOverSameType:bool,autoApproval:bool,creatable:bool,permissions:string,routes:string[],translations:array<string,string>,description:?string,descriptionTranslations:array<string,string>}>,retired:int[],errors:string[],warnings:string[]}
     */
    private static function state(): array
    {
        if (self::$state !== null) {
            return self::$state;
        }
        $file = self::file();
        if (!is_file($file)) {
            return self::$state = self::build([]);
        }
        $data = null;
        $readError = null;
        try {
            //Solo datos: el archivo devuelve un array y se lee antes que las traducciones y los módulos.
            $data = (static function (string $path): mixed {
                return require $path;
            })($file);
        } catch (\Throwable $e) {
            $readError = 'el archivo no se pudo leer (' . get_class($e) . ': ' . $e->getMessage() . ')';
        }
        if (!is_array($data)) {
            return self::$state = ['types' => self::factory(), 'retired' => [], 'errors' => [$readError ?? 'el archivo no devuelve un array'], 'warnings' => []];
        }
        return self::$state = self::build($data);
    }

    /**
     * Los tipos de fábrica, en el orden de sus prioridades. El 50 va sin etiqueta: apagado.
     *
     * @return array<int,array{label:?string,priority:int,organization:bool,profile:bool,externalLogin:bool,authorityOverSameType:bool,autoApproval:bool,creatable:bool,permissions:string,routes:string[],translations:array<string,string>,description:?string,descriptionTranslations:array<string,string>}>
     */
    private static function factory(): array
    {
        $types = [];
        foreach (self::CORE_PRIORITY as $code => $priority) {
            $types[$code] = [
                'label' => self::CORE_LABELS[$code] ?? null,
                'priority' => $priority,
                'organization' => !in_array($code, self::CORE_NO_ORGANIZATION, true),
                'profile' => in_array($code, self::CORE_PROFILE, true),
                'externalLogin' => in_array($code, self::CORE_EXTERNAL_LOGIN, true),
                'authorityOverSameType' => !in_array($code, self::CORE_NO_AUTHORITY_OVER_SAME_TYPE, true),
                'autoApproval' => in_array($code, self::CORE_AUTO_APPROVAL, true),
                'creatable' => true,
                'permissions' => match ($code) {
                    UsersModel::TYPE_USER_ROOT => 'superiors',
                    UsersModel::TYPE_USER_ADMIN_GRAL => 'administratives',
                    default => 'generals',
                },
                'routes' => [],
                'translations' => [],
                'description' => null,
                'descriptionTranslations' => [],
            ];
        }
        return $types;
    }

    /**
     * Valida el contenido del archivo y lo aplica sobre los de fábrica; con un solo error, no aplica nada.
     *
     * No se aplica a medias: aceptar las entradas buenas y tirar las malas daría un reparto que nadie escribió.
     *
     * @param array<mixed> $data
     * @return array{types:array<int,array{label:?string,priority:int,organization:bool,profile:bool,externalLogin:bool,authorityOverSameType:bool,autoApproval:bool,creatable:bool,permissions:string,routes:string[],translations:array<string,string>,description:?string,descriptionTranslations:array<string,string>}>,retired:int[],errors:string[],warnings:string[]}
     */
    private static function build(array $data): array
    {
        $factory = self::factory();
        $types = $factory;
        $errors = [];
        $warnings = [];

        foreach (array_keys($data) as $key) {
            if (!in_array($key, ['types', 'retire'], true)) {
                $errors[] = "clave desconocida «{$key}»: el archivo solo lleva types y retire";
            }
        }
        $declared = $data['types'] ?? [];
        $retire = $data['retire'] ?? [];
        if (!is_array($declared) || !array_is_list($declared)) {
            $errors[] = 'types tiene que ser una lista de tipos';
            $declared = [];
        }
        if (!is_array($retire) || !array_is_list($retire)) {
            $errors[] = 'retire tiene que ser una lista de códigos';
            $retire = [];
        }

        $seen = [];
        foreach ($declared as $position => $entry) {
            if (!is_array($entry)) {
                $errors[] = "types[{$position}] no es un tipo (un array con su id)";
                continue;
            }
            $id = $entry['id'] ?? null;
            if (!is_int($id) || $id < 0) {
                $errors[] = "types[{$position}]: id tiene que ser un entero no negativo";
                continue;
            }
            $where = "el tipo {$id}";
            if (isset($seen[$id])) {
                $errors[] = "{$where} está declarado dos veces";
                continue;
            }
            $seen[$id] = true;
            if ($id === UsersModel::TYPE_USER_SYSTEM) {
                $errors[] = "{$where} es el del usuario del sistema: está reservado";
                continue;
            }
            $isCore = array_key_exists($id, $factory);
            foreach (array_keys($entry) as $key) {
                if (!in_array($key, $isCore ? self::CORE_TYPE_KEYS : self::TYPE_KEYS, true)) {
                    $errors[] = $isCore
                        ? "{$where} es de fábrica y solo admite label, translations, description, descriptionTranslations y creatable («{$key}»)"
                        : "{$where}: clave desconocida «{$key}»";
                }
            }
            $type = $types[$id] ?? [
                'label' => null,
                'priority' => 0,
                'organization' => true,
                'profile' => false,
                'externalLogin' => false,
                'authorityOverSameType' => true,
                'autoApproval' => false,
                'creatable' => true,
                'permissions' => 'generals',
                'routes' => [],
                'translations' => [],
                'description' => null,
                'descriptionTranslations' => [],
            ];

            //El 50 sin etiqueta está apagado: declararlo es encenderlo, así que la necesita, como un tipo propio.
            $needsLabel = !$isCore || $type['label'] === null;
            if (array_key_exists('label', $entry) || $needsLabel) {
                $label = $entry['label'] ?? null;
                if (!is_string($label) || trim($label) === '') {
                    $errors[] = "{$where}: label tiene que ser un texto no vacío";
                } elseif (is_numeric(trim($label))) {
                    $errors[] = "{$where}: label no puede ser un número («{$label}»): es el nombre de su rol";
                } else {
                    $type['label'] = trim($label);
                }
            }
            foreach (['translations', 'descriptionTranslations'] as $key) {
                if (!array_key_exists($key, $entry)) {
                    continue;
                }
                $texts = $entry[$key];
                $valid = is_array($texts);
                foreach (is_array($texts) ? $texts : [] as $lang => $text) {
                    $valid = $valid && is_string($lang) && $lang !== '' && is_string($text) && trim($text) !== '';
                }
                if ($valid && is_array($texts)) {
                    $type[$key] = $texts;
                } else {
                    $errors[] = "{$where}: {$key} tiene que ser idioma => texto, p. ej. ['en' => '…']";
                }
            }
            if (array_key_exists('description', $entry)) {
                if (is_string($entry['description']) && trim($entry['description']) !== '') {
                    $type['description'] = trim($entry['description']);
                } else {
                    $errors[] = "{$where}: description tiene que ser un texto no vacío";
                }
            }
            if (array_key_exists('creatable', $entry)) {
                if (is_bool($entry['creatable'])) {
                    $type['creatable'] = $entry['creatable'];
                } else {
                    $errors[] = "{$where}: creatable tiene que ser true o false";
                }
            }

            if (!$isCore) {
                $priority = $entry['priority'] ?? null;
                if (!is_int($priority)) {
                    $errors[] = "{$where}: priority es obligatoria y entera";
                } elseif ($priority >= self::PRIORITY_LIMIT) {
                    $errors[] = "{$where}: priority tiene que ser menor que " . self::PRIORITY_LIMIT . " (la de root), y es {$priority}";
                } else {
                    $type['priority'] = $priority;
                    if ($priority >= self::PRIORITY_WARNING) {
                        $warnings[] = "{$where}: priority {$priority} lo pone al nivel de la administración general";
                    }
                }
                foreach (['organization', 'profile', 'externalLogin', 'authorityOverSameType', 'autoApproval'] as $key) {
                    if (array_key_exists($key, $entry)) {
                        if (is_bool($entry[$key])) {
                            $type[$key] = $entry[$key];
                        } else {
                            $errors[] = "{$where}: {$key} tiene que ser true o false";
                        }
                    }
                }
                if (array_key_exists('permissions', $entry)) {
                    if (is_string($entry['permissions']) && in_array($entry['permissions'], self::SEGMENTS, true)) {
                        $type['permissions'] = $entry['permissions'];
                    } else {
                        $errors[] = "{$where}: permissions tiene que ser generals, administratives o superiors";
                    }
                }
                if (array_key_exists('routes', $entry)) {
                    $routes = $entry['routes'];
                    $valid = is_array($routes) && array_is_list($routes);
                    foreach ($valid ? $routes : [] as $route) {
                        $valid = $valid && is_string($route) && trim($route) !== '';
                    }
                    if ($valid && is_array($routes)) {
                        $type['routes'] = $routes;
                    } else {
                        $errors[] = "{$where}: routes tiene que ser una lista de nombres de ruta";
                    }
                }
            }
            $types[$id] = $type;
        }

        $retired = [];
        foreach ($retire as $position => $id) {
            if (!is_int($id) || $id < 0) {
                $errors[] = "retire[{$position}] no es un código de tipo";
            } elseif (in_array($id, self::PROTECTED, true)) {
                $errors[] = "el tipo {$id} está protegido: no se retira";
            } elseif (in_array($id, self::RESERVED, true)) {
                $errors[] = "el tipo {$id} está reservado: no se retira";
            } elseif (isset($seen[$id])) {
                $errors[] = "el tipo {$id} está en types y en retire a la vez";
            } else {
                $retired[] = $id;
                unset($types[$id]);
            }
        }

        //La etiqueta es el nombre del rol: no puede repetirse, tampoco con otra mayúscula ni contra las de fábrica.
        $byLabel = [];
        foreach ($types as $code => $type) {
            if ($type['label'] === null) {
                continue;
            }
            $key = mb_strtolower($type['label']);
            if (isset($byLabel[$key])) {
                $errors[] = "la etiqueta «{$type['label']}» se repite (tipos {$byLabel[$key]} y {$code}): es el nombre del rol";
            } else {
                $byLabel[$key] = $code;
            }
        }

        if ($errors !== []) {
            return ['types' => $factory, 'retired' => [], 'errors' => $errors, 'warnings' => []];
        }
        return ['types' => $types, 'retired' => $retired, 'errors' => [], 'warnings' => $warnings];
    }

}
