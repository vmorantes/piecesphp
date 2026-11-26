/* Fork: componente propio, no existe en Fomantic. */
/*!
 * # Fomantic-UI 1.4.0 - Pin
 * https://github.com/fomantic/Fomantic-UI/
 *
 *
 * Released under the MIT license
 * https://opensource.org/licenses/MIT
 *
 */

(function ($, window, document) {
    'use strict';

    function isFunction(obj) {
        return typeof obj === 'function' && typeof obj.nodeType !== 'number';
    }

    window = window !== undefined && window.Math === Math
        ? window
        : globalThis;

    $.fn.pin = function (parameters) {
        var
            $allModules     = $(this),

            time            = Date.now(),
            performance     = [],

            query           = arguments[0],
            methodInvoked   = typeof query === 'string',
            queryArguments  = [].slice.call(arguments, 1),
            returnedValue
        ;
        $allModules.each(function () {
            var
                settings        = $.isPlainObject(parameters)
                    ? $.extend(true, {}, $.fn.pin.settings, parameters)
                    : $.extend({}, $.fn.pin.settings),

                namespace       = settings.namespace,
                className       = settings.className,
                error           = settings.error,
                metadata        = settings.metadata,
                selector        = settings.selector,

                eventNamespace  = '.' + namespace,
                moduleNamespace = 'module-' + namespace,

                element         = this,
                instance        = $(this).data(moduleNamespace),

                $module         = $(this),
                $input,
                $cells,
                $cell,

                input,
                length,
                completed       = false,
                module
            ;

            module = {

                initialize: function () {
                    module.verbose('Initializing pin', settings);
                    $input = $module.children(selector.input).first();
                    input = $input[0];
                    if (!input) {
                        module.error(error.noInput, element);

                        return;
                    }
                    length = module.get.length();
                    module.setup.input();
                    module.create.cells();
                    module.bind.events();
                    // Desactivado: la clase también en el contenedor (estilo y herramientas que excluyen lo desactivado)
                    $module.toggleClass(className.disabled, input.disabled);
                    module.render();
                    module.instantiate();
                },

                instantiate: function () {
                    module.verbose('Storing instance of pin', module);
                    instance = module;
                    $module.data(moduleNamespace, module);
                },

                destroy: function () {
                    module.verbose('Destroying previous pin for', $module);
                    $input.off(eventNamespace);
                    $(document).off(eventNamespace + '-' + module.get.id());
                    $cells.remove();
                    $module
                        .removeClass(className.focus)
                        .removeData(moduleNamespace)
                    ;
                },

                setup: {
                    input: function () {
                        // El campo real: un código de un solo uso que el navegador y el teclado del móvil reconocen
                        if ($input.attr('autocomplete') === undefined) {
                            $input.attr('autocomplete', settings.autocomplete);
                        }
                        if ($input.attr('inputmode') === undefined) {
                            $input.attr('inputmode', module.is.numeric() ? 'numeric' : 'text');
                        }
                        if ($input.attr('spellcheck') === undefined) {
                            $input.attr('spellcheck', 'false');
                        }
                        if (module.is.numeric() && $input.attr('pattern') === undefined) {
                            $input.attr('pattern', '[0-9]{' + length + '}');
                        }
                        if ($input.attr('aria-label') === undefined && !$input.attr('aria-labelledby') && !$input.attr('id')) {
                            $input.attr('aria-label', settings.text.label.replace('{length}', length));
                        }
                    },
                },

                create: {
                    cells: function () {
                        var
                            html = '',
                            half = Math.ceil(length / 2) - 1,
                            index = 0
                        ;
                        while (index < length) {
                            html += '<span class="' + className.cell + (index === half ? ' ' + className.half : '') + '"></span>';
                            index++;
                        }
                        $cells = $('<span class="' + className.cells + '" aria-hidden="true">' + html + '</span>').insertBefore($input);
                        $cell = $cells.children();
                    },
                },

                bind: {
                    events: function () {
                        $input
                            .on('beforeinput' + eventNamespace, module.event.beforeInput)
                            .on('input' + eventNamespace, module.event.input)
                            .on('paste' + eventNamespace, module.event.paste)
                            .on('focus' + eventNamespace, module.event.focus)
                            .on('blur' + eventNamespace, module.event.blur)
                            .on('keyup' + eventNamespace + ' click' + eventNamespace + ' select' + eventNamespace, module.render)
                            .on('mousedown' + eventNamespace, module.event.mousedown)
                        ;
                        // Las flechas, Inicio y Fin mueven el cursor del campo: la casilla activa lo sigue
                        $(document).on('selectionchange' + eventNamespace + '-' + module.get.id(), function () {
                            if (document.activeElement === input) {
                                module.render();
                            }
                        });
                    },
                },

                event: {
                    beforeInput: function (event) {
                        var
                            original = event.originalEvent || event,
                            data = original.data,
                            value = input.value,
                            start = input.selectionStart,
                            end = input.selectionEnd,
                            chars
                        ;
                        // Pegar (o soltar) un código entero: se filtra antes de que el límite de longitud lo recorte; si trae
                        // todas las cifras, sustituye el valor; si no, se escribe desde la casilla activa
                        if (original.inputType === 'insertFromPaste' || original.inputType === 'insertFromDrop') {
                            event.preventDefault();
                            module.insert.pasted(original.dataTransfer ? original.dataTransfer.getData('text/plain') : data);

                            return;
                        }
                        if (original.inputType !== 'insertText' || data === null || data === undefined) {
                            return;
                        }
                        chars = module.filter(data);
                        event.preventDefault();
                        if (chars === '') {
                            return;
                        }
                        // Escribir sobre la casilla activa (no insertar y desplazar): así funciona un campo de código
                        if (start === end && start < value.length) {
                            end = start + chars.length;
                        }
                        module.set.value(value.slice(0, start) + chars + value.slice(end), start + chars.length);
                    },
                    paste: function (event) {
                        var clipboard = (event.originalEvent || event).clipboardData;
                        if (clipboard) {
                            event.preventDefault();
                            module.insert.pasted(clipboard.getData('text/plain'));
                        }
                    },
                    input: function () {
                        // Pegado, autorrelleno del SMS, dictado o borrado: se filtra y se recorta a la longitud
                        var
                            clean = module.filter(input.value),
                            caret = input.selectionStart
                        ;
                        if (clean !== input.value) {
                            module.set.value(clean, Math.min(caret, clean.length));
                        } else {
                            module.changed();
                        }
                    },
                    focus: function () {
                        $module.addClass(className.focus);
                        // Con el código completo, el foco selecciona la última casilla; si no, va a la primera vacía
                        requestAnimationFrame(function () {
                            var position = Math.min(input.value.length, length - 1);
                            if (input.value.length < length) {
                                position = input.value.length;
                            }
                            input.setSelectionRange(position, position);
                            module.render();
                        });
                    },
                    blur: function () {
                        $module.removeClass(className.focus);
                        module.render();
                    },
                    mousedown: function (event) {
                        // Clic en una casilla: el cursor va a ella (o a la primera vacía si está más allá)
                        var
                            x = event.clientX,
                            target = 0
                        ;
                        $cell.each(function (index) {
                            var rect = this.getBoundingClientRect();
                            if (x >= rect.left) {
                                target = index;
                            }
                        });
                        target = Math.min(target, input.value.length);
                        event.preventDefault();
                        input.focus();
                        input.setSelectionRange(target, target);
                        module.render();
                    },
                },

                insert: {
                    pasted: function (text) {
                        var
                            chars = module.filter(text || ''),
                            value = input.value,
                            start = input.selectionStart,
                            end = input.selectionEnd
                        ;
                        if (chars.length >= length) {
                            module.set.value(chars);
                        } else if (chars !== '') {
                            module.set.value(value.slice(0, start) + chars + value.slice(Math.max(end, start + chars.length)), start + chars.length);
                        }
                    },
                },

                filter: function (value) {
                    var
                        allowed = module.get.pattern(),
                        out = ''
                    ;
                    String(value).split('').forEach(function (char) {
                        if (settings.uppercase) {
                            char = char.toUpperCase();
                        }
                        if (allowed.test(char)) {
                            out += char;
                        }
                    });

                    return out.slice(0, length);
                },

                render: function () {
                    var
                        value = input.value,
                        caret = Math.min(input.selectionStart === null ? value.length : input.selectionStart, length - 1)
                    ;
                    $cell.each(function (index) {
                        var char = value.charAt(index);
                        this.textContent = char && settings.mask ? settings.maskCharacter : char;
                        this.classList.toggle(className.filled, char !== '');
                        this.classList.toggle(className.active, index === caret);
                    });
                },

                changed: function () {
                    var value = input.value;
                    module.render();
                    settings.onChange.call(element, value);
                    if (value.length === length && !completed) {
                        completed = true;
                        module.debug('Code complete', value);
                        settings.onComplete.call(element, value);
                    } else if (value.length < length) {
                        completed = false;
                    }
                },

                get: {
                    id: function () {
                        if (!module.uid) {
                            module.uid = Math.random().toString(16).slice(2, 10);
                        }

                        return module.uid;
                    },
                    length: function () {
                        var value = $module.data(metadata.length) || settings.length || Number($input.attr('maxlength'));

                        return Math.max(1, parseInt(value, 10) || 6);
                    },
                    pattern: function () {
                        var type = $module.data(metadata.type) || settings.type;
                        if (type instanceof RegExp) {
                            return type;
                        }

                        return settings.patterns[type] || settings.patterns.numeric;
                    },
                    value: function () {
                        return input.value;
                    },
                },

                is: {
                    numeric: function () {
                        return ($module.data(metadata.type) || settings.type) === 'numeric';
                    },
                    complete: function () {
                        return input.value.length === length;
                    },
                },

                set: {
                    value: function (value, caret) {
                        input.value = module.filter(value);
                        caret = caret === undefined ? input.value.length : Math.min(caret, input.value.length);
                        if (document.activeElement === input) {
                            input.setSelectionRange(caret, caret);
                        }
                        // Para quien escuche el campo (validación de form, frameworks)
                        $input.trigger('change');
                        module.changed();
                    },
                },

                clear: function () {
                    module.set.value('', 0);
                },

                focus: function () {
                    input.focus();
                },

                setting: function (name, value) {
                    module.debug('Changing setting', name, value);
                    if ($.isPlainObject(name)) {
                        $.extend(true, settings, name);
                    } else if (value !== undefined) {
                        if ($.isPlainObject(settings[name])) {
                            $.extend(true, settings[name], value);
                        } else {
                            settings[name] = value;
                        }
                    } else {
                        return settings[name];
                    }
                },
                internal: function (name, value) {
                    if ($.isPlainObject(name)) {
                        $.extend(true, module, name);
                    } else if (value !== undefined) {
                        module[name] = value;
                    } else {
                        return module[name];
                    }
                },
                debug: function () {
                    if (!settings.silent && settings.debug) {
                        if (settings.performance) {
                            module.performance.log(arguments);
                        } else {
                            module.debug = Function.prototype.bind.call(console.info, console, settings.name + ':');
                            module.debug.apply(console, arguments);
                        }
                    }
                },
                verbose: function () {
                    if (!settings.silent && settings.verbose && settings.debug) {
                        if (settings.performance) {
                            module.performance.log(arguments);
                        } else {
                            module.verbose = Function.prototype.bind.call(console.info, console, settings.name + ':');
                            module.verbose.apply(console, arguments);
                        }
                    }
                },
                error: function () {
                    if (!settings.silent) {
                        module.error = Function.prototype.bind.call(console.error, console, settings.name + ':');
                        module.error.apply(console, arguments);
                    }
                },
                performance: {
                    log: function (message) {
                        var
                            currentTime,
                            executionTime,
                            previousTime
                        ;
                        if (settings.performance) {
                            currentTime = Date.now();
                            previousTime = time || currentTime;
                            executionTime = currentTime - previousTime;
                            time = currentTime;
                            performance.push({
                                Name: message[0],
                                Arguments: [].slice.call(message, 1) || '',
                                Element: element,
                                'Execution Time': executionTime,
                            });
                        }
                        clearTimeout(module.performance.timer);
                        module.performance.timer = setTimeout(function () {
                            module.performance.display();
                        }, 500);
                    },
                    display: function () {
                        var
                            title = settings.name + ':',
                            totalTime = 0
                        ;
                        time = false;
                        clearTimeout(module.performance.timer);
                        $.each(performance, function (index, data) {
                            totalTime += data['Execution Time'];
                        });
                        title += ' ' + totalTime + 'ms';
                        if ($allModules.length > 1) {
                            title += ' (' + $allModules.length + ')';
                        }
                        if (performance.length > 0) {
                            console.groupCollapsed(title);
                            if (console.table) {
                                console.table(performance);
                            } else {
                                $.each(performance, function (index, data) {
                                    console.log(data.Name + ': ' + data['Execution Time'] + 'ms');
                                });
                            }
                            console.groupEnd();
                        }
                        performance = [];
                    },
                },
                invoke: function (query, passedArguments, context) {
                    var
                        object = instance,
                        maxDepth,
                        found,
                        response
                    ;
                    passedArguments = passedArguments || queryArguments;
                    context = context || element;
                    if (typeof query === 'string' && object !== undefined) {
                        query = query.split(/[ .]/);
                        maxDepth = query.length - 1;
                        $.each(query, function (depth, value) {
                            var camelCaseValue = depth !== maxDepth
                                ? value + query[depth + 1].charAt(0).toUpperCase() + query[depth + 1].slice(1)
                                : query
                            ;
                            if ($.isPlainObject(object[camelCaseValue]) && (depth !== maxDepth)) {
                                object = object[camelCaseValue];
                            } else if (object[camelCaseValue] !== undefined) {
                                found = object[camelCaseValue];

                                return false;
                            } else if ($.isPlainObject(object[value]) && (depth !== maxDepth)) {
                                object = object[value];
                            } else if (object[value] !== undefined) {
                                found = object[value];

                                return false;
                            } else {
                                module.error(error.method, query);

                                return false;
                            }
                        });
                    }
                    if (isFunction(found)) {
                        response = found.apply(context, passedArguments);
                    } else if (found !== undefined) {
                        response = found;
                    }
                    if (Array.isArray(returnedValue)) {
                        returnedValue.push(response);
                    } else if (returnedValue !== undefined) {
                        returnedValue = [returnedValue, response];
                    } else if (response !== undefined) {
                        returnedValue = response;
                    }

                    return found;
                },
            };
            if (methodInvoked) {
                if (instance === undefined) {
                    module.initialize();
                }
                module.invoke(query);
            } else {
                if (instance !== undefined) {
                    instance.invoke('destroy');
                }
                module.initialize();
            }
        });

        return returnedValue !== undefined
            ? returnedValue
            : this;
    };

    $.fn.pin.settings = {

        name: 'Pin',
        namespace: 'pin',

        silent: false,
        debug: false,
        verbose: false,
        performance: true,

        // Número de casillas (también data-length o el maxlength del campo)
        length: 6,
        // Caracteres admitidos: 'numeric', 'alphanumeric', 'alpha' o una RegExp de un carácter (también data-type)
        type: 'numeric',
        // Pasar a mayúsculas lo escrito (códigos alfanuméricos)
        uppercase: true,
        // Ocultar lo escrito (PIN) con maskCharacter; el campo puede ser además type="password"
        mask: false,
        maskCharacter: '•',
        // autocomplete del campo si el marcado no lo trae: el autorrelleno del código del SMS
        autocomplete: 'one-time-code',

        onChange: function (value) {},
        onComplete: function (value) {},

        patterns: {
            numeric: /^\d$/,
            alphanumeric: /^[\dA-Za-z]$/,
            alpha: /^[A-Za-z]$/,
        },

        error: {
            method: 'The method you called is not defined',
            noInput: 'Pin needs an <input> inside',
        },

        metadata: {
            length: 'length',
            type: 'type',
        },

        className: {
            cells: 'cells',
            cell: 'cell',
            half: 'half',
            active: 'active',
            filled: 'filled',
            focus: 'focus',
            disabled: 'disabled',
        },

        selector: {
            input: 'input',
        },

        text: {
            label: 'Verification code, {length} characters',
        },

    };
})(jQuery, window, document);
