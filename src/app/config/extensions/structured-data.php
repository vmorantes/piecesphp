<?php

/**
 * @pcsphp-config clon
 * Qué conviene editar aquí: cómo se presenta la organización en los datos estructurados (JSON-LD) de cada página pública: su subtipo de schema.org, sus perfiles en redes y cualquier otra propiedad. Por omisión, una Organization con el nombre, la URL y el logotipo de «Identidad y SEO».
 */

//@codigo-comentado · Una fundación con sus perfiles. @type, de StructuredData::ORGANIZATION_TYPES; sameAs, solo URL absolutas http(s). Lo rechazado no se aplica y en local se ve el error.
//\PiecesPHP\Core\Utilities\Helpers\StructuredData::extendOrganization([
//    '@type' => 'NGO',
//    'sameAs' => [
//        'https://www.facebook.com/ejemplo',
//        'https://www.instagram.com/ejemplo',
//    ],
//]);
