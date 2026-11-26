/* Fomantic (fork): textos de los módulos en español neutro, con tuteo (D99). Todas las claves de base.json (examples/catalogo/i18n.mjs
   avisa de las que falten). Se carga tras semantic.js y fomantic-i18n.js. */
FomanticI18n.add('es', {
    "calendar": {
        "text": {
            "days": [
                "D",
                "L",
                "M",
                "X",
                "J",
                "V",
                "S"
            ],
            "dayNamesShort": [
                "dom",
                "lun",
                "mar",
                "mié",
                "jue",
                "vie",
                "sáb"
            ],
            "dayNames": [
                "domingo",
                "lunes",
                "martes",
                "miércoles",
                "jueves",
                "viernes",
                "sábado"
            ],
            "months": [
                "enero",
                "febrero",
                "marzo",
                "abril",
                "mayo",
                "junio",
                "julio",
                "agosto",
                "septiembre",
                "octubre",
                "noviembre",
                "diciembre"
            ],
            "monthsShort": [
                "ene",
                "feb",
                "mar",
                "abr",
                "may",
                "jun",
                "jul",
                "ago",
                "sep",
                "oct",
                "nov",
                "dic"
            ],
            "today": "Hoy",
            "now": "Ahora",
            "am": "a. m.",
            "pm": "p. m.",
            "weekNo": "Semana"
        }
    },
    "chat": {
        "text": {
            "title": "Asistente",
            "conversation": "Conversación",
            "close": "Cerrar",
            "newChat": "Nueva conversación",
            "placeholder": "Escribe tu pregunta",
            "send": "Enviar",
            "thinking": "Pensando…",
            "empty": "Pregúntame lo que necesites sobre la plataforma.",
            "notices": {
                "scope": "Eso queda fuera de lo que puedo hacer.",
                "unavailable": "El asistente no está disponible ahora.",
                "error": "Algo ha fallado. Vuelve a intentarlo."
            }
        }
    },
    "colorpicker": {
        "text": {
            "panel": "Selector de color",
            "trigger": "Elegir color",
            "area": "Saturación y brillo",
            "areaRole": "deslizador 2D",
            "areaValue": "Saturación {saturation} %, brillo {brightness} %, {value}",
            "hue": "Tono",
            "hueValue": "{hue} grados, {value}",
            "alpha": "Opacidad",
            "alphaValue": "{alpha} %, {value}",
            "input": "Valor del color",
            "initial": "Color anterior: {value}. Púlsalo para recuperarlo",
            "current": "Color nuevo: {value}",
            "format": "Formato del color",
            "formats": {
                "hex": "HEX",
                "rgb": "RGB",
                "hsl": "HSL",
                "oklch": "OKLCH"
            },
            "channels": "Canales del color",
            "red": "R",
            "green": "G",
            "blue": "B",
            "hueChannel": "H",
            "saturation": "S",
            "lightness": "L",
            "palette": "Paleta",
            "swatch": "Usar {value}",
            "recents": "Recientes",
            "analogous": "Análogos",
            "triad": "Tríada",
            "complementary": "Complementario",
            "contrast": "Contraste",
            "contrastValue": "{ratio}:1",
            "contrastPass": "Cumple {threshold}:1",
            "contrastFail": "Por debajo de {threshold}:1",
            "eyedropper": "Tomar un color de la pantalla",
            "clear": "Sin color",
            "more": "Más colores",
            "less": "Menos colores",
            "accept": "Aceptar",
            "cancel": "Cancelar"
        }
    },
    "dropdown": {
        "message": {
            "addResult": "Agregar <b>{term}</b>",
            "count": "{count} seleccionados",
            "maxSelections": "Máximo {maxCount} selecciones",
            "noResults": "No se encontraron resultados.",
            "serverError": "Hubo un error al comunicarse con el servidor"
        }
    },
    "flyout": {
        "text": {
            "ok": "Aceptar",
            "cancel": "Cancelar",
            "close": "Cerrar"
        }
    },
    "form": {
        "text": {
            "and": "y",
            "unspecifiedRule": "Ingresa un valor válido",
            "unspecifiedField": "Este campo",
            "leavingMessage": "Hay cambios sin guardar en esta página que se perderán si continúas."
        },
        "prompt": {
            "range": "{name} debe estar entre {min} y {max}",
            "maxValue": "{name} debe ser como máximo {ruleValue}",
            "minValue": "{name} debe ser como mínimo {ruleValue}",
            "empty": "{name} es obligatorio",
            "notEmpty": "{name} es obligatorio",
            "checked": "{name} debe estar marcado",
            "email": "{name} debe ser un correo electrónico válido",
            "url": "{name} debe ser una URL válida",
            "regExp": "{name} no tiene el formato correcto",
            "integer": "{name} debe ser un número entero",
            "decimal": "{name} debe ser un número decimal",
            "number": "{name} debe ser un número",
            "is": "{name} debe ser \"{ruleValue}\"",
            "isExactly": "{name} debe ser exactamente \"{ruleValue}\"",
            "not": "{name} no puede ser \"{ruleValue}\"",
            "notExactly": "{name} no puede ser exactamente \"{ruleValue}\"",
            "contains": "{name} debe contener \"{ruleValue}\"",
            "containsExactly": "{name} debe contener exactamente \"{ruleValue}\"",
            "doesntContain": "{name} no puede contener \"{ruleValue}\"",
            "doesntContainExactly": "{name} no puede contener exactamente \"{ruleValue}\"",
            "minLength": "{name} debe tener al menos {ruleValue} caracteres",
            "exactLength": "{name} debe tener exactamente {ruleValue} caracteres",
            "maxLength": "{name} no puede tener más de {ruleValue} caracteres",
            "size": "{name} debe tener entre {min} y {max} caracteres",
            "match": "{name} debe coincidir con el campo {ruleValue}",
            "different": "{name} debe ser distinto del campo {ruleValue}",
            "creditCard": "{name} debe ser un número de tarjeta de crédito válido",
            "minCount": "{name} debe tener al menos {ruleValue} opciones",
            "exactCount": "{name} debe tener exactamente {ruleValue} opciones",
            "maxCount": "{name} debe tener como máximo {ruleValue} opciones",
            "addErrors": "{name}: {error}"
        }
    },
    "loadbar": {
        "text": {
            "label": "Cargando"
        }
    },
    "modal": {
        "text": {
            "ok": "Aceptar",
            "cancel": "Cancelar",
            "close": "Cerrar"
        }
    },
    "pin": {
        "text": {
            "label": "Código de verificación, {length} caracteres"
        }
    },
    "progress": {
        "text": {
            "percent": "{percent} %",
            "ratio": "{value} de {total}",
            "bars": [
                ""
            ]
        }
    },
    "search": {
        "error": {
            "noResults": "La búsqueda no devolvió resultados",
            "serverError": "Hubo un problema al consultar el servidor."
        }
    },
    "shell": {
        "text": {
            "collapse": "Contraer menú",
            "expand": "Expandir menú",
            "openMenu": "Abrir menú",
            "closeMenu": "Cerrar menú"
        }
    },
    "slider": {
        "text": {
            "rangeStart": "Mínimo",
            "rangeEnd": "Máximo"
        }
    },
    "slideshow": {
        "text": {
            "label": "Carrusel",
            "roleDescription": "carrusel",
            "slideRoleDescription": "diapositiva",
            "slide": "{index} de {total}",
            "indicator": "Ir a la diapositiva {index}",
            "previous": "Diapositiva anterior",
            "next": "Diapositiva siguiente",
            "play": "Reanudar rotación",
            "pause": "Detener rotación"
        }
    },
    "toast": {
        "text": {
            "close": "Cerrar"
        }
    }
});
