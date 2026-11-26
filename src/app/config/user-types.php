<?php

/**
 * @pcsphp-config clon
 * Qué conviene editar aquí: todo, solo las diferencias con los de fábrica y solo datos (sin __() ni constantes de módulo); root y administración general no se retiran. Guía: «Los tipos de usuario».
 */

//@codigo-comentado · Para nombrar los de fábrica por su constante:
//use PiecesPHP\UserSystem\ORM\UsersModel;

return [
    'types' => [
        //@codigo-comentado · Un tipo propio: id, etiqueta y prioridad son obligatorios.
        //[
        //    'id' => 5,
        //    'label' => 'Telemarketing',
        //    'translations' => ['en' => 'Telemarketing'],
        //    'priority' => 3,
        //],
        //@codigo-comentado · Uno de fábrica: admite lo mismo que uno propio salvo id; root y la administración general, solo textos, creatable, profile y routes.
        //['id' => UsersModel::TYPE_USER_INSTITUCIONAL, 'profile' => true],
    ],
    'retire' => [],
];
