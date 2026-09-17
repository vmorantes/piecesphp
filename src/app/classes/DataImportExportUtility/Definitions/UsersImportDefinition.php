<?php

/**
 * UsersImportDefinition.php
 */

namespace DataImportExportUtility\Definitions;

use App\Model\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportPersistException;
use PiecesPHP\Core\DataTransfer\Import\ParsedRow;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

/**
 * UsersImportDefinition - Importación de usuarios: todo el archivo en una transacción y credenciales de entrega única.
 *
 * @package     DataImportExportUtility\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UsersImportDefinition extends ImportDefinition
{

    //Añade aquí los tipos que se pueden importar; nunca administradores.
    const IMPORTABLE_TYPES = [
        UsersModel::TYPE_USER_GENERAL,
    ];

    //Fichas de credenciales por página impresa.
    const CARDS_PER_PAGE = 6;

    const LANG_GROUP = DataImportExportUtilityLang::LANG_GROUP;

    /**
     * @return string
     */
    public function key(): string
    {
        return 'users';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return __(self::LANG_GROUP, 'Usuarios');
    }

    /**
     * @return int[]
     */
    public function allowedUserTypes(): array
    {
        return [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];
    }

    /**
     * @return Column[]
     */
    public function columns(): array
    {
        return [
            new Column('username', __(self::LANG_GROUP, 'Usuario'), true, ['user', 'nombre de usuario']),
            new Column('email', __(self::LANG_GROUP, 'Correo'), true, ['correo electrónico', 'e-mail'], function (?string $value) {
                //Validator::isEmail() consulta el MX por DNS: aquí solo el formato.
                return filter_var($value, \FILTER_VALIDATE_EMAIL) !== false ? null : sprintf(__(self::LANG_GROUP, 'Correo: «%s» no es un correo válido.'), $value);
            }),
            new Column('firstname', __(self::LANG_GROUP, 'Primer nombre'), true, ['nombre']),
            new Column('secondname', __(self::LANG_GROUP, 'Segundo nombre')),
            new Column('first_lastname', __(self::LANG_GROUP, 'Primer apellido'), true, ['apellido']),
            new Column('second_lastname', __(self::LANG_GROUP, 'Segundo apellido')),
            new Column('password', __(self::LANG_GROUP, 'Contraseña')),
            new Column('type', __(self::LANG_GROUP, 'Tipo'), false, [], function (?string $value) {
                return $this->typeError($value);
            }),
            new Column('organization', __(self::LANG_GROUP, 'Organización'), false, [], function (?string $value) {
                if ($value === null) {
                    return null;
                }
                return ctype_digit($value) && OrganizationMapper::existsByID((int) $value) ? null : sprintf(__(self::LANG_GROUP, 'Organización: no existe la organización «%s».'), $value);
            }),
        ];
    }

    /**
     * Duplicados en la base y dentro del archivo, sin distinguir mayúsculas.
     *
     * @param ParsedRow[] $rows
     * @return array<int,string[]>
     */
    public function validateAll(array $rows): array
    {
        $errors = [];
        $seenUsernames = [];
        $seenEmails = [];
        foreach ($rows as $row) {
            $position = $row->position();
            $username = (string) $row->get('username');
            $email = mb_strtolower((string) $row->get('email'));
            $usernameKey = mb_strtolower($username);

            if ($username !== '' && UsersModel::isDuplicateUsername($username)) {
                $errors[$position][] = sprintf(__(self::LANG_GROUP, 'Usuario: «%s» ya existe.'), $username);
            }
            if ($email !== '' && UsersModel::isDuplicateEmail($email)) {
                $errors[$position][] = sprintf(__(self::LANG_GROUP, 'Correo: «%s» ya existe.'), $email);
            }
            if ($username !== '' && isset($seenUsernames[$usernameKey])) {
                $errors[$position][] = sprintf(__(self::LANG_GROUP, 'Usuario: «%s» se repite en la fila %d.'), $username, $seenUsernames[$usernameKey]);
            } else {
                $seenUsernames[$usernameKey] = $position;
            }
            if ($email !== '' && isset($seenEmails[$email])) {
                $errors[$position][] = sprintf(__(self::LANG_GROUP, 'Correo: «%s» se repite en la fila %d.'), $email, $seenEmails[$email]);
            } else {
                $seenEmails[$email] = $position;
            }
        }
        return $errors;
    }

    /**
     * @param ParsedRow[] $rows
     * @return ImportArtifacts|null
     * @throws ImportPersistException
     */
    public function persist(array $rows): ?ImportArtifacts
    {
        $importer = getLoggedFrameworkUser();
        $importerOrganization = $importer !== null ? $importer->organization : null;

        $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
        if ($pdo === null) {
            throw new ImportPersistException(__(self::LANG_GROUP, 'No se pudo guardar la importación; no se guardó ningún usuario.'));
        }

        //Las contraseñas generadas viven solo aquí y en el artefacto: a la base va el hash.
        $generated = [];

        try {
            $pdo->beginTransaction();

            foreach ($rows as $row) {
                $type = $this->resolveType($row->get('type')) ?? UsersModel::TYPE_USER_GENERAL;
                $givenPassword = $row->get('password');
                $password = $givenPassword ?? generate_pass(12)['password'];

                $user = new UsersModel();
                $user->username = (string) $row->get('username');
                $user->email = mb_strtolower((string) $row->get('email'));
                $user->password = password_hash($password, \PASSWORD_DEFAULT);
                $user->firstname = ucwords((string) $row->get('firstname'));
                $user->secondname = ucwords((string) $row->get('secondname'));
                $user->first_lastname = ucwords((string) $row->get('first_lastname'));
                $user->second_lastname = ucwords((string) $row->get('second_lastname'));
                $user->type = $type;
                $user->status = UsersModel::STATUS_USER_ACTIVE;
                $user->failed_attempts = 0;
                $user->created_at = new \DateTime();
                $user->modified_at = $user->created_at;

                //Misma regla que el alta por formulario.
                if (!in_array($type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION)) {
                    $organization = $row->get('organization');
                    $user->organization = $organization !== null
                        ? (int) $organization
                        : ($importerOrganization !== null ? (int) $importerOrganization : OrganizationMapper::INITIAL_ID_GLOBAL);
                }

                //save() no rellena el id: se lee de la misma conexión, dentro de la transacción.
                if (!$user->save()) {
                    throw new \RuntimeException('No se guardó el usuario de la fila ' . $row->position() . '.');
                }
                $userID = (int) $user->getLastInsertID();
                if ($userID <= 0 || UserProfileMapper::createProfile($userID) === null) {
                    throw new \RuntimeException('No se creó el perfil del usuario de la fila ' . $row->position() . '.');
                }

                if ($givenPassword === null) {
                    $generated[] = [
                        'name' => trim(implode(' ', array_filter([$user->firstname, $user->secondname, $user->first_lastname, $user->second_lastname], fn($p) => is_string($p) && $p !== ''))),
                        'username' => $user->username,
                        'password' => $password,
                    ];
                }
            }

            $pdo->commit();

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_exception($e);
            throw new ImportPersistException(__(self::LANG_GROUP, 'No se pudo guardar la importación; no se guardó ningún usuario.'));
        }

        if (count($generated) === 0) {
            return null;
        }

        return new ImportArtifacts(
            'credenciales-usuarios-' . date('Ymd-His') . '.html',
            'text/html',
            self::credentialsSheet($generated)
        );
    }

    /**
     * @param string|null $value
     * @return string|null
     */
    private function typeError(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $type = $this->resolveType($value);
        if ($type === null || !in_array($type, self::IMPORTABLE_TYPES, true)) {
            return sprintf(__(self::LANG_GROUP, 'Tipo: «%s» no se puede importar.'), $value);
        }
        //Nadie importa un tipo con tanta o más prioridad que la suya.
        $importer = getLoggedFrameworkUser();
        $importerPriority = $importer !== null ? (UsersModel::TYPES_USER_PRIORITY[(int) $importer->type] ?? null) : null;
        if ($importerPriority === null || UsersModel::TYPES_USER_PRIORITY[$type] >= $importerPriority) {
            return sprintf(__(self::LANG_GROUP, 'Tipo: no tienes permiso para importar usuarios de tipo «%s».'), $value);
        }
        return null;
    }

    /**
     * Código numérico o nombre de UsersModel::TYPES_USERS, sin mayúsculas.
     *
     * @param string|null $value
     * @return int|null
     */
    private function resolveType(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (ctype_digit($value)) {
            return array_key_exists((int) $value, UsersModel::TYPES_USERS) ? (int) $value : null;
        }
        foreach (UsersModel::TYPES_USERS as $code => $name) {
            if (mb_strtolower($name) === mb_strtolower(trim($value))) {
                return (int) $code;
            }
        }
        return null;
    }

    /**
     * HTML autónomo, sin recursos externos: se descarga una vez y se imprime.
     *
     * @param array<int,array{name:string,username:string,password:string}> $cards
     * @return string
     */
    public static function credentialsSheet(array $cards): string
    {
        $e = fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $loginURL = (string) get_route('users-form-login', [], true);
        $title = __(self::LANG_GROUP, 'Credenciales de acceso');

        $html = [];
        $html[] = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>' . $e($title) . '</title><style>';
        $html[] = 'body{font-family:sans-serif;margin:0;padding:1cm}.page{display:grid;grid-template-columns:1fr 1fr;gap:.5cm}';
        $html[] = '.card{border:1px dashed #555;padding:.5cm;break-inside:avoid}.card dt{font-weight:bold;margin-top:.2cm}.card dd{margin:0;font-family:monospace;font-size:14pt}';
        $html[] = '@media print{.page{break-after:page}.page:last-child{break-after:auto}}';
        $html[] = '</style></head><body>';
        foreach (array_chunk($cards, self::CARDS_PER_PAGE) as $page) {
            $html[] = '<section class="page">';
            foreach ($page as $card) {
                $html[] = '<div class="card"><dl>'
                    . '<dt>' . $e(__(self::LANG_GROUP, 'Nombre')) . '</dt><dd>' . $e($card['name']) . '</dd>'
                    . '<dt>' . $e(__(self::LANG_GROUP, 'Usuario')) . '</dt><dd>' . $e($card['username']) . '</dd>'
                    . '<dt>' . $e(__(self::LANG_GROUP, 'Contraseña')) . '</dt><dd>' . $e($card['password']) . '</dd>'
                    . '<dt>' . $e(__(self::LANG_GROUP, 'Inicio de sesión')) . '</dt><dd>' . $e($loginURL) . '</dd>'
                    . '</dl></div>';
            }
            $html[] = '</section>';
        }
        $html[] = '</body></html>';

        return implode("\n", $html);
    }
}
