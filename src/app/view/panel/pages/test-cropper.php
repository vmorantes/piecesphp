<?php
    $withImage = false;
?>
<form class="ui form" test-cropper>
    <div class="ui form cropper-adapter" cropper-image-main data-edit="<?= $withImage ? 'yes' : 'no'; ?>">

        <div class="field required">
            <label>Test</label>
            <input type="file" accept="image/*">
        </div>

        <?php cropperAdapterWorkSpace([
            'referenceW' => '400',
            'referenceH' => '300',
            'image' => $withImage ? 'statics/images/logo.png' : '',
        ]); ?>

    </div>
    <br>
    <div class="field">
        <button type="submit" class="ui button green">Probar</button>
    </div>
</form>
<script>
let loader = document.createElement('div')
loader.innerHTML = "<div></div>"
const loaderName = 'test-cropper-loader'
loader.querySelector('div').outerHTML = `<div class="ui-pcs-global-loader active" data-name="${loaderName}"><div class="ui-pcs-box"><div class="ui-pcs-loader"></div></div></div>`
document.body.appendChild(loader)

window.addEventListener('load', function() {

    let formSelector = "[test-cropper]";
    let form = $(formSelector)
    let imagenMain = new CropperAdapterComponent({
        containerSelector: '[cropper-image-main]',
        minWidth: 400,
        outputWidth: 400,
        cropperOptions: {
            aspectRatio: 4 / 3,
        },
    })

    form.on('submit', function(e) {
        e.preventDefault()
        const isEdit = form.data('edit') === 'yes'
        let dataImage = null
        if (isEdit) {
            dataImage = imagenMain.getFile(null, null, null, null, true)
        } else {
            dataImage = imagenMain.getFile()
        }
        console.log(dataImage)
    })

    removeGenericLoader(loaderName)

})
</script>
