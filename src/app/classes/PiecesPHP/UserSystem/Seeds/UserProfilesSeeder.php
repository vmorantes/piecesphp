<?php

/**
 * UserProfilesSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * UserProfilesSeeder.
 *
 * El perfil COMPLETO (`UserProfileMapper::profileIsComplete()`) de cada usuario sembrado de un tipo de
 * TYPES_USER_SHOULD_HAVE_PROFILE, para que el Directorio y los perfiles públicos se vean poblados. Con la
 * cantidad por defecto son exactamente esos; con otra, se completa con los demás usuarios sembrados.
 *
 * Tiene que sembrarse aquí y no rellenarse después: el arranque (`ProfileTasksUtilities::generateMissingProfiles()`)
 * crea un perfil VACÍO a todo usuario de esos tipos que no lo tenga, y en el proceso de la siembra los usuarios se
 * escriben después del arranque, así que este llega primero. El limpiado borra estos por su marca y, después, los
 * vacíos que queden por `UsersSeeder::ownedRows()`.
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UserProfilesSeeder extends ModuleSeeder
{

    const JOB_POSITIONS = [
        'Coordinadora de proyectos',
        'Analista de datos',
        'Gestor de alianzas',
        'Investigadora',
        'Responsable de comunicaciones',
        'Asesor técnico',
    ];

    const INSTITUTIONS = [
        'Universidad de ejemplo',
        'Red de cooperación local',
        'Fundación de prueba',
    ];

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return UserProfileMapper::class;
    }

    /**
     * El cargo: es texto libre, se pinta escapado en la ficha y en el Directorio, y el perfil lo exige.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'jobPosition';
    }

    public function dependsOn(): array
    {
        return [UsersModel::class, CityMapper::class];
    }

    /**
     * Tantos como usuarios con perfil siembra `UsersSeeder`: tres por cada tipo con perfil.
     *
     * @return int
     */
    public function defaultCount(): int
    {
        return UsersSeeder::USERS_PER_TYPE * count(self::profileTypes());
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     * @throws \RuntimeException Si no hay un usuario sembrado con perfil para esa fila, o no hay usuario principal.
     */
    public function row(int $index, SeedContext $context): array
    {
        if ($this->author === null) {
            $this->author = $context->existingUserId(UsersModel::TYPE_USER_ROOT);
            if ($this->author === null) {
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de los perfiles');
            }
        }
        //Primero los usuarios de los tipos con perfil; con una cantidad pedida que no los alcance (la suite de ida y
        //vuelta siembra dos de cada tabla), los demás sembrados, para no repetir dueño.
        $seededUsers = $context->seeded(UsersModel::class);
        $hasProfile = static fn (array $user): bool => in_array((int) ($user['type'] ?? -1), self::profileTypes(), true);
        $users = array_values(array_merge(array_filter($seededUsers, $hasProfile), array_filter($seededUsers, static fn (array $user): bool => !$hasProfile($user))));
        if (!isset($users[$index])) {
            throw new \RuntimeException('PARA: hay más perfiles que usuarios sembrados: la fila #' . ($index + 1) . ' de los perfiles no tiene dueño');
        }
        $user = $users[$index];
        $belongsTo = $user['id'] ?? ('<id del usuario sembrado ' . (string) ($user['username'] ?? '') . '>');
        $city = $context->reference(CityMapper::class, $index);
        return [
            'jobPosition' => $this->text('cargo', $index) . ' ' . self::JOB_POSITIONS[$index % count(self::JOB_POSITIONS)],
            'phoneCode' => '+57',
            'phoneNumber' => '601555' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            'nationality' => 'Colombiana',
            'linkedinLink' => 'https://example.org/linkedin/persona-' . ($index + 1),
            'websiteLink' => 'https://example.org/persona-' . ($index + 1),
            'country' => self::countryOfCity($city, $index, $context),
            'city' => $city,
            //Fijas por índice, alrededor de Bogotá: el mapa tiene que salir igual en cada siembra.
            'latitude' => round(4.55 + ($index % 6) * 0.04, 6),
            'longitude' => round(-74.15 + ($index % 5) * 0.03, 6),
            'affiliatedInstitutions' => array_slice(self::INSTITUTIONS, 0, 1 + $index % count(self::INSTITUTIONS)),
            'publicVisible' => 1,
            'belongsTo' => $belongsTo,
            'createdBy' => $this->author,
        ];
    }

    /**
     * Los tipos de usuario sembrados que llevan perfil.
     *
     * @return int[]
     */
    public static function profileTypes(): array
    {
        return array_values(array_filter(UsersSeeder::types(), static fn (int $type): bool => in_array($type, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE, true)));
    }

    /**
     * El país de la ciudad sembrada, por su región: una ciudad de un país y un país de otro no casan en la ficha.
     * Al simular no hay ids, y se pide la referencia por índice.
     *
     * @param string|int $city
     * @param int $index
     * @param SeedContext $context
     * @return string|int
     */
    public static function countryOfCity(string|int $city, int $index, SeedContext $context): string|int
    {
        if (is_int($city)) {
            foreach ($context->seeded(CityMapper::class) as $cityRow) {
                if (($cityRow['id'] ?? null) !== $city) {
                    continue;
                }
                foreach ($context->seeded(StateMapper::class) as $stateRow) {
                    if (($stateRow['id'] ?? null) === $cityRow['state'] && is_int($stateRow['country'] ?? null)) {
                        return $stateRow['country'];
                    }
                }
            }
        }
        return $context->reference(CountryMapper::class, $index);
    }

}
