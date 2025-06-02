# Instrucciones de implementación

## Integración de archivos

1. Formatear los HTML de origen con estos settings:
```json
"html.format.wrapLineLength": 0,
"html.format.wrapAttributes": "auto",
"html.format.wrapAttributesIndentSize": 0,
"html.format.maxPreserveNewLines": 0,
"html.format.preserveNewLines": true,
```
2. Al integrar a PHP seguir pasos:
    1. Formateo:
        - Formatear con Format HTML in PHP.
        - Settings del formateador:        
        ```json
        "[php]": {
            "editor.defaultFormatter": "kokororin.vscode-phpfmt",
            "editor.formatOnSave": false
        },
        "[html]": {
            "editor.formatOnSave": false
        },
        ```
    2. Buscar cadena: images/ (con distinción de mayúsculas)
        - Remplazar por: statics/wf/images/
    3. Quitar (srcset=("|')[a-z|A-Z|0-9|\/\-\.\s,\(\):_]*("|')|sizes=("|')[a-z|A-Z|0-9|\/\-\.\s,\(\):_]*("|')) en imágenes.
    4. Buscar elementos .html para remplazar por urls reales.
    5. Estandarizar carateres (en HTML y en traducciones):
        - &#x27; => '
        - &nbsp; => por un espacio;
        - & => &amp;
        - [\u00A0] se reemplaza por un espacio (usar regexp)
    6. Buscar href
        - Remplazar href=("|')#("|') por href="<?= get_current_url(); ?>#TODO:"
        - Remplazar href="<\?= get_current_url\(\); \?>#TODO:" por lo que corresponda
    7. Buscar vacíos: lang-group=("|')("|')
3. Menús:
    1. Clase w--current en etiqueta actual del menú y atributo aria-current="page".

## Guía de páginas