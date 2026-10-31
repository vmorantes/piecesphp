<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Errores». TODO lo que viene del registro se escapa: el mensaje de una excepción puede llevar texto que
 * tecleó cualquiera.
 *
 * @var string $langGroup
 * @var string $tabKey
 * @var string $tabURL Lleva ya ?tab=
 * @var array{text:string,type:string,day:string,level:string} $filters
 * @var string $reference
 * @var string|null $file Uno de $plainFiles, o null para el registro de errores
 * @var string[] $plainFiles
 * @var string[] $types Los tipos que hay en el registro leído
 * @var string[] $levels ErrorLogReader::LEVELS
 * @var int $plainLines
 * @var int $pageSize
 * @var string|null $plainContent Con $file
 * @var \PiecesPHP\Logs\Controllers\HelperController $pieces Pinta las piezas del panel
 * @var array{day:string,entry:array<string,mixed>}|null $found Con $reference
 * @var array{entries:array<int,array{day:string,entry:array<string,mixed>}>,page:int,hasMore:bool}|null $result Sin $file ni $reference
 */
//Pintada fuera del controlador (la suite la incluye con sus propias variables): las piezas, con lo que haya.
$pieces = isset($pieces) && $pieces instanceof \PiecesPHP\Logs\Controllers\HelperController ? $pieces : new \PiecesPHP\Logs\Controllers\HelperController();
$plainContent = $plainContent ?? null;
$found = $found ?? null;
$result = $result ?? null;
$text = fn($value): string => is_scalar($value) ? (string) $value : '';
$json = fn($value): string => (string) json_encode($value, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE);
$linkTo = fn(array $query): string => $tabURL . '&' . http_build_query(array_filter($query, fn($v) => $v !== '' && $v !== null));
$fileLabels = [
    'deprecations' => __($langGroup, 'Deprecaciones (deprecations.log)'),
    'expired-sessions' => __($langGroup, 'Sesiones caducadas (expired-sessions.log)'),
];
//Rutas relativas a src/: la absoluta es ruido en la fila y va entera, copiable, al final del detalle.
$srcRoot = rtrim(str_replace('\\', '/', dirname(rtrim(basepath(''), '/\\'))), '/') . '/src/';
$relative = fn(string $path): string => str_starts_with(str_replace('\\', '/', $path), $srcRoot) ? substr(str_replace('\\', '/', $path), strlen($srcRoot)) : $path;
$shortClass = function (string $class): string {
    $position = strrpos($class, '\\');
    return $position === false ? $class : substr($class, $position + 1);
};
$levelLabels = [
    'grave' => __($langGroup, 'Grave'),
    'soft' => __($langGroup, 'Suave'),
];
//Los marcos del enrutador y de vendor/ se pliegan en una línea (ADR 0060 §6): se ven, pero no estorban.
$isFramework = function (array $frame): bool {
    $file = str_replace('\\', '/', is_string($frame['file'] ?? null) ? $frame['file'] : '');
    $class = is_string($frame['class'] ?? null) ? $frame['class'] : '';
    return str_contains($file, '/vendor/') || str_starts_with($class, 'Slim\\') || str_starts_with($class, 'PiecesPHP\\Core\\Routing\\');
};
$renderTrace = function (array $trace) use ($langGroup, $text, $relative, $shortClass, $isFramework): void {
    $groups = [];
    $current = null;
    foreach (array_values($trace) as $index => $frame) {
        $frame = is_array($frame) ? $frame : [];
        $folded = $isFramework($frame);
        if ($current === null || $current['folded'] !== $folded) {
            if ($current !== null) {
                $groups[] = $current;
            }
            $current = ['folded' => $folded, 'frames' => []];
        }
        $current['frames'][$index] = $frame;
    }
    if ($current !== null) {
        $groups[] = $current;
    }
    ?>
    <table class="ui very basic compact table logs-trace">
        <?php foreach ($groups as $group): ?>
        <?php $fold = $group['folded'] && count($group['frames']) > 1; ?>
        <?php if ($fold): ?>
        <tbody>
            <tr class="logs-trace-fold">
                <td colspan="3"><a href="#" data-logs-fold><i class="caret right icon"></i><?= sprintf(__($langGroup, '%d marcos de Slim y vendor'), count($group['frames'])); ?></a></td>
            </tr>
        </tbody>
        <?php endif; ?>
        <tbody<?= $fold ? ' data-logs-folded hidden' : ''; ?>>
            <?php foreach ($group['frames'] as $index => $frame): ?>
            <?php
                $function = $text($frame['function'] ?? '');
                    $class = $text($frame['class'] ?? '');
                ?>
            <tr>
                <td class="collapsing"><?= (int) $index; ?></td>
                <td><code><?= escape_html(($class !== '' ? $shortClass($class) . '::' : '') . $function); ?></code></td>
                <td class="logs-error-place"><?= escape_html($relative($text($frame['file'] ?? '')) . ':' . $text($frame['line'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php endforeach; ?>
    </table>
    <?php
        };
        $renderEntry = function (array $item, bool $open) use ($langGroup, $text, $json, $relative, $shortClass, $levelLabels, $renderTrace): void {
            $entry = $item['entry'];
            $message = $text($entry['message']);
            $shortMessage = mb_strlen($message) > 200 ? rtrim(mb_substr($message, 0, 200)) . '…' : $message;
            $type = $text($entry['type']);
            $code = $text($entry['code']);
            $level = $text($entry['level'] ?? 'grave');
            $count = (int) ($entry['count'] ?? 1);
            $lastAt = (int) ($entry['lastAt'] ?? 0);
            $extraData = $entry['extraData'];
            $hasExtraData = $extraData !== [] && $extraData !== null && $extraData !== '' && $extraData !== (object) [];
            $trace = is_array($entry['trace']) ? $entry['trace'] : [];
            $previous = is_array($entry['previous'] ?? null) ? $entry['previous'] : [];
            $tracePurged = ($entry['tracePurged'] ?? false) === true;
            $context = is_array($entry['context'] ?? null) ? array_filter($entry['context'], fn($value) => $value !== '' && $value !== null) : [];
            $contextLabels = [
                'method' => __($langGroup, 'Método'),
                'route' => __($langGroup, 'Ruta'),
                'url' => __($langGroup, 'Dirección'),
                'userID' => __($langGroup, 'Usuario (id)'),
            ];
        ?>
    <div class="<?= $open ? 'active ' : ''; ?>title logs-error-title" data-error-reference="<?= escape_html($text($entry['reference'])); ?>" data-error-level="<?= escape_html($level); ?>">
        <i class="dropdown icon"></i>
        <div class="logs-error-summary">
            <div class="logs-error-head">
                <span class="logs-error-time"><?= escape_html(mb_substr($text($entry['time']), 0, 8)); ?></span>
                <span data-error-level-label><?= \PiecesPHP\Core\Utilities\Helpers\StatusLabel::html($levelLabels[$level] ?? $level, $level === 'soft' ? \PiecesPHP\Core\Utilities\Helpers\StatusLabel::WARNING : \PiecesPHP\Core\Utilities\Helpers\StatusLabel::NEGATIVE); ?></span>
                <span class="ui small basic label" data-logs-copy="<?= escape_html($text($entry['reference'])); ?>" title="<?= escape_html(__($langGroup, 'Copiar la referencia')); ?>"><i class="copy outline icon"></i><?= escape_html($text($entry['reference'])); ?></span>
                <strong title="<?= escape_html($type); ?>"><?= escape_html($shortClass($type)); ?></strong>
                <?php if ($level === 'soft' && $count > 1 && $lastAt > 0): ?>
                <span class="logs-error-place" data-error-count="<?= $count; ?>"><?= sprintf(__($langGroup, '%d veces, la última a las %s'), $count, date('H:i', $lastAt)); ?></span>
                <?php endif; ?>
            </div>
            <div class="logs-error-message" data-error-message><?= escape_html($shortMessage); ?></div>
            <div class="logs-error-place"><?= escape_html($relative($text($entry['file'])) . ':' . $text($entry['line'])); ?></div>
        </div>
    </div>
    <div class="<?= $open ? 'active ' : ''; ?>content logs-error-detail">
        <?php if ($code !== '' && $code !== '0' && $code !== '-'): ?>
        <p><strong><?= __($langGroup, 'Código'); ?>:</strong> <?= escape_html($code); ?></p>
        <?php endif; ?>
        <?php if ($context !== []): ?>
        <p><strong><?= __($langGroup, 'Petición'); ?>:</strong></p>
        <table class="ui very basic compact table logs-context" data-error-context>
            <tbody>
                <?php foreach ($context as $key => $value): ?>
                <tr>
                    <td class="collapsing"><?= escape_html($contextLabels[$key] ?? (string) $key); ?></td>
                    <td class="logs-error-place"><?= escape_html($text($value)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php if ($hasExtraData): ?>
        <p><strong><?= __($langGroup, 'Datos adicionales'); ?>:</strong></p>
        <pre class="logs-pre"><?= escape_html($json($extraData)); ?></pre>
        <?php endif; ?>
        <?php if ($trace !== []): ?>
        <p><strong><?= __($langGroup, 'Traza'); ?>:</strong></p>
        <?php $renderTrace($trace); ?>
        <?php elseif ($tracePurged): ?>
        <p><strong><?= __($langGroup, 'Traza'); ?>:</strong></p>
        <p class="ui grey text" data-error-trace-purged><?= __($langGroup, 'La traza de este error ya se purgó.'); ?></p>
        <?php endif; ?>
        <?php if ($previous !== []): ?>
        <p><strong><?= __($langGroup, 'Excepciones previas'); ?>:</strong></p>
        <?php foreach ($previous as $number => $prior): ?>
        <div class="ui segment" data-error-previous>
            <p>
                <strong title="<?= escape_html($text($prior['type'] ?? '')); ?>"><?= ((int) $number + 1) . '. ' . escape_html($shortClass($text($prior['type'] ?? ''))); ?></strong>
                <span class="logs-error-place"><?= escape_html($relative($text($prior['file'] ?? '')) . ':' . $text($prior['line'] ?? '')); ?></span>
            </p>
            <pre class="logs-pre"><?= escape_html($text($prior['message'] ?? '')); ?></pre>
            <?php if (is_array($prior['trace'] ?? null) && $prior['trace'] !== []): ?>
            <?php $renderTrace($prior['trace']); ?>
            <?php elseif (($prior['tracePurged'] ?? false) === true): ?>
            <p class="ui grey text" data-error-trace-purged><?= __($langGroup, 'La traza de este error ya se purgó.'); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <p><strong><?= __($langGroup, 'Mensaje completo y ruta'); ?>:</strong></p>
        <pre class="logs-pre"><?= escape_html($message . "\n\n" . $text($entry['file']) . ':' . $text($entry['line'])); ?></pre>
    </div>
    <?php
        };
    ?>
<form class="ui form segment" method="GET" action="<?= escape_html($tabURL); ?>" data-logs-errors-filters>
    <input type="hidden" name="tab" value="<?= escape_html($tabKey); ?>">
    <div class="fields">
        <div class="three wide field">
            <label><?= __($langGroup, 'Referencia'); ?></label>
            <div class="ui input">
                <input type="text" name="ref" value="<?= escape_html($reference); ?>" placeholder="ERR-AAAAMMDD-XXXXXX">
            </div>
        </div>
        <div class="three wide field">
            <label><?= __($langGroup, 'Texto (mensaje o archivo)'); ?></label>
            <div class="ui input">
                <input type="text" name="text" value="<?= escape_html($filters['text']); ?>">
            </div>
        </div>
        <div class="three wide field">
            <label><?= __($langGroup, 'Tipo de excepción'); ?></label>
            <select name="type" class="ui search dropdown" data-logs-errors-type>
                <option value=""><?= __($langGroup, 'Todos'); ?></option>
                <?php foreach ($types as $type): ?>
                <option value="<?= escape_html($type); ?>"<?= $filters['type'] === $type ? ' selected' : ''; ?>><?= escape_html($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="two wide field">
            <label><?= __($langGroup, 'Nivel'); ?></label>
            <select name="level" class="ui dropdown" data-logs-errors-level>
                <option value=""><?= __($langGroup, 'Todos'); ?></option>
                <?php foreach ($levels as $level): ?>
                <option value="<?= escape_html($level); ?>"<?= $filters['level'] === $level ? ' selected' : ''; ?>><?= escape_html($levelLabels[$level] ?? $level); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php //El calendario del panel (configurations.js, configCalendars()) da DD-MM-AAAA. ?>
        <div class="two wide field" calendar-js calendar-type="date">
            <label><?= __($langGroup, 'Día'); ?></label>
            <div class="ui input left icon">
                <i class="calendar icon"></i>
                <input type="text" name="day" value="<?= escape_html($filters['day']); ?>" autocomplete="off">
            </div>
        </div>
        <div class="three wide field">
            <label><?= __($langGroup, 'Otros archivos'); ?></label>
            <select name="file" class="ui dropdown">
                <option value=""><?= __($langGroup, 'Registro de errores'); ?></option>
                <?php foreach ($plainFiles as $plainFile): ?>
                <option value="<?= escape_html($plainFile); ?>"<?= $file === $plainFile ? ' selected' : ''; ?>><?= escape_html($fileLabels[$plainFile] ?? $plainFile); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php $pieces->_render('panel/built-in/content/filter-actions.php', ['clearUrl' => $tabURL]); ?>
</form>

<?php if ($file !== null): ?>

<p><?= sprintf(__($langGroup, 'Las últimas %d líneas de %s.'), $plainLines, escape_html($fileLabels[$file] ?? $file)); ?></p>
<?php if ($plainContent === null || $plainContent === ''): ?>
<div class="ui message"><?= __($langGroup, 'El archivo no existe o está vacío.'); ?></div>
<?php else: ?>
<pre class="logs-pre" data-logs-plain><?= escape_html($plainContent); ?></pre>
<?php endif; ?>

<?php elseif ($reference !== ''): ?>

<?php if ($found === null): ?>
<div class="ui warning message" data-logs-reference-missing><?= __($langGroup, 'No hay ningún error con esa referencia'); ?></div>
<?php else: ?>
<div class="ui styled fluid accordion" data-logs-errors>
    <?php $renderEntry($found, true); ?>
</div>
<?php endif; ?>

<?php elseif ($result !== null): ?>

<?php if ($result['entries'] === []): ?>
<div class="ui message"><?= __($langGroup, 'No hay errores que mostrar con estos filtros.'); ?></div>
<?php else: ?>
<?php
$byDay = [];
foreach ($result['entries'] as $item) {
    $byDay[$item['day']][] = $item;
}
?>
<?php foreach ($byDay as $day => $items): ?>
<h4 class="ui header"><?= escape_html($text($day)); ?></h4>
<div class="ui styled fluid accordion" data-logs-errors>
    <?php foreach ($items as $item): ?>
    <?php $renderEntry($item, false); ?>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="ui basic segment">
    <?php if ($result['page'] > 1): ?>
    <a class="ui basic button" href="<?= escape_html($linkTo($filters + ['page' => (string) ($result['page'] - 1)])); ?>"><i class="angle left icon"></i><?= __($langGroup, 'Más recientes'); ?></a>
    <?php endif; ?>
    <span><?= sprintf(__($langGroup, 'Página %d (%d por página)'), $result['page'], $pageSize); ?></span>
    <?php if ($result['hasMore']): ?>
    <a class="ui basic button" href="<?= escape_html($linkTo($filters + ['page' => (string) ($result['page'] + 1)])); ?>"><?= __($langGroup, 'Más antiguos'); ?><i class="angle right icon"></i></a>
    <?php endif; ?>
</div>

<?php endif; ?>
