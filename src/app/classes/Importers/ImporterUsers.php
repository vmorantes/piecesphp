<?php

/**
 * ImporterUsers.php
 */
namespace Importers;

use App\Model\UsersModel;
use PiecesPHP\Core\Importer\Collections\FieldCollection;
use PiecesPHP\Core\Importer\Field;
use PiecesPHP\Core\Importer\Importer;
use PiecesPHP\Core\Importer\Schema;
use PiecesPHP\Core\Validation\Validator;

/**
 * ImporterUsers.
 *
 * Importador de usuarios
 *
 * @package     Importers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ImporterUsers extends Importer
{

    const LANG_GROUP = 'importerModule';

    public function __construct(array $data)
    {
        $id              = new Field('id', __(self::LANG_GROUP, 'ID'), null, true, '', false);
        $username        = new Field('username', __(self::LANG_GROUP, 'Usuario'), '', false);
        $password        = new Field('password', __(self::LANG_GROUP, 'Contraseña'), '', false);
        $firstname       = new Field('firstname', __(self::LANG_GROUP, 'Primer nombre'), '', false);
        $secondname      = new Field('secondname', __(self::LANG_GROUP, 'Segundo nombre'), '', true);
        $first_lastname  = new Field('first_lastname', __(self::LANG_GROUP, 'Primer apellido'), '', false);
        $second_lastname = new Field('second_lastname', __(self::LANG_GROUP, 'Segundo apellido'), '', true);
        $email           = new Field('email', __(self::LANG_GROUP, 'Email'), '', false);
        $type            = new Field('type', __(self::LANG_GROUP, 'Tipo'), UsersModel::TYPE_USER_GENERAL, true, '', false);

        $email->setValidator(function ($value) {
            return Validator::isEmail($value);
        });

        $password->setValidator(function ($value) {
            return is_string($value);
        })->setParser(function ($value) {
            return password_hash($value, \PASSWORD_DEFAULT);
        });

        $fields = new FieldCollection([
            $id,
            $username,
            $password,
            $firstname,
            $secondname,
            $first_lastname,
            $second_lastname,
            $email,
            $type,
        ]);

        $schema = new Schema($fields, 'pcsphp_users', function (Schema $instance) {

            $id = $instance->getFieldByName('id')->getValue();
            $username = $instance->getFieldByName('username')->getValue();
            $email = $instance->getFieldByName('email')->getValue();

            $duplicatedID = is_object((new UsersModel())->getByID($id));
            $duplicatedUsername = UsersModel::isDuplicateUsername($username);
            $duplicatedEmail = UsersModel::isDuplicateEmail($email);

            $messageDuplicated = [];

            if ($duplicatedID) {
                $messageDuplicated[] = vsprintf(
                    __(self::LANG_GROUP, "El ID '%s' ya existe."),
                    [
                        $id,
                    ]
                );
            }

            if ($duplicatedUsername) {
                $messageDuplicated[] = vsprintf(
                    __(self::LANG_GROUP, "El usuario '%s' ya existe."),
                    [
                        $username,
                    ]
                );
            }

            if ($duplicatedEmail) {
                $messageDuplicated[] = vsprintf(
                    __(self::LANG_GROUP, "El email '%s' ya existe."),
                    [
                        $email,
                    ]
                );
            }

            if (count($messageDuplicated) > 0) {
                $messageDuplicated = implode('<br>', $messageDuplicated);
                throw new \Exception($messageDuplicated);
            }

        });

        $schema->setTemplateWithHumanReadable(true);

        parent::__construct($schema, $data, __(self::LANG_GROUP, 'Agregar de usuarios'));

    }

}
