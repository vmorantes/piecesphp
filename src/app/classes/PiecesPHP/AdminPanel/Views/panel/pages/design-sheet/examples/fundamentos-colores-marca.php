<?php
use PiecesPHP\CSSVariables;

//Las variables CSS globales salen de la configuración (src/app/config/containers.php): el color se cambia allí, nunca en
//una hoja de estilos.
$variables = CSSVariables::instance('global')->getVariables();
?>
<div class="ui stackable grid">
    <div class="six wide column">
        <button class="ui fluid button brand-color" type="button">--main-brand-color</button>
        <br>
        <button class="ui fluid button brand-color alt" type="button">--second-brand-color</button>
        <br>
        <button class="ui fluid button brand-color alt2" type="button">--font-color-one</button>
    </div>
    <div class="ten wide column">
        <table class="ui very basic compact table">
            <tbody>
                <?php foreach ($variables as $name => $value): ?>
                <tr>
                    <td class="collapsing"><code><?= escape_html($name); ?></code></td>
                    <td><code><?= escape_html($value); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
