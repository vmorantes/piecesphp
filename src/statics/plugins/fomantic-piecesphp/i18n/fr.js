/* Fomantic (fork): textos de los módulos en francés de Francia, con vous (D99). Todas las claves de base.json (examples/catalogo/i18n.mjs
   avisa de las que falten). Se carga tras semantic.js y fomantic-i18n.js. */
FomanticI18n.add('fr', {
    "calendar": {
        "text": {
            "days": [
                "D",
                "L",
                "M",
                "M",
                "J",
                "V",
                "S"
            ],
            "dayNamesShort": [
                "dim.",
                "lun.",
                "mar.",
                "mer.",
                "jeu.",
                "ven.",
                "sam."
            ],
            "dayNames": [
                "dimanche",
                "lundi",
                "mardi",
                "mercredi",
                "jeudi",
                "vendredi",
                "samedi"
            ],
            "months": [
                "janvier",
                "février",
                "mars",
                "avril",
                "mai",
                "juin",
                "juillet",
                "août",
                "septembre",
                "octobre",
                "novembre",
                "décembre"
            ],
            "monthsShort": [
                "janv.",
                "févr.",
                "mars",
                "avr.",
                "mai",
                "juin",
                "juil.",
                "août",
                "sept.",
                "oct.",
                "nov.",
                "déc."
            ],
            "today": "Aujourd'hui",
            "now": "Maintenant",
            "am": "AM",
            "pm": "PM",
            "weekNo": "Semaine"
        },
        "firstDayOfWeek": 1
    },
    "chat": {
        "text": {
            "title": "Assistant",
            "conversation": "Conversation",
            "close": "Fermer",
            "newChat": "Nouvelle conversation",
            "placeholder": "Saisissez votre question",
            "send": "Envoyer",
            "thinking": "Réflexion…",
            "empty": "Posez-moi toutes vos questions sur la plateforme.",
            "notices": {
                "scope": "Cela dépasse ce que je peux faire.",
                "unavailable": "L'assistant n'est pas disponible pour le moment.",
                "error": "Une erreur est survenue. Veuillez réessayer."
            }
        }
    },
    "colorpicker": {
        "text": {
            "panel": "Sélecteur de couleur",
            "trigger": "Choisir une couleur",
            "area": "Saturation et luminosité",
            "areaRole": "curseur 2D",
            "areaValue": "Saturation {saturation} %, luminosité {brightness} %, {value}",
            "hue": "Teinte",
            "hueValue": "{hue} degrés, {value}",
            "alpha": "Opacité",
            "alphaValue": "{alpha} %, {value}",
            "input": "Valeur de la couleur",
            "initial": "Couleur précédente : {value}. Appuyez pour la rétablir",
            "current": "Nouvelle couleur : {value}",
            "format": "Format de la couleur",
            "formats": {
                "hex": "HEX",
                "rgb": "RGB",
                "hsl": "HSL",
                "oklch": "OKLCH"
            },
            "channels": "Canaux de la couleur",
            "red": "R",
            "green": "V",
            "blue": "B",
            "hueChannel": "T",
            "saturation": "S",
            "lightness": "L",
            "palette": "Palette",
            "swatch": "Utiliser {value}",
            "recents": "Récentes",
            "analogous": "Analogues",
            "triad": "Triade",
            "complementary": "Complémentaire",
            "contrast": "Contraste",
            "contrastValue": "{ratio}:1",
            "contrastPass": "Conforme {threshold}:1",
            "contrastFail": "Inférieur à {threshold}:1",
            "eyedropper": "Prélever une couleur à l'écran",
            "clear": "Aucune couleur",
            "more": "Plus de couleurs",
            "less": "Moins de couleurs",
            "accept": "Valider",
            "cancel": "Annuler"
        }
    },
    "dropdown": {
        "message": {
            "addResult": "Ajouter <b>{term}</b>",
            "count": "{count} sélectionné(s)",
            "maxSelections": "{maxCount} sélections maximum",
            "noResults": "Aucun résultat.",
            "serverError": "Une erreur est survenue lors de la connexion au serveur"
        }
    },
    "flyout": {
        "text": {
            "ok": "OK",
            "cancel": "Annuler",
            "close": "Fermer"
        }
    },
    "form": {
        "text": {
            "and": "et",
            "unspecifiedRule": "Veuillez saisir une valeur valide",
            "unspecifiedField": "Ce champ",
            "leavingMessage": "Des modifications non enregistrées seront perdues si vous continuez."
        },
        "prompt": {
            "range": "{name} doit être compris entre {min} et {max}",
            "maxValue": "{name} doit valoir au maximum {ruleValue}",
            "minValue": "{name} doit valoir au minimum {ruleValue}",
            "empty": "{name} est obligatoire",
            "notEmpty": "{name} est obligatoire",
            "checked": "{name} doit être coché",
            "email": "{name} doit être une adresse e-mail valide",
            "url": "{name} doit être une URL valide",
            "regExp": "{name} n'est pas au bon format",
            "integer": "{name} doit être un nombre entier",
            "decimal": "{name} doit être un nombre décimal",
            "number": "{name} doit être un nombre",
            "is": "{name} doit être « {ruleValue} »",
            "isExactly": "{name} doit être exactement « {ruleValue} »",
            "not": "{name} ne peut pas être « {ruleValue} »",
            "notExactly": "{name} ne peut pas être exactement « {ruleValue} »",
            "contains": "{name} doit contenir « {ruleValue} »",
            "containsExactly": "{name} doit contenir exactement « {ruleValue} »",
            "doesntContain": "{name} ne peut pas contenir « {ruleValue} »",
            "doesntContainExactly": "{name} ne peut pas contenir exactement « {ruleValue} »",
            "minLength": "{name} doit comporter au moins {ruleValue} caractères",
            "exactLength": "{name} doit comporter exactement {ruleValue} caractères",
            "maxLength": "{name} ne peut pas dépasser {ruleValue} caractères",
            "size": "{name} doit comporter entre {min} et {max} caractères",
            "match": "{name} doit correspondre au champ {ruleValue}",
            "different": "{name} doit être différent du champ {ruleValue}",
            "creditCard": "{name} doit être un numéro de carte bancaire valide",
            "minCount": "{name} doit comporter au moins {ruleValue} choix",
            "exactCount": "{name} doit comporter exactement {ruleValue} choix",
            "maxCount": "{name} doit comporter au maximum {ruleValue} choix",
            "addErrors": "{name} : {error}"
        }
    },
    "loadbar": {
        "text": {
            "label": "Chargement"
        }
    },
    "modal": {
        "text": {
            "ok": "OK",
            "cancel": "Annuler",
            "close": "Fermer"
        }
    },
    "pin": {
        "text": {
            "label": "Code de vérification, {length} caractères"
        }
    },
    "progress": {
        "text": {
            "percent": "{percent} %",
            "ratio": "{value} sur {total}",
            "bars": [
                ""
            ]
        }
    },
    "search": {
        "error": {
            "noResults": "Votre recherche n’a donné aucun résultat",
            "serverError": "Un problème est survenu lors de l’interrogation du serveur."
        }
    },
    "shell": {
        "text": {
            "collapse": "Réduire le menu",
            "expand": "Développer le menu",
            "openMenu": "Ouvrir le menu",
            "closeMenu": "Fermer le menu"
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
            "label": "Carrousel",
            "roleDescription": "carrousel",
            "slideRoleDescription": "diapositive",
            "slide": "{index} sur {total}",
            "indicator": "Aller à la diapositive {index}",
            "previous": "Diapositive précédente",
            "next": "Diapositive suivante",
            "play": "Reprendre la rotation",
            "pause": "Arrêter la rotation"
        }
    },
    "toast": {
        "text": {
            "close": "Fermer"
        }
    }
});
