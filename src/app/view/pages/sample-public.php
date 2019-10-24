<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<br><br>
<br><br>

<div style="position:fixed; top:10px; left: 10px; opacity:0.1; transition:0.5s ease all;" onMouseOver="this.style.opacity=1;" onMouseOut="this.style.opacity=0.1;">
    <strong>Pueba CSS:</strong>
    <br>
    <div class="ui fitted toggle checkbox" toggle-dev-css-mode='body'>
        <input type="checkbox">
        <label></label>
    </div>
</div>

<div class="ui tabular menu">
    <div style="cursor:pointer;" class="item" data-tab="Exif">Exif</div>
    <div style="cursor:pointer;" class="item" data-tab="CropperJS">CropperJS</div>
    <div style="cursor:pointer;" class="item" data-tab="QuillJS">QuillJS</div>
    <div style="cursor:pointer;" class="item" data-tab="Styles">Styles</div>
    <div style="cursor:pointer;" class="item" data-tab="Messages">Messages</div>
    <div style="cursor:pointer;" class="item" data-tab="Blog">Blog</div>
</div>

<div class="ui tab" data-tab="Exif">

    <div class="elements-container centered fit">

        <form action="<?= explode('?', get_current_url())[0]; ?>" method="POST" enctype="multipart/form-data" class="ui form">

            <div class="field required">
                <label>Sube una foto</label>
                <input type="file" name="exif-image">
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Subir</button>
            </div>

        </form>

        <hr>

        <h1>Resultado:</h1>

        <div><?= $exifResult; ?></div>

    </div>

</div>

<div class="ui tab" data-tab="CropperJS">

    <div class="elements-container centered fit">

        <h3>CropperJS</h3>

        <h4>Sin imagen inicial</h4>

        <p><strong>Resultado de los botones en la consola</strong></p>

        <div>
            <div class="ui button green cropper-test-1 change">wasChanged()</div>
            <div class="ui button green cropper-test-1 init">initWithImage()</div>
            <div class="ui button green cropper-test-1 image">hasImage()</div>
            <div class="ui button green cropper-test-1 file">getFile()</div>
            <div class="ui button green cropper-test-1 title">getTitle()</div>
            <div class="ui button green cropper-test-1 crop">crop()</div>
        </div>

        <div class="ui form cropper-adapter without-image">

            <div class="field">
                <label>Imagen</label>
                <input type="file" accept="image/*">
            </div>

            <div class="preview" w="1920">
                <img src="img-gen/1920/1080">
                <button class="ui button blue" type="button" start></button>
            </div>

            <div class="workspace">

                <div class="steps">

                    <div class="step add">

                        <div class="ui header medium centered">Agregar imagen</div>

                        <div class="placeholder">

                            <div class="content">
                                <div>
                                    <i class="upload icon"></i>
                                    <button class="ui button blue" type="button" load-image>Seleccionar imagen</button>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="step edit">

                        <div class="field required">
                            <label>Título de la imagen</label>
                            <input type="text" cropper-title-export>
                        </div>

                        <div class="field">
                            <canvas data-image=''></canvas>
                        </div>

                    </div>

                </div>

                <div class="controls">

                    <p class="note">
                        <em>
                            <small>
                                <span show-crop-dimensions></span>
                            </small>
                        </em>
                        <br>
                        <strong>
                            <small>
                                La imagen se guardará con las dimensiones: <span show-output></span>
                            </small>
                        </strong>
                        <br>
                        <strong>
                            <small>
                                El ancho mínimo es <span min-w-output></span>
                            </small>
                        </strong>
                    </p>

                    <div class="options">

                        <div class="option" data-option="rotate">

                            <div class="icon">
                                <i class="sync alternate icon"></i>
                            </div>
                            <div class="text">Girar</div>

                        </div>

                        <div class="option" data-option="flip">

                            <div class="icon">
                                <i class="exchange alternate icon"></i>
                            </div>
                            <div class="text">Voltear</div>

                        </div>

                        <div class="option" data-option="adjust">

                            <div class="icon">
                                <i class="expand icon"></i>
                            </div>
                            <div class="text">Ajustar</div>

                        </div>

                        <div class="option" load-image>

                            <div class="icon">
                                <i class="image outline icon"></i>
                            </div>
                            <div class="text">Cambiar</div>

                        </div>

                    </div>

                    <div class="sub-options" data-name="rotate">

                        <div class="option" back-options>

                            <div class="icon">
                                <i class="arrow left icon"></i>
                            </div>
                            <div class="text">Atrás</div>

                        </div>

                        <div class="option" action-rotate-left>

                            <div class="icon">
                                <i class="undo icon"></i>
                            </div>
                            <div class="text">Izquierda</div>

                        </div>

                        <div class="option" action-rotate-right>

                            <div class="icon">
                                <i class="redo icon"></i>
                            </div>
                            <div class="text">Derecha</div>

                        </div>

                    </div>

                    <div class="sub-options" data-name="flip">

                        <div class="option" back-options>

                            <div class="icon">
                                <i class="arrow left icon"></i>
                            </div>
                            <div class="text">Atrás</div>

                        </div>

                        <div class="option" action-flip-horizontal>

                            <div class="icon">
                                <i class="arrows alternate horizontal icon"></i>
                            </div>
                            <div class="text">Horizontal</div>

                        </div>

                        <div class="option" action-flip-vertical>

                            <div class="icon">
                                <i class="arrows alternate vertical icon"></i>
                            </div>
                            <div class="text">Vertical</div>

                        </div>

                    </div>

                    <div class="sub-options" data-name="adjust">

                        <div class="option" back-options>

                            <div class="icon">
                                <i class="arrow left icon"></i>
                            </div>
                            <div class="text">Atrás</div>

                        </div>

                        <div class="option" action-move-up>

                            <div class="icon">
                                <i class="arrow alternate circle up outline icon"></i>
                            </div>
                            <div class="text">Arriba</div>

                        </div>

                        <div class="option" action-move-down>

                            <div class="icon">
                                <i class="arrow alternate circle down outline icon"></i>
                            </div>
                            <div class="text">Abajo</div>

                        </div>

                        <div class="option" action-move-left>

                            <div class="icon">
                                <i class="arrow alternate circle left outline icon"></i>
                            </div>
                            <div class="text">Izquierda</div>

                        </div>

                        <div class="option" action-move-right>

                            <div class="icon">
                                <i class="arrow alternate circle right outline icon"></i>
                            </div>
                            <div class="text">Derecha</div>

                        </div>

                        <div class="option" action-zoom-out>

                            <div class="icon">
                                <i class="search minus icon"></i>
                            </div>
                            <div class="text">Alejar</div>

                        </div>

                        <div class="option" action-zoom-in>

                            <div class="icon">
                                <i class="search plus icon"></i>
                            </div>
                            <div class="text">Acercar</div>

                        </div>

                    </div>

                </div>

                <div class="main-buttons">

                    <div class="element">
                        <button class="ui button red" type="button" cancel>Cancelar</button>
                    </div>

                    <div class="element">
                        <button class="ui button green" type="button" save>Guardar imagen</button>
                    </div>

                </div>

            </div>

        </div>

        <h4>Con imagen inicial</h4>

        <p><strong>Resultado de los botones en la consola</strong></p>

        <div>
            <div class="ui button green cropper-test-2 change">wasChanged()</div>
            <div class="ui button green cropper-test-2 init">initWithImage()</div>
            <div class="ui button green cropper-test-2 image">hasImage()</div>
            <div class="ui button green cropper-test-2 file">getFile()</div>
            <div class="ui button green cropper-test-2 title">getTitle()</div>
            <div class="ui button green cropper-test-2 crop">crop()</div>
        </div>

        <div class="ui form cropper-adapter with-image">

            <div class="field">
                <label>Imagen</label>
                <input type="file" accept="image/*">
            </div>

            <div class="preview" w="1920">
                <img src="img-gen/1920/1080">
                <button class="ui button blue" type="button" start></button>
            </div>

            <div class="workspace">

                <div class="steps">

                    <div class="step add">

                        <div class="ui header medium centered">Agregar imagen</div>

                        <div class="placeholder">

                            <div class="content">
                                <div>
                                    <i class="upload icon"></i>
                                    <button class="ui button blue" type="button" load-image>Seleccionar imagen</button>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="step edit">

                        <div class="field required">
                            <label>Título de la imagen</label>
                            <input type="text" cropper-title-export>
                        </div>

                        <div class="field">
                            <canvas data-image="img-gen/1920/1080"></canvas>
                        </div>

                    </div>

                </div>

                <div class="controls">

                    <p class="note">
                        <em>
                            <small>
                                <span show-crop-dimensions></span>
                            </small>
                        </em>
                        <br>
                        <strong>
                            <small>
                                La imagen se guardará con las dimensiones: <span show-output></span>
                            </small>
                        </strong>
                        <br>
                        <strong>
                            <small>
                                El ancho mínimo es <span min-w-output></span>
                            </small>
                        </strong>
                    </p>

                    <div class="options">

                        <div class="option" data-option="rotate">

                            <div class="icon">
                                <i class="sync alternate icon"></i>
                            </div>
                            <div class="text">Girar</div>

                        </div>

                        <div class="option" data-option="flip">

                            <div class="icon">
                                <i class="exchange alternate icon"></i>
                            </div>
                            <div class="text">Voltear</div>

                        </div>

                        <div class="option" data-option="adjust">

                            <div class="icon">
                                <i class="expand icon"></i>
                            </div>
                            <div class="text">Ajustar</div>

                        </div>

                        <div class="option" load-image>

                            <div class="icon">
                                <i class="image outline icon"></i>
                            </div>
                            <div class="text">Cambiar</div>

                        </div>

                    </div>

                    <div class="sub-options" data-name="rotate">

                        <div class="option" back-options>

                            <div class="icon">
                                <i class="arrow left icon"></i>
                            </div>
                            <div class="text">Atrás</div>

                        </div>

                        <div class="option" action-rotate-left>

                            <div class="icon">
                                <i class="undo icon"></i>
                            </div>
                            <div class="text">Izquierda</div>

                        </div>

                        <div class="option" action-rotate-right>

                            <div class="icon">
                                <i class="redo icon"></i>
                            </div>
                            <div class="text">Derecha</div>

                        </div>

                    </div>

                    <div class="sub-options" data-name="flip">

                        <div class="option" back-options>

                            <div class="icon">
                                <i class="arrow left icon"></i>
                            </div>
                            <div class="text">Atrás</div>

                        </div>

                        <div class="option" action-flip-horizontal>

                            <div class="icon">
                                <i class="arrows alternate horizontal icon"></i>
                            </div>
                            <div class="text">Horizontal</div>

                        </div>

                        <div class="option" action-flip-vertical>

                            <div class="icon">
                                <i class="arrows alternate vertical icon"></i>
                            </div>
                            <div class="text">Vertical</div>

                        </div>

                    </div>

                    <div class="sub-options" data-name="adjust">

                        <div class="option" back-options>

                            <div class="icon">
                                <i class="arrow left icon"></i>
                            </div>
                            <div class="text">Atrás</div>

                        </div>

                        <div class="option" action-move-up>

                            <div class="icon">
                                <i class="arrow alternate circle up outline icon"></i>
                            </div>
                            <div class="text">Arriba</div>

                        </div>

                        <div class="option" action-move-down>

                            <div class="icon">
                                <i class="arrow alternate circle down outline icon"></i>
                            </div>
                            <div class="text">Abajo</div>

                        </div>

                        <div class="option" action-move-left>

                            <div class="icon">
                                <i class="arrow alternate circle left outline icon"></i>
                            </div>
                            <div class="text">Izquierda</div>

                        </div>

                        <div class="option" action-move-right>

                            <div class="icon">
                                <i class="arrow alternate circle right outline icon"></i>
                            </div>
                            <div class="text">Derecha</div>

                        </div>

                        <div class="option" action-zoom-out>

                            <div class="icon">
                                <i class="search minus icon"></i>
                            </div>
                            <div class="text">Alejar</div>

                        </div>

                        <div class="option" action-zoom-in>

                            <div class="icon">
                                <i class="search plus icon"></i>
                            </div>
                            <div class="text">Acercar</div>

                        </div>

                    </div>

                </div>

                <div class="main-buttons">

                    <div class="element">
                        <button class="ui button red" type="button" cancel>Cancelar</button>
                    </div>

                    <div class="element">
                        <button class="ui button green" type="button" save>Guardar imagen</button>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<div class="ui tab" data-tab="QuillJS">

    <div class="elements-container centered fit">

        <h3>QuillJS</h3>

        <div class="text-align-c">
            <div quill-adapter-component>
            </div>
            <textarea target></textarea>
        </div>

    </div>

</div>

<div class="ui tab" data-tab="Styles">

    <div class="elements-container centered fit">

        <h3>Colores</h3>

        <br>

        <div class="elements-container small centered" style="display:flex; flex-wrap: wrap;">
            <div style="padding:5px;background-color:rgb(2, 61, 86);width: 150px;height: 150px;color:white;">
                <strong>Primero</strong>
                <br>
                <strong>rgb(2, 61, 86)</strong>
            </div>
            <div style="padding:5px;background-color:rgb(40, 100, 133);width: 150px;height: 150px;color:white;">
                <strong>Segundo</strong>
                <br>
                <strong>rgb(40, 100, 133)</strong>
            </div>
            <div style="padding:5px;background-color:rgb(150, 150, 150);width: 150px;height: 150px;color:white;">
                <strong>Tercero</strong>
                <br>
                <strong>rgb(150, 150, 150)</strong>
            </div>
            <div style="padding:5px;background-color:rgb(70, 70, 70);width: 150px;height: 150px;color:white;">
                <strong>Cuarto</strong>
                <br>
                <strong>rgb(70, 70, 70)</strong>
            </div>
            <div style="padding:5px;background-color:rgb(30, 30, 30);width: 150px;height: 150px;color:white;">
                <strong>Quinto</strong>
                <br>
                <strong>rgb(30, 30, 30)</strong>
            </div>
            <div style="padding:5px;background-color:white;width: 150px;height: 150px;color:black;">
                <strong>Color para sobrponer sobre el Primero</strong>
                <br>
                <strong>white</strong>
            </div>
            <div style="padding:5px;background-color:#1e1e1e;width: 150px;height: 150px;color:white;">
                <strong>H1</strong>
                <br>
                <strong>#1e1e1e</strong>
            </div>
            <div style="padding:5px;background-color:#286485;width: 150px;height: 150px;color:white;">
                <strong>H2</strong>
                <br>
                <strong>#286485</strong>
            </div>
            <div style="padding:5px;background-color:#464646;width: 150px;height: 150px;color:white;">
                <strong>H3...H6</strong>
                <br>
                <strong>#464646</strong>
            </div>
        </div>

        <hr>

        <h1>H1 lorem ipsum dolor sit amet consectetur.</h1>

        <h2>H2 lorem ipsum dolor sit amet consectetur.</h2>

        <h3>H3 lorem ipsum dolor sit amet consectetur.</h3>

        <h4>H4 lorem ipsum dolor sit amet consectetur.</h4>

        <h5>H5 lorem ipsum dolor sit amet consectetur.</h5>

        <h6>H6 lorem ipsum dolor sit amet consectetur.</h6>

        <hr>

        <p class="text-align-c">P - Lorem, ipsum dolor sit amet consectetur adipisicing elit. Error aliquid unde iste ab
            id,
            pariatur sed neque fuga obcaecati eos. Magni eligendi libero odit debitis ratione dicta numquam accusamus
            ullam,
            quaerat aspernatur nobis placeat, ducimus harum fugiat voluptatum quibusdam porro natus, beatae alias iure
            commodi officia vel itaque.</p>

        <p class="text-align-l">P - Lorem, ipsum dolor sit amet consectetur adipisicing elit. Error aliquid unde iste ab
            id,
            pariatur sed neque fuga obcaecati eos. Magni eligendi libero odit debitis ratione dicta numquam accusamus
            ullam,
            quaerat aspernatur nobis placeat, ducimus harum fugiat voluptatum quibusdam porro natus, beatae alias iure
            commodi officia vel itaque.</p>

        <p class="text-align-r">P - Lorem, ipsum dolor sit amet consectetur adipisicing elit. Error aliquid unde iste ab
            id,
            pariatur sed neque fuga obcaecati eos. Magni eligendi libero odit debitis ratione dicta numquam accusamus
            ullam,
            quaerat aspernatur nobis placeat, ducimus harum fugiat voluptatum quibusdam porro natus, beatae alias iure
            commodi officia vel itaque.</p>

        <p class="text-align-j">P - Lorem, ipsum dolor sit amet consectetur adipisicing elit. Error aliquid unde iste ab
            id,
            pariatur sed neque fuga obcaecati eos. Magni eligendi libero odit debitis ratione dicta numquam accusamus
            ullam,
            quaerat aspernatur nobis placeat, ducimus harum fugiat voluptatum quibusdam porro natus, beatae alias iure
            commodi officia vel itaque.</p>

        <hr>

        <ul class="pcs-list">

            <li>UL - Lorem ipsum dolor sit amet consectetur adipisicing elit. Accusamus ipsa nobis numquam excepturi,
                tenetur iusto deserunt vitae sit ratione praesentium laboriosam natus sint iure illo cupiditate veniam
                aperiam. Nesciunt, ad.</li>

            <li>Odit nemo, fugit modi beatae at, adipisci ad nihil possimus labore quia repellat maiores, praesentium
                cupiditate hic illum unde ratione magni. Blanditiis, eum. Atque aperiam incidunt doloribus doloremque.
                Perspiciatis, doloremque.</li>

            <li>Assumenda incidunt, unde laborum porro error cupiditate quidem corporis nam consectetur blanditiis quas?
                Maiores, ullam expedita! Recusandae voluptates fugit odio! Suscipit molestias voluptates enim nam quo
                error
                laborum ratione dolor?</li>

            <li>Unde ullam esse iusto aperiam exercitationem amet sapiente impedit nisi alias similique animi facere
                sunt
                excepturi, aut pariatur porro. Exercitationem, dicta iure. Natus quis placeat magni ea animi nisi
                reiciendis!</li>

            <li>Molestiae repudiandae ut ullam aliquam nobis officia dolores est ab ipsa voluptates asperiores amet,
                eaque
                praesentium ducimus natus. Repellat nihil et totam ducimus est quae hic ex optio esse illo!</li>

            <li>Id architecto quia maiores quo nulla iusto doloremque, iste numquam, ipsum rerum assumenda sapiente
                culpa
                sint error, temporibus odio quibusdam sequi quae. Vitae ad dolores aperiam facere placeat, quidem optio.
            </li>

        </ul>

        <ol class="pcs-list">

            <li>OL - Lorem ipsum dolor sit amet consectetur adipisicing elit. Velit repudiandae nobis minima voluptates
                eveniet. Nesciunt blanditiis culpa ipsum cum aut esse exercitationem, atque eius, omnis ea ex quaerat
                eveniet minus?</li>

            <li>Aliquam placeat adipisci alias soluta suscipit excepturi velit sapiente repudiandae sequi eveniet
                commodi,
                quod facere explicabo quis esse officia ipsa. Molestias voluptates doloribus dolores pariatur illo
                officia
                numquam fuga dignissimos?</li>

            <li>Perferendis laboriosam totam placeat enim. Nemo, neque distinctio? Molestias dolore maxime sunt
                voluptatem
                dolorem. Praesentium nulla omnis quod quasi dolorem suscipit quia aliquid reprehenderit blanditiis,
                consectetur at aliquam ut molestias!</li>

            <li>Accusamus error nostrum assumenda, eligendi, quod est rerum saepe nulla dignissimos eos tempore.
                Perferendis
                doloremque dicta quasi repudiandae iste vero porro placeat excepturi optio quos. Accusamus placeat et
                quis
                cumque.</li>

            <li>Rem distinctio dolorum nesciunt animi magni officia, ipsum laborum eum quis quam alias porro, aperiam
                eos
                expedita perferendis error quas hic. Obcaecati cum consequatur ab recusandae, ea iusto. Quasi,
                obcaecati!
            </li>

            <li>Deserunt explicabo velit fugiat ullam vero ad, veniam magni sequi minus doloremque facere qui pariatur
                autem. Sint aliquid doloremque deserunt ratione asperiores harum fugiat ad, mollitia voluptas corporis
                exercitationem ea.</li>

        </ol>

        <hr>

        <div class="image">
            <img src="img-gen/1920/1080" title="Imagen dentro de un div.image">
        </div>

        <p>P - Lorem, ipsum dolor sit amet consectetur adipisicing elit. Error aliquid unde iste ab id, pariatur sed
            neque
            fuga obcaecati eos. Magni eligendi libero odit debitis ratione dicta numquam accusamus ullam. Ipsam, nemo
            exercitationem numquam reprehenderit harum velit dignissimos sint veritatis fugiat architecto earum optio
            corrupti perferendis cupiditate atque labore error.</p>

        <img class='block' src="img-gen/400/300" title="Imagen suelta con clase .block">

        <p>P - Lorem, ipsum dolor sit amet consectetur adipisicing elit. Error aliquid unde iste ab id, pariatur sed
            neque
            fuga obcaecati eos. Magni eligendi libero odit debitis ratione dicta numquam accusamus ullam. Ipsam, nemo
            exercitationem numquam reprehenderit harum velit dignissimos sint veritatis fugiat architecto earum optio
            corrupti perferendis cupiditate atque labore error.</p>

        <hr>

        <blockquote>BLOCKQUOTE - Lorem ipsum dolor sit amet consectetur adipisicing elit. Eos nemo, dolorem aut
            architecto
            libero aliquid. Quo impedit itaque totam nobis facilis voluptatem architecto porro aliquam non fugiat.
            Quidem,
            soluta voluptate.</blockquote>

        <hr>

        <img class='block block-centered' src="img-gen/200/100" title="Imagen suelta con clase .block.block-centered">

        <br>

        <div class="text-align-l">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-l">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-l">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-l">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-l">
        </div>

        <div class="text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
        </div>

        <div class="text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
        </div>

        <div class="text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
        </div>

        <div class="text-align-j">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-j">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-j">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-j">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-j">
        </div>

        <div class="text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-c">
        </div>

        <div class="text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
            <img src="img-gen/100/100" title="Imagen dentro de un div con clase .text-align-r">
        </div>

    </div>

</div>

<div class="ui tab" data-tab="Messages">

    <div class="elements-container centered fit">

        <div class="text-align-c">
            <button class="ui button green" onClick="successMessage('Título', 'Mensaje', e => console.log('successMessage'))">
                successMessage (iziToast o alert si no está importado)
            </button>
            <button class="ui button yellow" onClick="warningMessage('Título', 'Mensaje', e => console.log('warningMessage'))">
                warningMessage (iziToast o alert si no está importado)
            </button>
            <br>
            <br>
            <button class="ui button blue" onClick="infoMessage('Título', 'Mensaje', e => console.log('infoMessage'))">
                infoMessage (iziToast o alert si no está importado)
            </button>
            <button class="ui button red" onClick="errorMessage('Título', 'Mensaje', e => console.log('successMessage'))">
                errorMessage (iziToast o alert si no está importado)
            </button>
            <br>
            <br>
        </div>

    </div>

</div>

<div class="ui tab" data-tab="Blog">

    <div class="elements-container centered fit">

        <h3>Blog</h3>

        <ul>
            <li>
                <a href="<?= \PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic::routeName('list')?>">
                    Artículos
                </a>
            </li>
            <li>
                <a href="<?= \PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic::routeName('list-categories')?>">
                    Categorías
                </a>
            </li>
        </ul>

    </div>

</div>
