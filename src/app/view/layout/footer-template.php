<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Controller\ContactFormsController;
?>

<?php if(!isset($withContactFormGlobal) || $withContactFormGlobal === true): ?>

<section data-smooth-target-id="contact-form">

    <h2><?= __(LANG_GROUP, 'Contacto'); ?></h2>

    <form method="POST" action="<?= ContactFormsController::routeName('general'); ?>" global-contact-form>

        <div class="row gtr-uniform" style="max-width: 800px;">

            <input type="hidden" name="from" value="<?= get_current_url(); ?>">

            <div class="col-12">
                <label class="required"><?= __(LANG_GROUP, 'Nombre'); ?></label>
                <input required type="text" name="name" value="">
            </div>

            <div class="col-12">
                <label class="required"><?= __(LANG_GROUP, 'Correo electrónico'); ?></label>
                <input required type="email" name="email" value="">
            </div>

            <div class="col-12">
                <input type="checkbox" id="updates-checkbox" name="updates" value="yes">
                <label for="updates-checkbox"><?= __(LANG_GROUP, 'Marque si desea recibir actualizaciones a su correo electrónico'); ?></label>
            </div>

            <div class="col-12">
                <label class="required"><?= __(LANG_GROUP, 'Asunto'); ?></label>
                <input required type="text" name="subject" value="">
            </div>

            <!-- Break -->
            <div class="col-12">
                <label class="required"><?= __(LANG_GROUP, 'Mensaje'); ?></label>
                <textarea required name="message"></textarea>
            </div>

            <!-- Break -->
            <div class="col-12">

                <ul class="actions">

                    <li>
                        <input type="submit" value="<?= __(LANG_GROUP, 'Enviar'); ?>" class="primary">
                    </li>

                    <li>
                        <input type="reset" value="<?= __(LANG_GROUP, 'Limpiar'); ?>">
                    </li>

                </ul>

            </div>

        </div>

    </form>

</section>

<?php endif;?>

</div>
</div>

<?php $this->render('layout/template-inc/sidebar'); ?>

</div>

<!-- Scripts -->
<?php load_js(['base_url' => "", 'custom_url' => ""]) ?>
<?php if(!isset($withContactFormGlobal) || $withContactFormGlobal === true): ?>
<script src="<?= baseurl('statics/js/contact/contact-form.js'); ?>"></script>
<?php endif;?>
</body>

</html>
