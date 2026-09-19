<?php
    $textAreaPlaceHolder = $this->lang('Ask Gemini');
    $previousQuestion = Adminer\h($_POST["gemini"]);
?>

<p>
    <hr>
    <br>
    <textarea name='gemini' rows='5' cols='50' placeholder='<?= $textAreaPlaceHolder ?>'><?= $previousQuestion ?></textarea>
    <br>
    <input type='button' value='Gemini'>
    <br><br>
    <hr>
</p>

<script <?= Adminer\nonce(); ?>>
const geminiText = qsl('textarea');
const geminiButton = qsl('input');

function setSqlareaValue(value) {
    const sqlarea = qs('textarea.sqlarea');
    sqlarea.value = value;
    sqlarea.onchange && sqlarea.onchange();
}
geminiButton.onclick = () => {
    if (geminiText.value == '') {
        alert('<?= $this->lang('Please enter a question'); ?>');
        return false
    }
    setSqlareaValue('-- <?= $this->lang('Just a sec...'); ?>');
    ajax(
        '',
        req => setSqlareaValue(req.responseText),
        'gemini=' + encodeURIComponent(geminiText.value)
    );
};
geminiText.onfocus = event => {
    alterClass(findDefaultSubmit(geminiText), 'default');
    alterClass(geminiButton, 'default', true);
    event.stopImmediatePropagation();
};
geminiText.onblur = () => {
    alterClass(geminiButton, 'default');
};
geminiText.onkeydown = event => {
    if (isCtrl(event) && (event.keyCode == 13 || event.keyCode == 10)) {
        geminiButton.onclick();
        event.stopPropagation();
    }
};
</script>