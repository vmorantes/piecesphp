/* Fomantic (fork): textos de los módulos en inglés (D99). Todas las claves de base.json (examples/catalogo/i18n.mjs
   avisa de las que falten). Se carga tras semantic.js y fomantic-i18n.js. */
FomanticI18n.add('en', {
    "calendar": {
        "text": {
            "days": [
                "S",
                "M",
                "T",
                "W",
                "T",
                "F",
                "S"
            ],
            "dayNamesShort": [
                "Sun",
                "Mon",
                "Tue",
                "Wed",
                "Thu",
                "Fri",
                "Sat"
            ],
            "dayNames": [
                "Sunday",
                "Monday",
                "Tuesday",
                "Wednesday",
                "Thursday",
                "Friday",
                "Saturday"
            ],
            "months": [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December"
            ],
            "monthsShort": [
                "Jan",
                "Feb",
                "Mar",
                "Apr",
                "May",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec"
            ],
            "today": "Today",
            "now": "Now",
            "am": "AM",
            "pm": "PM",
            "weekNo": "Week"
        }
    },
    "chat": {
        "text": {
            "title": "Assistant",
            "conversation": "Conversation",
            "close": "Close",
            "newChat": "New conversation",
            "placeholder": "Type your question",
            "send": "Send",
            "thinking": "Thinking…",
            "empty": "Ask me anything you need about the platform.",
            "notices": {
                "scope": "That's outside what I can do.",
                "unavailable": "The assistant is not available right now.",
                "error": "Something went wrong. Please try again."
            }
        }
    },
    "colorpicker": {
        "text": {
            "panel": "Color picker",
            "trigger": "Choose color",
            "area": "Saturation and brightness",
            "areaRole": "2D slider",
            "areaValue": "Saturation {saturation}%, brightness {brightness}%, {value}",
            "hue": "Hue",
            "hueValue": "{hue} degrees, {value}",
            "alpha": "Opacity",
            "alphaValue": "{alpha}%, {value}",
            "input": "Color value",
            "initial": "Previous color: {value}. Press it to restore it",
            "current": "New color: {value}",
            "format": "Color format",
            "formats": {
                "hex": "HEX",
                "rgb": "RGB",
                "hsl": "HSL",
                "oklch": "OKLCH"
            },
            "channels": "Color channels",
            "red": "R",
            "green": "G",
            "blue": "B",
            "hueChannel": "H",
            "saturation": "S",
            "lightness": "L",
            "palette": "Palette",
            "swatch": "Use {value}",
            "recents": "Recent",
            "analogous": "Analogous",
            "triad": "Triad",
            "complementary": "Complementary",
            "contrast": "Contrast",
            "contrastValue": "{ratio}:1",
            "contrastPass": "Meets {threshold}:1",
            "contrastFail": "Below {threshold}:1",
            "eyedropper": "Pick a color from the screen",
            "clear": "No color",
            "more": "More colors",
            "less": "Fewer colors",
            "accept": "Accept",
            "cancel": "Cancel"
        }
    },
    "dropdown": {
        "message": {
            "addResult": "Add <b>{term}</b>",
            "count": "{count} selected",
            "maxSelections": "Max {maxCount} selections",
            "noResults": "No results found.",
            "serverError": "There was an error contacting the server"
        }
    },
    "flyout": {
        "text": {
            "ok": "Ok",
            "cancel": "Cancel",
            "close": "Close"
        }
    },
    "form": {
        "text": {
            "and": "and",
            "unspecifiedRule": "Please enter a valid value",
            "unspecifiedField": "This field",
            "leavingMessage": "There are unsaved changes on this page which will be discarded if you continue."
        },
        "prompt": {
            "range": "{name} must be in a range from {min} to {max}",
            "maxValue": "{name} must have a maximum value of {ruleValue}",
            "minValue": "{name} must have a minimum value of {ruleValue}",
            "empty": "{name} must have a value",
            "notEmpty": "{name} must have a value",
            "checked": "{name} must be checked",
            "email": "{name} must be a valid e-mail",
            "url": "{name} must be a valid url",
            "regExp": "{name} is not formatted correctly",
            "integer": "{name} must be an integer",
            "decimal": "{name} must be a decimal number",
            "number": "{name} must be set to a number",
            "is": "{name} must be \"{ruleValue}\"",
            "isExactly": "{name} must be exactly \"{ruleValue}\"",
            "not": "{name} cannot be set to \"{ruleValue}\"",
            "notExactly": "{name} cannot be set to exactly \"{ruleValue}\"",
            "contains": "{name} must contain \"{ruleValue}\"",
            "containsExactly": "{name} must contain exactly \"{ruleValue}\"",
            "doesntContain": "{name} cannot contain \"{ruleValue}\"",
            "doesntContainExactly": "{name} cannot contain exactly \"{ruleValue}\"",
            "minLength": "{name} must be at least {ruleValue} characters",
            "exactLength": "{name} must be exactly {ruleValue} characters",
            "maxLength": "{name} cannot be longer than {ruleValue} characters",
            "size": "{name} must have a length between {min} and {max} characters",
            "match": "{name} must match {ruleValue} field",
            "different": "{name} must have a different value than {ruleValue} field",
            "creditCard": "{name} must be a valid credit card number",
            "minCount": "{name} must have at least {ruleValue} choices",
            "exactCount": "{name} must have exactly {ruleValue} choices",
            "maxCount": "{name} must have {ruleValue} or less choices",
            "addErrors": "{name}: {error}"
        }
    },
    "loadbar": {
        "text": {
            "label": "Loading"
        }
    },
    "modal": {
        "text": {
            "ok": "Ok",
            "cancel": "Cancel",
            "close": "Close"
        }
    },
    "pin": {
        "text": {
            "label": "Verification code, {length} characters"
        }
    },
    "progress": {
        "text": {
            "percent": "{percent}%",
            "ratio": "{value} of {total}",
            "bars": [
                ""
            ]
        }
    },
    "search": {
        "error": {
            "noResults": "Your search returned no results",
            "serverError": "There was an issue querying the server."
        }
    },
    "shell": {
        "text": {
            "collapse": "Collapse menu",
            "expand": "Expand menu",
            "openMenu": "Open menu",
            "closeMenu": "Close menu"
        }
    },
    "slider": {
        "text": {
            "rangeStart": "Minimum",
            "rangeEnd": "Maximum"
        }
    },
    "slideshow": {
        "text": {
            "label": "Carousel",
            "roleDescription": "carousel",
            "slideRoleDescription": "slide",
            "slide": "{index} of {total}",
            "indicator": "Go to slide {index}",
            "previous": "Previous slide",
            "next": "Next slide",
            "play": "Resume rotation",
            "pause": "Stop rotation"
        }
    },
    "toast": {
        "text": {
            "close": "Close"
        }
    }
});
