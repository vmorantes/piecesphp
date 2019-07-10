<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!DOCTYPE html>
<html lang="<?=get_config('app_lang');?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?=baseurl();?>">
    <title><?=get_title();?></title>
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""])?>
</head>

<body>

    <section class="container">

        <div class="topbar">
            <div class="text">
                Desbloquear <br> mi usuario
            </div>
            <div class="back">
                <a href="<?=get_route('user-problems-list')?>">
                    Volver atrás
                </a>
            </div>
        </div>

        <div class="header one">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/user-block.png'); ?>">
                </div>
                <div class="text">Paso 2</div>
            </div>
        </div>

        <div class="header two">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/code-mail.png'); ?>">
                </div>
                <div class="text">Paso 3</div>
            </div>
        </div>

        <div class="header three">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/wrong-mail.png'); ?>">
                </div>
                <div class="text">Correo no registrado</div>
            </div>
        </div>

        <div class="header four">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/wrong-code.png'); ?>">
                </div>
                <div class="text">Código incorrecto</div>
            </div>
        </div>

        <div class="header five">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/user-unblock.png'); ?>">
                </div>
                <div class="text">Usuario desbloqueado</div>
            </div>
        </div>

        <div class="form-container">

            <div message class="message"></div>

            <div recovery>

                <form class="ui form">
                    <div class="field required">
                        <input type="email" name="username" placeholder="Ingrese su correo electrónico">
                    </div>
                    <div class="field">
                        <button type="submit" class="ui button green fluid">Siguiente</button>
                    </div>
                    <p>
                        <strong>
                            <a href="#" class="ui mini button blue" has-code>Ya tengo un código</a>
                        </strong>
                    </p>
                </form>

            </div>

            <div code>

                <form class="ui form">
                    <div class="field required">
                        <input required type="text" name="code" placeholder="######">
                    </div>
                    <div class="field">
                        <button type="submit" class="ui button green fluid">Enviar</button>
                    </div>
                    <p>
                        <strong>
                            <a href="#" class="ui mini button blue" repeat>Introducir un correo diferente</a>
                        </strong>
                    </p>
                </form>

            </div>

            <div error>

                <form class="ui form">
                    <div class="two fields">
                        <div class="field">
                            <a href="<?= get_route('user-not-exists-form'); ?>" class="ui button green">Solicitud de
                                soporte</a>
                        </div>
                        <div class="field">
                            <a href="#" class="ui button green" repeat>Nuevo correo</a>
                        </div>
                    </div>
                </form>

            </div>

            <div finish>

                <form class="ui form">
                    <div class="field">
                        <a href="<?= get_route('login-form'); ?>" class="ui button green fuid">Ingresar</a>
                    </div>
                </form>

            </div>

        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""])?>
</body>

</html>