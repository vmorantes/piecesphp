<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<section class="body">

    <div class="content">

        <div class="wrapper">

            <h2 class="segment-title text-center"><?= __(LANG_GROUP, 'Contacto'); ?></h2>

        </div>

        <div class="wrapper small-size">

            <form class="ui form" method="POST" action="<?= $contactURL; ?>" contact-form>

                <input type="hidden" name="from" value="<?= get_current_url(); ?>">

                <div class="field required">
                    <label><?= __(LANG_GROUP, 'Nombre'); ?></label>
                    <input type="text" name="name" placeholder="<?= __(LANG_GROUP, 'Nombre'); ?>..." required>
                </div>


                <div class="field required">

                    <label><?= __(LANG_GROUP, 'Correo electrónico'); ?></label>
                    <input type="text" name="email" placeholder="correo@correo.com" required>

                </div>

                <div class="field required">
                    <label><?= __(LANG_GROUP, 'Asunto'); ?></label>
                    <input required type="text" name="subject" placeholder="<?= __(LANG_GROUP, 'Asunto'); ?>...">
                </div>

                <div class="field required">

                    <label><?= __(LANG_GROUP, 'Mensaje'); ?></label>
                    <textarea name="message" placeholder="<?= __(LANG_GROUP, 'Mensaje'); ?>..." required></textarea>

                </div>

                <div class="field">
                    <div class="ui checkbox">
                        <input type="checkbox" id="updates-checkbox" name="updates" value="yes">
                        <label for="updates-checkbox"><?= __(LANG_GROUP, 'Marque si desea recibir actualizaciones a su correo electrónico'); ?></label>
                    </div>
                </div>

                <div class="field element-center">
                    <button class="ui right labeled icon button fluid" type="submit">
                        <i class="send icon"></i>
                        Enviar
                    </button>
                </div>

            </form>

        </div>

    </div>

</section>
