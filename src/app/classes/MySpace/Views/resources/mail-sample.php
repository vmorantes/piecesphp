<?php
$this->helpController->render('mailing/template_base', [
    'header_image' => base_url(get_config('mailing_logo')),
    'text' => "Razón del correo de ejemplo",
    'code' => generate_code(6, true),
    'note' => "Mensaje informativo del correo electrónico.",
    'url' => $refererURL !== null ? $refererURL : '#',
    'text_button' => 'Llamado a la acción',
    'text_footer' => 'Texto del footer - ' . get_config('owner'),
]);


$text = <<<EOF
<p>
  Estimado/a <strong>Lorem ipsum dolor sit amet</strong>,
</p>
<p>
  Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
</p>
<p>
  Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
</p>
<p>
  Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus pretium purus a tortor varius, non feugiat lorem ullamcorper. Sed auctor nulla at neque pharetra, vitae aliquam turpis varius.
</p>
<p>
  Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
</p>
<p>
  <em>Aliquam erat volutpat. Nulla facilisi.</em>
</p>
<p>
  <a href="#">Lorem ipsum dolor sit amet</a>
</p>
EOF;

$footerTemplate = "<span class='owner'>{TITLE}</span> <p>{TEXT}</p>";
$this->helpController->render('mailing/template_base', [
    'header_image' => base_url(get_config('mailing_logo')),
    'text' => $text,
    'url' => $refererURL !== null ? $refererURL : '#',
    'text_button' => 'Llamado a la acción',
    'text_footer' => strReplaceTemplate($footerTemplate, [
        '{TITLE}' => get_config('owner'),
        '{TEXT}' => "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium.",
    ]),
]);
