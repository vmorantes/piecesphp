<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!-- Main -->
<div id="main">
    <div class="inner">

        <!-- Header -->
        <?php if(isset($withSocialBar) && $withSocialBar === true):?>
        <?php $this->render('layout/template-inc/social-bar', isset($socialBarData) ? $socialBarData : []); ?>
        <?php endif;?>

        <!-- Content -->
        <section class="vm-content-generic-views">

            <header class="main">
                <h1><?= __(LANG_GROUP, 'Ejemplo de tabs'); ?></h1>
            </header>

            <span class="image main">
                <img loading="lazy" src="<?= baseurl('statics/images/generic-views/tabs-sample.jpg'); ?>" />
            </span>

            <hr class="major" />

            <div data-tab-menu="tab-sample">
                <ul class="actions">
                    <li>
                        <button data-tab-active="yes" data-tab-target="tab-1" class="button margin-top">Tab 1</button>
                        <button data-tab-active="no" data-tab-target="tab-2" class="button margin-top">Tab 2 con subtabs</button>
                    </li>
                </ul>
            </div>

            <div data-tab-content="tab-sample">

                <div data-tab-name="tab-1">

                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Et debitis, sint aspernatur molestiae aut repellat laborum facere ducimus, illum iusto accusantium quidem, neque recusandae minima. Velit voluptatibus eum dignissimos nesciunt.</p>

                </div>

                <div data-tab-name="tab-2">

                    <div data-tab-menu="tab-2-1">

                        <ul class="actions">
                            <li>
                                <button data-tab-active="yes" data-tab-target="tab-2-1-1" class="button margin-top">Tab 1</button>
                                <button data-tab-active="yes" data-tab-target="tab-2-1-2" class="button margin-top">Tab 2</button>
                            </li>
                        </ul>

                    </div>

                    <div data-tab-content="tab-2-1">

                        <div data-tab-name="tab-2-1-1">

                            <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Accusamus nemo magnam quaerat alias amet qui, quam praesentium esse hic sed eligendi cupiditate dolore molestias? Voluptatum, nobis est. Voluptatum, illo reiciendis!</p>

                        </div>

                        <div data-tab-name="tab-2-1-2">

                            <ul>
                                <li>Lorem ipsum dolor sit amet consectetur adipisicing elit. Impedit voluptatum dignissimos corrupti! Iure minus perspiciatis fugiat, quo magnam voluptatum totam quod at reiciendis fuga, alias inventore voluptas eveniet sint. Iusto?</li>
                                <li>Lorem ipsum dolor sit amet consectetur adipisicing elit. Impedit voluptatum dignissimos corrupti! Iure minus perspiciatis fugiat, quo magnam voluptatum totam quod at reiciendis fuga, alias inventore voluptas eveniet sint. Iusto?</li>
                                <li>Lorem ipsum dolor sit amet consectetur adipisicing elit. Impedit voluptatum dignissimos corrupti! Iure minus perspiciatis fugiat, quo magnam voluptatum totam quod at reiciendis fuga, alias inventore voluptas eveniet sint. Iusto?</li>
                            </ul>

                        </div>

                    </div>

                </div>

            </div>

        </section>
