<?php

/**
 * OrganizationsSeeder.php
 */

namespace Organizations\Seeds;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Seeds\UserProfilesSeeder;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * OrganizationsSeeder.
 *
 * Organizaciones de prueba. Su autor (`createdBy`, obligatorio) es el usuario principal que ya
 * existe, LEÍDO de la base: los usuarios sembrados dependen de las organizaciones, así que
 * declararlos como autores cerraría un ciclo. El encargado se pone en afterAll(), cuando ya hay
 * usuarios sembrados: sin él, el formulario de «Mi organización» no se pinta.
 *
 * Con el perfil ENTERO (`OrganizationMapper::profileIsComplete()` y lo demás que pinta la ficha), y el
 * logo copiado a donde nace al subirlo por el formulario: privado, en `statics/uploads/organizations/<folder>/`.
 *
 * @package     Organizations\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OrganizationsSeeder extends ModuleSeeder
{

    const UPLOADS = 'statics/uploads/organizations';
    const LOGO = 'statics/images/default-avatar-org.jpg';

    /** Sector de actividad: en el idioma por defecto y, para los demás, en inglés. */
    const SECTORS = [
        ['Educación', 'Education'],
        ['Medio ambiente', 'Environment'],
        ['Salud', 'Health'],
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
        return OrganizationMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    /**
     * Cada arranque crea su fila de aprobación (`SystemApprovalManager::init()`), que apunta por
     * número de id y por tabla.
     *
     * @return array<string,string|array{column: string, where: array<string,string|int>}>
     */
    public function ownedRows(): array
    {
        return [SystemApprovalsMapper::TABLE => ['column' => 'referenceValue', 'where' => ['referenceTable' => OrganizationMapper::TABLE]]];
    }

    /**
     * La carpeta del logo, que lleva el prefijo en la columna `folder`.
     *
     * @return array{base: string, column: string}
     */
    public function ownedFolder(): ?array
    {
        return ['base' => self::UPLOADS, 'column' => 'folder'];
    }

    public function dependsOn(): array
    {
        return [CityMapper::class];
    }

    public function defaultCount(): int
    {
        return 3;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     * @throws \RuntimeException Si no hay un usuario principal que poner de autor.
     */
    public function row(int $index, SeedContext $context): array
    {
        if ($this->author === null) {
            $this->author = $context->existingUserId(UsersModel::TYPE_USER_ROOT);
            if ($this->author === null) {
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de las organizaciones');
            }
        }
        $folder = $this->text('organizacion', $index);
        $city = $context->reference(CityMapper::class, $index);
        [$sector, $sectorTranslated] = self::SECTORS[$index % count(self::SECTORS)];
        $langData = new \stdClass();
        foreach (Config::get_allowed_langs() as $lang) {
            if ($lang !== Config::get_default_lang()) {
                $langData->$lang = (object) ['activitySector' => $sectorTranslated];
            }
        }
        $number = $index + 1;
        return [
            'name' => $folder,
            'nit' => $this->text('nit', $index),
            'size' => array_keys(OrganizationMapper::SIZES)[$index % count(OrganizationMapper::SIZES)],
            'activitySector' => $sector,
            'actionLines' => array_slice(array_keys(OrganizationMapper::ACTION_LINES), 0, 1 + $index % count(OrganizationMapper::ACTION_LINES)),
            'esal' => $index % 2 === 0 ? OrganizationMapper::ESAL_YES : OrganizationMapper::ESAL_NO,
            'country' => UserProfilesSeeder::countryOfCity($city, $index, $context),
            'city' => $city,
            'address' => 'Calle ' . (10 + $number) . ' # ' . (5 + $number) . '-' . (20 + $number),
            'phoneCode' => '+57',
            'phone' => '601556' . str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'linkedinLink' => 'https://example.org/linkedin/organizacion-' . $number,
            'websiteLink' => 'https://example.org/organizacion-' . $number,
            'informativeEmail' => 'contacto-organizacion-' . $number . '@example.invalid',
            'billingEmail' => 'facturacion-organizacion-' . $number . '@example.invalid',
            //Fijas por índice, alrededor de Bogotá, y distintas de las de las personas.
            'latitude' => round(4.57 + ($index % 6) * 0.04, 6),
            'longitude' => round(-74.12 + ($index % 4) * 0.025, 6),
            'affiliatedInstitutions' => array_slice(self::INSTITUTIONS, 0, 1 + $index % count(self::INSTITUTIONS)),
            'logo' => $context->copyToUploads(self::LOGO, self::UPLOADS, $folder, 'logo.jpg', true),
            'langData' => $langData,
            'folder' => $folder,
            'createdBy' => $this->author,
            'createdAt' => date('Y-m-d H:i:s'),
            'status' => OrganizationMapper::ACTIVE,
            'publicVisible' => 1,
        ];
    }

    /**
     * A cada organización sembrada, un encargado entre sus usuarios sembrados: el administrador de organización y, si
     * no hay, el primero que pueda serlo. Sin tocar quién ni cuándo la modificó.
     *
     * @param SeedContext $context
     * @return string[]
     */
    public function afterAll(SeedContext $context): array
    {
        $users = $context->seeded(UsersModel::class);
        $lines = [];
        foreach ($context->seeded(OrganizationMapper::class) as $organizationRow) {
            $organizationID = (int) ($organizationRow['id'] ?? 0);
            $members = array_values(array_filter($users, static fn (array $user): bool => (int) ($user['organization'] ?? 0) === $organizationID));
            $chosen = null;
            foreach ($members as $user) {
                if ((int) ($user['type'] ?? -1) === UsersModel::TYPE_USER_ADMIN_ORG) {
                    $chosen = $user;
                    break;
                }
            }
            if ($chosen === null) {
                foreach ($members as $user) {
                    if (in_array((int) ($user['type'] ?? -1), OrganizationMapper::PROFILE_EDITOR, true)) {
                        $chosen = $user;
                        break;
                    }
                }
            }
            if ($chosen === null) {
                $lines[] = "organización {$organizationID}: sin usuario que pueda ser encargado";
                continue;
            }
            $organization = new OrganizationMapper($organizationID);
            $organization->administrator = (int) $chosen['id'];
            if (!$organization->update(true)) {
                throw new \RuntimeException("no se pudo poner el encargado de la organización {$organizationID}");
            }
            $lines[] = "organización {$organizationID}: encargado {$chosen['id']} ({$chosen['username']})";
        }
        return $lines;
    }
}
