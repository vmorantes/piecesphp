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
    public function __construct(array $data)
    {
        $username = new Field('username', 'Usuario', '', false);
        $password = new Field('password', 'Contraseña', '', false);
        $firstname = new Field('firstname', 'Primer nombre', '', false);
        $secondname = new Field('secondname', 'Segundo nombre', '', true);
        $first_lastname = new Field('first_lastname', 'Primer apellido', '', false);
        $second_lastname = new Field('second_lastname', 'Segundo apellido', '', true);
        $email = new Field('email', 'Email', '', false);
        $type = new Field('type', 'Tipo', UsersModel::TYPE_USER_GENERAL, true, '', false);

        $email->setValidator(function ($value) {
            return Validator::isEmail($value);
        });

        $password->setValidator(function ($value) {
            return is_string($value);
        })->setParser(function ($value) {
            return password_hash($value, \PASSWORD_DEFAULT);
        });

        $fields = new FieldCollection([
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

            $username = $instance->getFieldByName('username')->getValue();
            $email = $instance->getFieldByName('email')->getValue();

            $duplicatedUsername = UsersModel::isDuplicateUsername($username);
            $duplicatedEmail = UsersModel::isDuplicateEmail($email);

            if ($duplicatedUsername && $duplicatedEmail) {

                throw new \Exception('El email y el usuario están ya existen.');

            } else if ($duplicatedUsername) {

                throw new \Exception('El usuario ya existe.');

            } else if ($duplicatedEmail) {

                throw new \Exception('El email ya existe.');

            }

        });

        $schema->setTemplateWithHumanReadable(true);

        parent::__construct($schema, $data, 'Importador de usuarios');

    }

}
