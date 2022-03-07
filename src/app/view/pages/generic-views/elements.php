<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<section class="body">

    <div class="content">

        <div class="wrapper medium-size ">

            <h1 class="segment-title text-center"><?= __(LANG_GROUP, 'Elements'); ?></h1>

        </div>

    </div>

    <div class="content">

        <div class="wrapper medium-size ">

            <h2 class="text-center">Tabs</h2>

            <div data-tab-menu="tabs-1" class="tabs-menu-items">
                <div data-tab-active="yes" data-tab-target="tab-1">Tab 1</div>
                <div data-tab-active="no" data-tab-target="tab-2">Tab 2</div>
                <div data-tab-active="no" data-tab-target="tab-3">Tab 3</div>
            </div>

        </div>

    </div>

    <div data-tab-content="tabs-1" class="content">

        <div data-tab-name="tab-1" class="wrapper medium-size no-padding-top">
            <h3>#1 Lorem ipsum dolor sit amet.</h3>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
        </div>
        <div data-tab-name="tab-2" class="wrapper medium-size">
            <h3>#2 Lorem ipsum dolor sit amet.</h3>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
        </div>
        <div data-tab-name="tab-3" class="wrapper medium-size">
            <h3>#3 Lorem ipsum dolor sit amet.</h3>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Aperiam, suscipit provident, ab, quisquam fugit error sunt deserunt architecto blanditiis ipsum ut! Libero hic commodi distinctio obcaecati nisi numquam rem laboriosam?</p>
        </div>

    </div>

    <div class="content">

        <div class="wrapper medium-size ">

            <h2 class="text-center"><?= __(LANG_GROUP, 'Título de sección'); ?></h2>
            <div class="segment-title">Lorem, ipsum dolor. (Normal)</div>
            <div class="segment-title text-center">Lorem, ipsum dolor. (Centrado)</div>
            <div class="segment-title text-right">Lorem, ipsum dolor. (A la derecha)</div>
            <div class="segment-title medium">Lorem, ipsum dolor. (medium)</div>
        </div>

    </div>

    <div class="content">

        <div class="wrapper medium-size ">

            <h2 class="text-center"><?= __(LANG_GROUP, 'Listas'); ?></h2>

            <h3 class="text-center"><?= __(LANG_GROUP, 'Ordenadas'); ?></h3>

            <ol class="custom-list">
                <li>Lorem, ipsum dolor.</li>
                <li>Provident, doloremque. Fugiat!</li>
                <ol class="custom-list">
                    <li>Lorem, ipsum dolor.</li>
                    <li>Provident, doloremque. Fugiat!</li>
                    <li>Vitae, tempora adipisci?</li>
                    <li>Ut, inventore magni?</li>
                </ol>
                <li>Ut, inventore magni?</li>
            </ol>

            <h3 class="text-center"><?= __(LANG_GROUP, 'Desordenadas'); ?></h3>

            <ul class="custom-list">
                <li>Lorem, ipsum dolor.</li>
                <li>Provident, doloremque. Fugiat!</li>
                <ul class="custom-list">
                    <li>Lorem, ipsum dolor.</li>
                    <li>Provident, doloremque. Fugiat!</li>
                    <li>Vitae, tempora adipisci?</li>
                    <li>Ut, inventore magni?</li>
                </ul>
                <li>Ut, inventore magni?</li>
            </ul>

        </div>

    </div>

</section>
