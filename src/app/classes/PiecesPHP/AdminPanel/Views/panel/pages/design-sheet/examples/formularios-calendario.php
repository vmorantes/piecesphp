<!-- `calendar-js` lo inicia el panel al cargar (configurations.js, configCalendars()); `calendar-type`: date, datetime, month o time. -->
<div class="ui form">
    <div class="field required" calendar-js calendar-type="date">
        <label><?= __($langGroup, 'Fecha'); ?></label>
        <input type="text" name="publicDate" required autocomplete="off">
    </div>
</div>
