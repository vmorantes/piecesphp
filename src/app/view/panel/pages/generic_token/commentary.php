<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div class="ui fixed inverted menu">
    <div class="ui container" id="conaner">
        <a href='<?= base_url(); ?>' class="header item">
            <?= get_title(); ?>
        </a>

    </div>
</div>

<br>
<br>
<br>
<br>

<div class="ui raised very padded text container segment">

    <h2 class="ui header">Comentarios</h2>

    <form pcs-generic-handler-js action="<?= $action; ?>" method="<?= $method_action; ?>" class="ui form"
        style="max-width: 600px; margin: 0 auto;">
        <input type="hidden" name="token" value="<?= $token; ?>">

        <div class="field required">
            <label>Correo electrónico</label>
            <input type="email" name="email" required>
        </div>

        <div class="field required">
            <label>Asunto</label>
            <input type="text" name="subject" required>
        </div>

        <div class="field required">
            <label>Comentario</label>
            <textarea name="message" required></textarea>
        </div>

        <div class="field required">
            <label>Requerir respuesta</label>
            <input type="checkbox" name="response" value='yes'>
        </div>

        <div class="field">
            <button type="submit" class="ui button green">Enviar</button>
        </div>

    </form>

</div>
