<?php
use App\Controller\MessagesController as Messages;
use App\Controller\MessagesController;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = MessagesController::LANG_GROUP;
?>

<section class="ui text message-component-message-container">

    <div messages-component-container>

        <div messages-component>
            <div aside-menu>
                <div send-form>

                    <?php if (Messages::isAllowed($this->user->type, Messages::COMPONENT_EXTERNAL_EDITOR)): ?>

                    <div toggle-form>
                        <div>
                            <i class="plus icon"></i>
                        </div>
                        <div class="text">
                            <?= __($langGroup, 'Enviar nuevo mensaje'); ?>
                        </div>
                    </div>

                    <div class="ui modal message-component-new-message-modal">

                        <form messages-component-external-editor action="<?=get_route('messages-send-message');?>"
                            method="POST" class="ui form">
                            <p><strong><?= __($langGroup, 'Redactar mensaje'); ?></strong></p>

                            <input type="hidden" name="from" value="<?=$this->user->id;?>">

                            <div class="field required">
                                <label><?= __($langGroup, 'Asunto'); ?></label>
                                <input type="text" name="subject" required>
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Mensaje'); ?></label>
                                <input type="text" name="message" required>
                            </div>

                            <div class="field">
                                <button type="submit" class="ui button green"><?= __($langGroup, 'Enviar'); ?></button>
                            </div>

                        </form>

                    </div>

                    <br>

                    <?php elseif (Messages::isAllowed($this->user->type, Messages::COMPONENT_EXTERNAL_EDITOR_WITH_SELECT_DESTINE)): ?>

                    <div toggle-form>
                        <div>
                            <i class="plus icon"></i>
                        </div>
                        <div class="text">
                            <?= __($langGroup, 'Enviar nuevo mensaje'); ?>
                        </div>
                    </div>

                    <div class="ui modal message-component-new-message-modal">

                        <form messages-component-external-editor action="<?=get_route('messages-send-message');?>"
                            method="POST" class="ui form">
                            <p><strong><?= __($langGroup, 'Redactar mensaje'); ?></strong></p>

							<input type="hidden" name="from" value="<?=$this->user->id;?>">
							
                            <div class="field required">
                                <label><?= __($langGroup, 'Para'); ?></label>
                                <select name="to" class="ui dropdown search">
                                    <option value=""><?= __($langGroup, 'Seleccionar destinatario'); ?></option>
                                    <?php foreach ($destinatarios as $destinatario): ?>
                                    <option value="<?=$destinatario->id;?>">
                                        <?="$destinatario->firstname $destinatario->first_lastname";?>
                                    </option>
                                    <?php endforeach;?>
                                </select>
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Asunto'); ?></label>
                                <input type="text" name="subject" required>
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Mensaje'); ?></label>
                                <textarea name="message" cols="30" rows="10" required></textarea>
                            </div>

                            <div class="field">
                                <button type="submit" class="ui button green"><?= __($langGroup, 'Enviar'); ?></button>
                            </div>

                        </form>

                    </div>
                    <br>

                    <?php endif;?>

                </div>

                <div previews>
                </div>

                <button message-component-load-more><?= __($langGroup, 'Cargar más'); ?></button>

            </div>

            <div conversations></div>

        </div>
    </div>
</section>

<template messenger>

    <div send-route="<?=get_route('messages-send-message');?>"></div>
    <div response-route="<?=get_route('messages-send-response');?>"></div>
    <div load-route="<?=get_route('messages-inbox', ['user_id' => $this->user->id])?>"></div>
    <div user-id="<?=$this->user->id?>"></div>

    <div preview>

        <div class="image-container">
            <img avatar src="<?=base_url('statics/images/default-avatar.png')?>" alt="avatar">
        </div>
        <div class="text-container">
            <span date></span>
            <span name></span>
        </div>

    </div>

    <div main-message-body>

        <div class="details-container">
            <div class="top-bar">
                <button message-component-close-conversation class="ui mini button red"><?= __($langGroup, 'Cerrar'); ?></button>
            </div>
            <div emisor-details-container>
                <div>
                    <img avatar>
                </div>
                <div class="text-container">
                    <div>
                        <div name></div>
                        <div course></div>
                        <div date></div>
                    </div>
                </div>
            </div>
        </div>

        <div messages-container>
            <h2 subject></h2>
            <div text></div>
        </div>

        <div conversation></div>

    </div>

    <div sub-message>
        <img avatar>
        <p text></p>
    </div>

    <form class="ui reply form" response-message-form>
        <input type="hidden" name="message_id">
        <input type="hidden" name="message_from">
        <div class="field">
            <div class="field required">
                <textarea name="message" placeholder="<?= __($langGroup, 'Escriba su respuesta'); ?>"></textarea>
            </div>
            <div class="field">
                <button class="ui blue button" type="submit"><?= __($langGroup, 'Enviar'); ?>
            </div>
        </div>
    </form>

</template>
