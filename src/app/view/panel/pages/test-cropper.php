<?php
$withImage = true;
?>
<form class="ui form" test-cropper>
    <div class="ui form cropper-adapter" cropper-image-main data-edit="<?=$withImage ? 'yes' : 'no';?>">

        <div class="field required">
            <label>Test</label>
            <input type="file" accept="image/*">
        </div>

        <?php cropperAdapterWorkSpace([
            'referenceW' => '400',
            'referenceH' => '300',
            'image' => $withImage ? 'statics/images/logo.png' : '',
        ]);?>

    </div>
    <br>
    <div class="field">
        <button type="submit" class="ui button green">Probar</button>
    </div>
</form>

<?php simpleCropperAdapterWorkSpace([
    'selectorAttr' => 'simple-cropper',
    'referenceW' => '400',
    'referenceH' => '300',
    'image' => $withImage ? 'statics/images/logo.png' : '',
]);?>

<div class="ui modal cropper-test">
    <div class="content">
        <?php simpleCropperAdapterWorkSpace([
            'selectorAttr' => 'simple-cropper-modal',
            'referenceW' => '400',
            'referenceH' => '300',
            'image' => $withImage ? 'img-gen/1920/1080' : '',
        ]);?>
    </div>
</div>

<button class="ui button red" open-modal>ABRIR MODAL</button>

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

    const simpleCropperAdapter = new SimpleCropperAdapter(null, {
        aspectRatio: 1 / 1,
        format: 'image/jpeg',
        quality: 0.8,
        fillColor: 'red',
        outputWidth: '350',
    })

    simpleCropperAdapter.onCropped(function(blob, url) {
        console.log('onCropped')
        console.log(blob)
        console.log(url)
    })

    setInterval(function() {
        if (simpleCropperAdapter.wasChange()) {
            console.log('Interval wasChange')
            //Obtener archivo
            console.log(simpleCropperAdapter.getFile())
        }
    }, 1000)

    //EJEMPLO MODAL INICIO
    let firstDrawCropper = true
    const modalCropper = $('.ui.modal.cropper-test').modal({
        onVisible: function(){
            if(firstDrawCropper){
                simpleCropperAdapterModal.refresh()
                firstDrawCropper = false
            }
        },
    })
    const simpleCropperAdapterModal = new SimpleCropperAdapter('[simple-cropper-modal]', {
        aspectRatio: 1 / 1,
        format: 'image/jpeg',
        quality: 1,
        fillColor: 'red',
        outputWidth: '350',
    })
    simpleCropperAdapterModal.onCropped(function(blob, url) {
        console.log(blob)
        console.log(url)
    })
    simpleCropperAdapterModal.onCancel(function() {
        modalCropper.modal('hide')
    })
    $('[open-modal]').click(function(){
        $('.ui.modal.cropper-test').modal('show')
    })
    //EJEMPLO MODAL FIN

    removeGenericLoader(loaderName)

})
</script>
