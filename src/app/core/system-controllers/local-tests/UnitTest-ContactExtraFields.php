<?php

//Los campos de más del formulario de contacto (un teléfono que la acción no lee) entran en el cuerpo del correo.
//Sin envío: prueba la selección y la plantilla, no la ruta (la ruta manda correo real).
//@areas nucleo

use App\Controller\ContactFormsController;
use PiecesPHP\MailCatalog\Definitions\ContactFormMail;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/contact-extra-fields', function ($args) {

    echoTerminal("\e[33m[TEST:ContactExtraFields] Los campos de más del formulario de contacto no se pierden\e[39m");
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
    $json = fn($value): string => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    $labels = fn(array $fields): array => array_column($fields, 'label');

    $expected = ['from', 'name', 'email', 'subject', 'message', 'updates', 'tokenCaptcha'];
    $base = [
        'from' => 'https://zz-prueba.test/contacto',
        'name' => 'zz-agente-nombre',
        'email' => 'zz-prueba-a@mailinator.com',
        'subject' => 'zz asunto',
        'message' => 'zz mensaje',
        'updates' => 'yes',
        'tokenCaptcha' => 'zz-token',
    ];

    echoTerminal('[1/4] Qué campos entran');
    $fields = ContactFormsController::extraFields($base + [
        'phone' => ' 300 123 4567 ',
        'companyName' => 'ZZ S.A.',
        'preferred_time' => 'tarde',
        'interests' => ['web', 'correo'],
    ], $expected);
    $check($labels($fields) === ['Phone', 'Company Name', 'Preferred time', 'Interests'], 'a1 entran los de más, con la etiqueta sacada del nombre y en su orden', $json($fields));
    $check(($fields[0]['value'] ?? null) === '300 123 4567', 'a2 el valor llega recortado de espacios', $json($fields[0] ?? null));
    $check(($fields[3]['value'] ?? null) === 'web, correo', 'a3 una lista de casillas se une con comas', $json($fields[3] ?? null));
    $check(ContactFormsController::extraFields($base, $expected) === [], 'a4 sin campos de más, nada: los esperados no se duplican');
    $check(ContactFormsController::extraFields(['NAME' => 'x', 'Message' => 'y'], $expected) === [], 'a5 un esperado con otras mayúsculas tampoco entra');
    echoTerminal(' ');

    echoTerminal('[2/4] Qué campos se excluyen siempre');
    $excluded = [
        'g-recaptcha-response' => 'x', 'h-captcha-response' => 'x', 'cf-turnstile-response' => 'x', 'csrf_token' => 'x',
        'XSRF-TOKEN' => 'x', 'nonce' => 'x', 'password' => 'x', 'Passwd2' => 'x', 'apiToken' => 'x',
        '_method' => 'x', '1phone' => 'x', 'tel%' => 'x', 'a b' => 'x', str_repeat('n', 65) => 'x',
        'nested' => ['a' => 'x'], 'deep' => [['x']], 'object' => null, 'empty' => '   ', 'onlyControl' => "\x00\x01",
    ];
    $fields = ContactFormsController::extraFields($excluded + ['zzKept' => 'sí'], $expected);
    $check($labels($fields) === ['Zz Kept'], 'b1 protección, nombres sin forma segura, anidados, nulos y vacíos no entran', $json($fields));
    $fields = ContactFormsController::extraFields([0 => 'numérica', 'ok' => 'v'], $expected);
    $check($labels($fields) === ['Ok'], 'b2 una clave numérica no entra', $json($fields));
    $fields = ContactFormsController::extraFields(["name\n" => 'x', "phone\n" => 'x', 'ok' => 'v'], $expected);
    $check($labels($fields) === ['Ok'], 'b3 un nombre con salto de línea final («name\\n») no casa con la forma segura', $json($fields));
    $imitations = ['Nombre' => 'x', 'Asunto' => 'x', 'Mensaje' => 'x', 'Email_' => 'x', 'message-' => 'x', 'E-Mail' => 'x', 'correo' => 'x', 'CorreoElectronico' => 'x', 'EXTRA' => 'x', 'from_' => 'x'];
    $fields = ContactFormsController::extraFields($imitations + ['ok' => 'v'], $expected);
    $check($labels($fields) === ['Ok'], 'b4 los que se llaman como una etiqueta del correo o un esperado (sin mayúsculas, acentos ni signos) no entran', $json($fields));
    $protection = ['pwd' => 'x', 'userPwd' => 'x', 'xsrfKey' => 'x', 'clientSecret' => 'x', 'otp' => 'x', 'otp_code' => 'x', 'pin' => 'x', 'pinCode' => 'x', 'pin2' => 'x', 'totp' => 'x', 'mfa' => 'x', 'passcode' => 'x'];
    $fields = ContactFormsController::extraFields($protection + ['ok' => 'v'], $expected);
    $check($labels($fields) === ['Ok'], 'b5 pwd, xsrf, secret, otp, pin, totp, mfa y passcode no entran', $json($fields));
    $lookAlikes = ['opinion' => 'x', 'spinner' => 'x', 'shipping' => 'x', 'footprint' => 'x', 'pinterest' => 'x', 'passenger' => 'x'];
    $fields = ContactFormsController::extraFields($lookAlikes, $expected);
    $check($labels($fields) === ['Opinion', 'Spinner', 'Shipping', 'Footprint', 'Pinterest', 'Passenger'], 'b6 las palabras que solo contienen pin, otp o pass sí entran', $json($fields));
    echoTerminal(' ');

    echoTerminal('[3/4] Los topes');
    $many = [];
    for ($i = 1; $i <= ContactFormsController::EXTRA_FIELDS_LIMIT + 5; $i++) {
        $many["zzField{$i}"] = "v{$i}";
    }
    $fields = ContactFormsController::extraFields($many, $expected);
    $check(count($fields) === ContactFormsController::EXTRA_FIELDS_LIMIT, 'c1 no entran más de ' . ContactFormsController::EXTRA_FIELDS_LIMIT . ' campos', (string) count($fields));
    $check(($fields[ContactFormsController::EXTRA_FIELDS_LIMIT - 1]['value'] ?? null) === 'v' . ContactFormsController::EXTRA_FIELDS_LIMIT, 'c2 entran los primeros, en orden', $json(end($fields)));
    $fields = ContactFormsController::extraFields(['empty1' => '', 'empty2' => ' '] + $many, $expected);
    $check(count($fields) === ContactFormsController::EXTRA_FIELDS_LIMIT && ($fields[0]['label'] ?? null) === 'Zz Field1', 'c3 los descartados no gastan cupo', $json($fields[0] ?? null));
    $long = str_repeat('ñ', ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT + 20);
    $fields = ContactFormsController::extraFields(['comments' => $long, 'exact' => str_repeat('a', ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT)], $expected);
    $check(($fields[0]['value'] ?? null) === str_repeat('ñ', ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT) . '…', 'c4 un valor largo se corta en ' . ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT . ' caracteres (no bytes) y termina en «…»', (string) mb_strlen($fields[0]['value'] ?? ''));
    $check(($fields[1]['value'] ?? null) === str_repeat('a', ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT), 'c5 uno justo en el tope no se corta');
    $fields = ContactFormsController::extraFields(['notes' => "línea uno\r\n\tlínea dos\x00", 'bad' => "zz\xC3"], $expected);
    $check(($fields[0]['value'] ?? null) === 'línea uno línea dos', 'c6 saltos, tabuladores y controles quedan en un espacio', $json($fields[0] ?? null));
    $check(isset($fields[1]['value']) && mb_check_encoding($fields[1]['value'], 'UTF-8'), 'c7 un UTF-8 roto sale como UTF-8 válido', $json($fields[1] ?? null));
    echoTerminal(' ');

    echoTerminal('[4/4] El cuerpo del correo los escapa');
    $mailData = [
        'name' => 'zz-agente-nombre',
        'email' => 'zz-prueba-a@mailinator.com',
        'subject' => 'zz asunto',
        'message' => 'zz mensaje',
        'updates' => false,
        'titleSuffix' => 'ZZ',
        'extraFields' => ContactFormsController::extraFields($base + [
            'phone' => '<script>zzRun(1)</script>',
            'zz-label' => '"comillas" & <b>negrita</b>',
        ], $expected),
    ];
    $body = (new ContactFormMail())->render($mailData);
    $check(!str_contains($body, '<script>zzRun(1)</script>'), 'd1 el <script> no sale vivo');
    $check(str_contains($body, '<strong>Phone:</strong> &lt;script&gt;zzRun(1)&lt;/script&gt;'), 'd2 sale como texto, con su etiqueta');
    $check(str_contains($body, '<strong>Zz label:</strong> &quot;comillas&quot; &amp; &lt;b&gt;negrita&lt;/b&gt;'), 'd3 comillas, & y etiquetas se escapan');
    $check(substr_count($body, 'zz mensaje') === 1 && !str_contains($body, 'Message:</strong>'), 'd4 el mensaje no se repite como campo de más');
    $title = (string) __(LANG_GROUP, 'Extra');
    $titleAt = strpos($body, "<strong>{$title}</strong>");
    $messageAt = strpos($body, 'zz mensaje');
    $check($titleAt !== false && $messageAt !== false && $titleAt > $messageAt && strpos($body, '<strong>Phone:</strong>') > $titleAt && strpos($body, '<strong>Zz label:</strong>') > $titleAt, 'd7 los campos de más van tras el mensaje, bajo su propio título', $json([$messageAt, $titleAt]));
    $bodyMalformed = (new ContactFormMail())->render(['extraFields' => [['label' => 'ok', 'value' => 'zz-bien'], ['label' => ['x'], 'value' => 'zz-mal'], 'zz-suelto']] + $mailData);
    $check(str_contains($bodyMalformed, 'zz-bien') && !str_contains($bodyMalformed, 'zz-mal') && !str_contains($bodyMalformed, 'zz-suelto'), 'd5 una entrada mal formada en extraFields se descarta');
    $bodyWithout = (new ContactFormMail())->render(array_diff_key($mailData, ['extraFields' => true]));
    $check(str_contains($bodyWithout, 'zz mensaje') && !str_contains($bodyWithout, 'Phone:') && !str_contains($bodyWithout, $title), 'd6 sin extraFields el correo es el de siempre, sin el título');
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los campos de más del formulario de contacto entran en el correo, escapados, acotados y sin los de protección.')->setEffects([CliActions::EFFECT_NONE])->register();
