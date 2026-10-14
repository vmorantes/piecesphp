<?php

//El sembrador descubre los seeders, los ordena y sin pedirlo solo simula. Todo en memoria: el autor de
//las organizaciones, que en la tarea se lee de la base, aquí lo pone un contexto de prueba.

use News\Mappers\NewsMapper;
use Newsletter\Mappers\NewsletterSuscriberMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\App\Locations\Mappers\PointMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;
use Terminal\Seeding\SeedPlanner;
use Terminal\Tasks\SeedTask;

CliActions::make('unit-tests:core/seed-planner', function ($args) {

    echoTerminal("\e[33m[TEST:SeedPlanner] La siembra respeta las dependencias, detecta los ciclos y sin pedirlo solo simula\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };
    $error = function (callable $run): string {
        try {
            $run();
            return '';
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }
    };
    //Un seeder de prueba: el mapper y sus dependencias, sin filas.
    $factory = new class {
        /**
         * @param class-string<EntityMapper> $mapper
         * @param array<class-string<EntityMapper>> $dependencies
         * @return ModuleSeeder
         */
        public function make(string $mapper, array $dependencies): ModuleSeeder
        {
            return new class ($mapper, $dependencies) extends ModuleSeeder {
                /**
                 * @param class-string<EntityMapper> $target
                 * @param array<class-string<EntityMapper>> $dependencies
                 */
                public function __construct(private string $target, private array $dependencies)
                {
                }
                public function mapper(): string
                {
                    return $this->target;
                }
                public function markerColumn(): string
                {
                    return 'id';
                }
                public function dependsOn(): array
                {
                    return $this->dependencies;
                }
                public function row(int $index, SeedContext $context): array
                {
                    return [];
                }
            };
        }
    };

    $seeders = SeedPlanner::discover('all');
    $classes = array_map('get_class', $seeders);
    //La lista exacta: un seeder nuevo se añade aquí a propósito, y uno que se cuele la rompe.
    $expected = [
        'Documents\Seeds\DocumentsSeeder',
        'Forms\Seeds\DocumentTypesSeeder',
        'Forms\Seeds\FormCategoriesSeeder',
        'News\Seeds\NewsCategoriesSeeder',
        'News\Seeds\NewsSeeder',
        'Newsletter\Seeds\NewsletterSuscribersSeeder',
        'Organizations\Seeds\OrganizationsSeeder',
        'PiecesPHP\App\Locations\Seeds\CitiesSeeder',
        'PiecesPHP\App\Locations\Seeds\CountriesSeeder',
        'PiecesPHP\App\Locations\Seeds\PointsSeeder',
        'PiecesPHP\App\Locations\Seeds\StatesSeeder',
        'PiecesPHP\BuiltIn\Banner\Seeds\BuiltInBannersSeeder',
        'PiecesPHP\UserSystem\Seeds\UsersSeeder',
        'Publications\Seeds\PublicationAttachmentsSeeder',
        'Publications\Seeds\PublicationCategoriesSeeder',
        'Publications\Seeds\PublicationsSeeder',
    ];
    //Los dos de referencia, para las pruebas de orden que no deben depender de cuántos seeders haya.
    $reference = array_values(array_filter($seeders, static fn (ModuleSeeder $s): bool => in_array($s->mapper(), [OrganizationMapper::class, UsersModel::class], true)));

    echoTerminal('[canario] El instrumento ve');
    $check($classes === $expected, 'c1 el descubrimiento encuentra los ' . count($expected) . ' seeders esperados, y solo esos', implode(', ', $classes) ?: 'ninguno');
    $check(SeedPlanner::canary($seeders) === null, 'c2 el canario del propio sembrador pasa');
    echoTerminal(' ');

    echoTerminal('[1] El orden respeta las dependencias');
    $order = array_map(static fn (ModuleSeeder $s): string => $s->mapper(), SeedPlanner::order(array_reverse($reference)));
    $check($order === [OrganizationMapper::class, UsersModel::class], '1a las organizaciones van antes que los usuarios, aunque se pasen al revés', implode(' -> ', $order));
    $locations = array_values(array_filter($seeders, static fn (ModuleSeeder $s): bool => in_array($s->mapper(), [CountryMapper::class, StateMapper::class, CityMapper::class, PointMapper::class], true)));
    $chain = array_map(static fn (ModuleSeeder $s): string => $s->mapper(), SeedPlanner::order(array_reverse($locations)));
    $check($chain === [CountryMapper::class, StateMapper::class, CityMapper::class, PointMapper::class], '1b país -> región -> ciudad -> punto, aunque se pasen al revés', implode(' -> ', $chain));
    echoTerminal(' ');

    echoTerminal('[2] Lo que no se puede ordenar se para');
    $self = $error(fn () => SeedPlanner::order([$factory->make(NewsMapper::class, [NewsMapper::class])]));
    $check(str_starts_with($self, 'PARA: hay un ciclo'), '2a un seeder que depende de sí mismo es un ciclo', $self ?: 'no se detectó');
    $pair = $error(fn () => SeedPlanner::order([$factory->make(NewsMapper::class, [NewsletterSuscriberMapper::class]), $factory->make(NewsletterSuscriberMapper::class, [NewsMapper::class])]));
    $check(str_starts_with($pair, 'PARA: hay un ciclo'), '2b dos seeders que dependen el uno del otro son un ciclo', $pair ?: 'no se detectó');
    $orphan = $error(fn () => SeedPlanner::order([$factory->make(NewsMapper::class, [NewsletterSuscriberMapper::class])]));
    $check(str_contains($orphan, 'no tiene seeder'), '2c una dependencia sobre un mapper sin seeder se detecta, en vez de inventar la referencia', $orphan ?: 'no se detectó');
    echoTerminal(' ');

    echoTerminal('[3] Lo que genera');
    $context = new class extends SeedContext {
        public function existingUserId(int $type): ?int
        {
            return 1;
        }
    };
    $empty = SeedPlanner::simulate(SeedPlanner::order($seeders), 0, new SeedContext());
    $check(array_sum(array_map('count', $empty)) === 0, '3a con cantidad=0 no genera ninguna fila');
    $rows = SeedPlanner::simulate(SeedPlanner::order($seeders), 2, $context);
    $users = $rows[UsersModel::class] ?? [];
    $check(count($rows[OrganizationMapper::class] ?? []) === 2 && count($users) === 2, '3b con cantidad=2 genera dos filas por tabla');
    $check(($users[0]['password'] ?? null) === '<hash>', '3c la contraseña es el marcador <hash>, nunca un valor');
    $prefixed = array_filter([$users[0]['username'] ?? '', $users[0]['firstname'] ?? '', $rows[OrganizationMapper::class][0]['name'] ?? ''], static fn ($v): bool => str_starts_with((string) $v, ModuleSeeder::PREFIX));
    $check(count($prefixed) === 3, '3d los textos llevan el prefijo ' . ModuleSeeder::PREFIX);
    $check(($users[1]['organization'] ?? null) === '<id de ' . OrganizationMapper::class . ' #2>', '3e la referencia a la organización es un marcador de una fila sembrada, no un id inventado', var_export($users[1]['organization'] ?? null, true));
    //Una organización sin `nit`: el ORM no la guardaría, así que la simulación tiene que pararla.
    $withoutNit = new class extends ModuleSeeder {
        public function mapper(): string
        {
            return OrganizationMapper::class;
        }
        public function markerColumn(): string
        {
            return 'name';
        }
        public function row(int $index, SeedContext $context): array
        {
            return ['name' => $this->text('organizacion', $index), 'folder' => $this->text('organizacion', $index), 'createdBy' => 1];
        }
    };
    $missing = $error(fn () => SeedPlanner::simulate([$withoutNit], 1, $context));
    $check(str_contains($missing, 'no genera campos obligatorios') && preg_match('/:.*\bnit\b/', $missing) === 1, '3f una fila que no cubre un campo obligatorio se para, y nombra el campo', $missing ?: 'no se paró');
    echoTerminal(' ');

    echoTerminal('[4] Sin pedirlo, simula');
    $check(SeedTask::mode('', '') === SeedTask::MODE_SIMULATE, '4a sin escribir=yes ni limpiar=yes, el modo es simular');
    $check(SeedTask::mode('yes', '') === SeedTask::MODE_WRITE && SeedTask::mode('', 'yes') === SeedTask::MODE_CLEAN, '4b escribir=yes escribe y limpiar=yes limpia');
    $check(SeedTask::mode('yes', 'yes') === null, '4c los dos a la vez no son un modo');
    echoTerminal(' ');

    echoTerminal('[5] El limpiado de carpetas se niega fuera de lo sembrado (en un temporal propio)');
    $root = sys_get_temp_dir() . '/zz-seed-planner-' . bin2hex(random_bytes(6));
    $uploads = $root . '/statics/uploads/x';
    $mounted = mkdir($uploads . '/zz-siembra-ok', 0755, true)
        && mkdir($uploads . '/victima', 0755, true)
        && mkdir($root . '/fuera', 0755, true)
        && mkdir($uploads . '/zz-siembra-con-enlace', 0755, true)
        && file_put_contents($uploads . '/zz-siembra-ok/a.png', 'a') !== false
        && file_put_contents($uploads . '/victima/b.png', 'b') !== false
        && file_put_contents($root . '/fuera/c.png', 'c') !== false
        && symlink($root . '/fuera', $uploads . '/zz-siembra-con-enlace/salida')
        //Apunta DENTRO de la base, a la carpeta ajena: solo la guarda de enlaces lo para.
        && symlink($uploads . '/victima', $uploads . '/zz-siembra-enlace');
    $check($mounted, '5· el temporal de la prueba se monta', $root);
    $refused = [
        '5a un nombre sin el prefijo' => ['statics/uploads/x', 'victima'],
        '5b un nombre con «..»' => ['statics/uploads/x', 'zz-siembra-..'],
        '5c un nombre con barra' => ['statics/uploads/x', 'zz-siembra-ok/../victima'],
        '5d una base fuera de statics/uploads/' => ['fuera', 'zz-siembra-ok'],
        '5e una base que sube con «..»' => ['statics/uploads/../../fuera', 'zz-siembra-ok'],
        '5f una carpeta que es un enlace simbólico' => ['statics/uploads/x', 'zz-siembra-enlace'],
        '5g una carpeta con un enlace simbólico dentro' => ['statics/uploads/x', 'zz-siembra-con-enlace'],
    ];
    foreach ($refused as $name => [$base, $value]) {
        $message = $error(fn () => SeedPlanner::ownedFolderPath($base, $value, $root));
        $check(str_starts_with($message, 'PARA'), "{$name} se para", $message ?: 'no se paró');
    }
    $check(is_file($uploads . '/victima/b.png') && is_file($root . '/fuera/c.png') && is_file($uploads . '/zz-siembra-con-enlace/salida/c.png'), '5h tras las negativas no se borró nada');
    $okPath = $error(fn () => SeedPlanner::ownedFolderPath('statics/uploads/x', 'zz-siembra-ok', $root));
    $check($okPath === '', '5i una carpeta sembrada de verdad pasa las guardas', $okPath);
    $check(SeedPlanner::removeFolder(SeedPlanner::ownedFolderPath('statics/uploads/x', 'zz-siembra-ok', $root)) && !file_exists($uploads . '/zz-siembra-ok') && is_file($uploads . '/victima/b.png'), '5j y se borra entera, sin tocar la de al lado');
    //removeFolder() borra los enlaces sin seguirlos: el destino de «salida» está en el mismo temporal.
    $check(SeedPlanner::removeFolder($root), '5k el temporal de la prueba se retira entero', $root);
    echoTerminal(' ');

    echoTerminal('[6] Copiar a subidas tiene las mismas guardas, y simulando no escribe');
    $simulation = new SeedContext();
    $copyRefused = [
        '6a un origen fuera de statics/images/' => ['app/config/database.php', 'statics/uploads/publications', 'zz-siembra-x', 'a.png'],
        '6b una carpeta sin el prefijo' => ['statics/images/generic-image-placeholder.png', 'statics/uploads/publications', 'otra', 'a.png'],
        '6c una subcarpeta con «..»' => ['statics/images/generic-image-placeholder.png', 'statics/uploads/publications', 'zz-siembra-x/..', 'a.png'],
        '6d un nombre con barra' => ['statics/images/generic-image-placeholder.png', 'statics/uploads/publications', 'zz-siembra-x', '../a.png'],
        '6e una base fuera de statics/uploads/' => ['statics/images/generic-image-placeholder.png', 'statics/images', 'zz-siembra-x', 'a.png'],
    ];
    foreach ($copyRefused as $name => [$source, $base, $folder, $file]) {
        $message = $error(fn () => $simulation->copyToUploads($source, $base, $folder, $file, true));
        $check(str_starts_with($message, 'PARA'), "{$name} se para", $message ?: 'no se paró');
    }
    $simulated = $simulation->copyToUploads('statics/images/generic-image-placeholder.png', 'statics/uploads/publications', 'zz-siembra-simulada/attachments', 'a.png', true);
    $check($simulated === 'statics/uploads/publications/zz-siembra-simulada/attachments/a.png' && !file_exists(basepath('statics/uploads/publications/zz-siembra-simulada')), '6f simulando devuelve la ruta de la columna y no crea nada', $simulated);
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El sembrador descubre los seeders, los ordena por sus dependencias, detecta ciclos y dependencias sin seeder, y sin pedirlo solo simula.')->setEffects([CliActions::EFFECT_NONE])->register();
