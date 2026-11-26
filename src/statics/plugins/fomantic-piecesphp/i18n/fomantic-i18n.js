/*!
 * Fomantic (fork): paquetes de idioma de los módulos (D99, D155).
 *
 * Carga: semantic.js, este archivo y los paquetes que se quieran (en.js, es.js, fr.js), antes de iniciar los módulos.
 *   <script src="semantic.min.js"></script>
 *   <script src="i18n/fomantic-i18n.js"></script>
 *   <script src="i18n/es.js"></script>
 * El paquete cuyo idioma coincide con el `lang` de <html> (es-CO cuenta como es) se aplica solo al cargarse. Para cambiar
 * de idioma: FomanticI18n.use('fr'). Un paquete solo cambia los ajustes por defecto ($.fn.<módulo>.settings): afecta a los
 * módulos que se inicien después; un módulo ya iniciado conserva sus textos hasta que se vuelva a iniciar. Lo que se pase
 * al iniciar un módulo (`$('.ui.calendar').calendar({ text: … })`) sigue mandando.
 */
(function ($, window, document) {
    'use strict';

    var packs = {};
    var current = null;
    var wanted = function () {
        return (document.documentElement.getAttribute('lang') || '').toLowerCase();
    };
    var matches = function (lang, target) {
        return target === lang || target.split('-')[0] === lang;
    };
    var api = {
        // Registra un paquete; si es el idioma de la página y aún no se ha elegido otro, lo aplica
        add: function (lang, pack) {
            packs[lang.toLowerCase()] = pack;
            if (current === null && wanted() && matches(lang.toLowerCase(), wanted())) {
                api.use(lang);
            }

            return api;
        },
        // Aplica un paquete registrado (con o sin región: es-CO usa es si no hay es-CO)
        use: function (lang) {
            lang = String(lang || '').toLowerCase();
            var pack = packs[lang] || packs[lang.split('-')[0]];
            if (!pack) {
                return false;
            }
            $.each(pack, function (module, values) {
                if ($.fn[module] && $.fn[module].settings) {
                    $.extend(true, $.fn[module].settings, values);
                }
            });
            current = lang;

            return true;
        },
        lang: function () {
            return current;
        },
        available: function () {
            return Object.keys(packs);
        },
    };
    window.FomanticI18n = api;
})(jQuery, window, document);
