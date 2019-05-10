<?php

use App\Controller\MessagesController as Messages;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

?>

<h3 class="ui dividing header">
    <?=__('general', 'messages');?>
	<span class="ui mini button green" refresh-messages><i class="icon sync alternate"></i>Verificar mensajes</span>
</h3>
<section class="ui text container">
    <div messages-component-container>
        <div send-form>

            <?php if (Messages::isAllowed($this->user->type, Messages::COMPONENT_EXTERNAL_EDITOR)): ?>
            <div toggle-form>
                <div>
                    <i class="plus icon"></i>
                </div>
                <div class="text">
                    Enviar nuevo mensaje
                </div>
            </div>
            <form messages-component-external-editor action="<?=get_route('messages-send-message');?>" method="POST"
                class="ui form">
                <p><strong>Redactar mensaje</strong></p>

                <input type="hidden" name="from" value="<?=$this->user->id;?>">

                <div class="field required">
                    <label>Asunto</label>
                    <input type="text" name="subject" required>
                </div>

                <div class="field required">
                    <label>Mensaje</label>
                    <input type="text" name="message" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green">Enviar</button>
                </div>

            </form>
            <br>
            <?php elseif (Messages::isAllowed($this->user->type, Messages::COMPONENT_EXTERNAL_EDITOR_WITH_SELECT_DESTINE)): ?>
            <div toggle-form>
                <div>
                    <i class="plus icon"></i>
                </div>
                <div class="text">
				Enviar nuevo mensaje
                </div>
            </div>
            <form messages-component-external-editor action="<?=get_route('messages-send-message');?>" method="POST"
                class="ui form">
                <p><strong>Redactar mensaje</strong></p>

                <input type="hidden" name="from" value="<?=$this->user->id;?>">

                <div class="field required">
                    <label>Para</label>
                    <select name="to" class="ui dropdown search">
                        <option value="">Seleccionar destinatario</option>
                        <?php foreach ($destinatarios as $destinatario): ?>
                        <option value="<?=$destinatario->id;?>"><?="$destinatario->firstname $destinatario->first_lastname";?>
                        </option>
                        <?php endforeach;?>
                    </select>
                </div>

                <div class="field required">
                    <label>Asunto</label>
                    <input type="text" name="subject" required>
                </div>

                <div class="field required">
                    <label>Mensaje</label>
                    <input type="text" name="message" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green">Enviar</button>
                </div>

            </form>
            <br>
            <?php endif;?>
        </div>
        <div messages-component user="<?=$this->user->id;?>"
            route="<?=get_route('messages-inbox', ['user_id' => $this->user->id])?>">
            <div inbox>
                <div content></div>
            </div>
            <div pagination></div>
        </div>

    </div>

</section>

<script type='text/html' messages-component-templates>
<div message>

    <div preview>
        <span subject>{{subject}}</span>
		<span date>{{date}}</span>
		<span bubble-unread><i class="icon comment outline"></i></span>
    </div>

    <div content>

        <div message-detail>
            <span author>{{message_from_name}}</span>
            <span subject>{{subject}}</span>
            <span date>{{date}}</span>
            <p text class="text-reading">{{message}}</p>
        </div>

        <div dialog>
            {{messages}}
        </div>

        <form reply class="ui reply form" action="<?=get_route('messages-send-response');?>" method="POST">
            <input type="hidden" name="message_id" value="{{message_id}}">
            <input type="hidden" name="message_from" value="{{message_from}}">
            <div class="field">
                <div class="field required">
                    <label>Escriba su respuesta</label>
                    <textarea required name="message"></textarea>
                </div>
                <div class="field">
                    <button class="ui blue labeled submit icon button" type="submit">
                        Enviar <i class="icon edit"></i>
                    </button>
                </div>
            </div>
        </form>

    </div>

</div>

<div sub-message>
    <div data>
        <div avatar data-title="{{message_from_name}}" data-content="{{date}}">{{avatar}}</div>
    </div>
    <div text class="text-reading">{{message}}</div>
</div>
</script>
