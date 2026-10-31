<?php
use PiecesPHP\CSSVariables;

//Los tokens que escribe la instalación salen de la configuración (src/app/config/containers.php): el color se cambia allí,
//nunca en una hoja de estilos.
$variables = CSSVariables::instance('global')->getVariables();
?>
<div class="ui stackable grid">
    <div class="six wide column">
        <?php //Cada acento lo enseña su botón; sus valores, en la tabla. ?>
        <button class="ui fluid primary button" type="button">--accent</button>
        <div class="ui hidden divider"></div>
        <button class="ui fluid secondary button" type="button">--accent-2</button>
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
