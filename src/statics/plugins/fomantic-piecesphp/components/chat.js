/* Fork: componente propio, no existe en Fomantic. */
/*!
 * # Fomantic-UI 1.4.0 - Chat
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

    $.fn.chat = function (parameters) {
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
                    ? $.extend(true, {}, $.fn.chat.settings, parameters)
                    : $.extend({}, $.fn.chat.settings),

                namespace       = settings.namespace,
                className       = settings.className,
                error           = settings.error,
                metadata        = settings.metadata,
                selector        = settings.selector,
                text            = settings.text,

                eventNamespace  = '.' + namespace,
                moduleNamespace = 'module-' + namespace,

                element         = this,
                instance        = $(this).data(moduleNamespace),

                $module         = $(this),
                $header,
                $title,
                $conversation,
                $composer,
                $textarea,
                $counter,
                $send,
                $launchers,
                $returnFocus    = $(),

                thinking        = false,
                id,
                module
            ;

            module = {

                initialize: function () {
                    module.verbose('Initializing chat', settings);
                    id = $module.attr('id') || ('chat-' + Math.random().toString(16).slice(2, 10));
                    $module.attr('id', id);
                    module.create.parts();
                    // Sin ajuste ni data-position, mandan las clases del marcado
                    if (settings.position || $module.data(metadata.position)) {
                        module.set.position(settings.position || $module.data(metadata.position));
                    }
                    module.bind.events();
                    module.refresh.composer();
                    if ($conversation.children(selector.turn + ', ' + selector.notice).length === 0) {
                        module.show.empty();
                    }
                    module.instantiate();
                },

                instantiate: function () {
                    module.verbose('Storing instance of chat', module);
                    instance = module;
                    $module.data(moduleNamespace, module);
                },

                destroy: function () {
                    module.verbose('Destroying previous chat for', $module);
                    $module.off(eventNamespace).removeData(moduleNamespace);
                    $launchers.off(eventNamespace);
                },

                create: {
                    parts: function () {
                        var titleId = id + '-title';
                        // Cerrado por la clase (visible), no por hidden: así la transición funciona; hidden solo evita el destello antes del JS
                        $module.attr({ role: 'dialog', 'aria-labelledby': titleId }).prop('hidden', false);
                        $header = $module.children(selector.header);
                        if ($header.length === 0) {
                            $header = $(settings.templates.header(text, className)).prependTo($module);
                        }
                        $title = $header.children(selector.title).attr('id', titleId);
                        if ($title.text() === '') {
                            $title.text(text.title);
                        }
                        $conversation = $module.children(selector.conversation);
                        if ($conversation.length === 0) {
                            $conversation = $('<div class="' + className.conversation + '"></div>').insertAfter($header);
                        }
                        // Las respuestas nuevas se anuncian sin interrumpir
                        $conversation.attr({ role: 'log', 'aria-live': 'polite', 'aria-relevant': 'additions', tabindex: '0', 'aria-label': text.conversation });
                        $composer = $module.children(selector.composer);
                        if ($composer.length === 0) {
                            $composer = $(settings.templates.composer(text, className, settings)).appendTo($module);
                        }
                        $textarea = $composer.find('textarea');
                        $textarea.attr('maxlength', settings.maxLength);
                        if (!$textarea.attr('aria-label') && !$textarea.attr('id')) {
                            $textarea.attr('aria-label', text.placeholder);
                        }
                        $counter = $composer.find(selector.counter);
                        $send = $composer.find(selector.send);
                        $launchers = $(selector.launcher.replace('{id}', id));
                        $launchers.attr({ 'aria-controls': id, 'aria-expanded': 'false' });
                    },
                },

                bind: {
                    events: function () {
                        $module
                            .on('click' + eventNamespace, selector.close, module.close)
                            .on('click' + eventNamespace, selector.newChat, module.event.newChat)
                            .on('click' + eventNamespace, selector.suggestion, module.event.suggestion)
                            .on('keydown' + eventNamespace, module.event.keydown)
                            .on('submit' + eventNamespace, selector.composer, module.event.submit)
                            .on('input' + eventNamespace, 'textarea', module.refresh.composer)
                        ;
                        $launchers.on('click' + eventNamespace, function (event) {
                            event.preventDefault();
                            $returnFocus = $(this);
                            module.toggle();
                        });
                    },
                },

                event: {
                    keydown: function (event) {
                        if (event.key === 'Escape') {
                            event.stopPropagation();
                            module.close();
                        } else if (event.key === 'Enter' && $(event.target).is($textarea) && !event.shiftKey && !event.isComposing && event.keyCode !== 229) {
                            // Enter envía; Mayús+Enter salta de línea (y no se envía a mitad de una composición IME)
                            event.preventDefault();
                            module.submit();
                        }
                    },
                    submit: function (event) {
                        event.preventDefault();
                        module.submit();
                    },
                    newChat: function () {
                        module.clear();
                        settings.onNew.call(element);
                        $textarea.trigger('focus');
                    },
                    suggestion: function () {
                        var value = $(this).data(metadata.text) || $(this).text();
                        $textarea.val(String(value).trim());
                        module.submit();
                    },
                },

                submit: function () {
                    var value = String($textarea.val()).trim();
                    if (value === '' || thinking) {
                        return;
                    }
                    // El mensaje del usuario se pinta antes de avisar: el «pensando» que ponga onSubmit va debajo
                    var $echo = settings.echo ? module.add.user(value) : $();
                    if (settings.onSubmit.call(element, value) === false) {
                        $echo.remove();

                        return;
                    }
                    $textarea.val('');
                    module.refresh.composer();
                },

                open: function () {
                    if (module.is.open()) {
                        return;
                    }
                    if ($returnFocus.length === 0) {
                        $returnFocus = $(document.activeElement);
                    }
                    // Un fotograma para que la transición arranque desde el estado cerrado
                    requestAnimationFrame(function () {
                        $module.addClass(className.visible);
                        $launchers.attr('aria-expanded', 'true');
                        module.scroll.bottom();
                        $textarea.trigger('focus');
                        settings.onOpen.call(element);
                    });
                },

                close: function () {
                    if (!module.is.open()) {
                        return;
                    }
                    $module.removeClass(className.visible);
                    $launchers.attr('aria-expanded', 'false');
                    if ($returnFocus.length > 0 && document.contains($returnFocus[0])) {
                        $returnFocus.trigger('focus');
                    } else if ($launchers.length > 0) {
                        $launchers.first().trigger('focus');
                    }
                    $returnFocus = $();
                    settings.onClose.call(element);
                },

                toggle: function () {
                    if (module.is.open()) {
                        module.close();
                    } else {
                        module.open();
                    }
                },

                add: {
                    user: function (value, meta) {
                        return module.add.turn(className.user, value, meta);
                    },
                    assistant: function (value, meta) {
                        return module.add.turn(className.assistant, value, meta);
                    },
                    turn: function (role, value, meta) {
                        var
                            $turn = $('<div class="' + role + ' ' + className.turn + '"></div>'),
                            $bubble = $('<div class="' + className.bubble + '"></div>').appendTo($turn)
                        ;
                        module.hide.empty();
                        // Una respuesta termina el «pensando»; un mensaje del usuario, no
                        if (role !== className.user) {
                            module.set.thinking(false);
                        }
                        module.fill($bubble, value);
                        $turn.attr('data-role', role);
                        if (meta !== undefined) {
                            $turn.attr('data-' + metadata.meta, typeof meta === 'string' ? meta : JSON.stringify(meta));
                        }
                        $conversation.append($turn);
                        module.scroll.bottom();

                        return $turn;
                    },
                    actions: function (actions) {
                        // Pantallas del propio sitio que propone el asistente: bajo su último mensaje
                        var
                            $turn = $conversation.children(selector.assistant).last(),
                            $actions = $('<div class="' + className.actions + '"></div>')
                        ;
                        if ($turn.length === 0 || !Array.isArray(actions) || actions.length === 0) {
                            return;
                        }
                        actions.forEach(function (action) {
                            var $link = $(settings.templates.action(className)).attr('href', action.url);
                            $link.text(action.title);
                            $actions.append($link);
                        });
                        $turn.children(selector.actions).remove();
                        $turn.append($actions);
                        module.scroll.bottom();
                    },
                    notice: function (type, value) {
                        var
                            kind = settings.notices[type] || settings.notices.error,
                            $notice = $('<div class="ui ' + kind + ' ' + className.notice + ' message" role="status"></div>')
                        ;
                        module.hide.empty();
                        module.set.thinking(false);
                        module.fill($notice, value === undefined ? text.notices[type] || text.notices.error : value);
                        $notice.attr('data-notice', type);
                        $conversation.append($notice);
                        module.scroll.bottom();

                        return $notice;
                    },
                },

                // El texto del asistente puede traer contenido de terceros: siempre como texto, nunca como HTML.
                // Párrafos por líneas en blanco; un salto simple, salto de línea.
                fill: function ($target, value) {
                    String(value === undefined || value === null ? '' : value)
                        .replace(/\r\n?/g, '\n')
                        .split(/\n{2,}/)
                        .forEach(function (paragraph) {
                            var p = document.createElement('p');
                            paragraph.split('\n').forEach(function (line, index) {
                                if (index > 0) {
                                    p.appendChild(document.createElement('br'));
                                }
                                p.appendChild(document.createTextNode(line));
                            });
                            $target[0].appendChild(p);
                        })
                    ;
                },

                set: {
                    thinking: function (state) {
                        thinking = state !== false;
                        $conversation.children(selector.thinking).remove();
                        if (thinking) {
                            module.hide.empty();
                            $conversation.append(settings.templates.thinking(text, className));
                            module.scroll.bottom();
                        }
                        $module.toggleClass(className.thinking, thinking).attr('aria-busy', thinking ? 'true' : 'false');
                        module.refresh.composer();
                    },
                    position: function (position) {
                        var classes = [className.left, className.right, className.floating].join(' ');
                        position = String(position || 'right').toLowerCase();
                        $module.removeClass(classes).addClass(position.split(/\s+/).filter(function (name) {
                            return [className.left, className.right, className.floating].indexOf(name) !== -1;
                        }).join(' '));
                    },
                    suggestions: function (list) {
                        settings.suggestions = list || [];
                        if ($conversation.children(selector.empty).length > 0) {
                            module.show.empty();
                        }
                    },
                },

                show: {
                    empty: function () {
                        var $empty = $(settings.templates.empty(text, className));
                        (settings.suggestions || []).forEach(function (suggestion) {
                            var $button = $(settings.templates.suggestion(className));
                            $button.text(suggestion);
                            $empty.children(selector.suggestions).append($button);
                        });
                        $conversation.children(selector.empty).remove();
                        $conversation.append($empty);
                    },
                },

                hide: {
                    empty: function () {
                        $conversation.children(selector.empty).remove();
                    },
                },

                refresh: {
                    composer: function () {
                        var
                            length = $textarea.val().length,
                            left = settings.maxLength - length
                        ;
                        // Contador solo cerca del límite
                        if (length >= settings.counterFrom) {
                            $counter.prop('hidden', false).text(length + ' / ' + settings.maxLength).toggleClass(className.limit, left <= 0);
                        } else {
                            $counter.prop('hidden', true);
                        }
                        $send.prop('disabled', thinking || String($textarea.val()).trim() === '');
                        $send.toggleClass(className.disabled, thinking || String($textarea.val()).trim() === '');
                        // Alto según el contenido, hasta el máximo del CSS
                        $textarea.css('height', 'auto');
                        $textarea.css('height', $textarea[0].scrollHeight + 'px');
                    },
                },

                scroll: {
                    bottom: function () {
                        $conversation[0].scrollTop = $conversation[0].scrollHeight;
                    },
                },

                clear: function () {
                    $conversation.children(selector.turn + ', ' + selector.notice).remove();
                    module.set.thinking(false);
                    module.show.empty();
                },

                focus: function () {
                    $textarea.trigger('focus');
                },

                get: {
                    history: function () {
                        return $conversation.children(selector.turn).not(selector.thinking).map(function () {
                            var
                                $turn = $(this),
                                meta = $turn.attr('data-' + metadata.meta)
                            ;
                            try {
                                meta = meta === undefined ? undefined : JSON.parse(meta);
                            } catch (e) {}

                            return {
                                role: $turn.attr('data-role'),
                                text: $turn.children(selector.bubble).children('p').map(function () {
                                    return this.innerText !== undefined ? this.innerText : this.textContent;
                                }).get().join('\n\n'),
                                meta: meta,
                            };
                        }).get();
                    },
                    value: function () {
                        return $textarea.val();
                    },
                },

                is: {
                    open: function () {
                        return $module.hasClass(className.visible);
                    },
                    thinking: function () {
                        return thinking;
                    },
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

    $.fn.chat.settings = {

        name: 'Chat',
        namespace: 'chat',

        silent: false,
        debug: false,
        verbose: false,
        performance: true,

        // 'right' (por defecto), 'left', 'floating right' o 'floating left' (también data-position o las clases)
        position: false,
        // Límite del campo de escribir y desde cuántos caracteres se ve el contador
        maxLength: 1000,
        counterFrom: 900,
        // Pintar el mensaje del usuario al enviar (si es false, lo añade quien escuche onSubmit)
        echo: true,
        // Sugerencias del estado vacío (botones que envían su texto)
        suggestions: [],

        // Devuelve false para no enviar (ni limpiar el campo)
        onSubmit: function (value) {},
        // Botón «nueva conversación», tras vaciarla
        onNew: function () {},
        onOpen: function () {},
        onClose: function () {},

        // Variante de ui message de cada aviso
        notices: {
            scope: 'info',
            unavailable: 'warning',
            error: 'negative',
        },

        error: {
            method: 'The method you called is not defined',
        },

        metadata: {
            position: 'position',
            meta: 'chat-meta',
            text: 'text',
        },

        className: {
            visible: 'visible',
            left: 'left',
            right: 'right',
            floating: 'floating',
            header: 'header',
            title: 'title',
            conversation: 'conversation',
            composer: 'composer',
            turn: 'turn',
            user: 'user',
            assistant: 'assistant',
            bubble: 'bubble',
            actions: 'actions',
            notice: 'notice',
            thinking: 'thinking',
            empty: 'empty',
            suggestions: 'suggestions',
            limit: 'limit',
            disabled: 'disabled',
        },

        selector: {
            header: '.header',
            title: '.title',
            conversation: '.conversation',
            composer: '.composer',
            counter: '.counter',
            send: '.send',
            close: '.close',
            newChat: '.new',
            turn: '.turn',
            assistant: '.assistant.turn',
            thinking: '.thinking.turn',
            bubble: '.bubble',
            actions: '.actions',
            notice: '.notice.message',
            empty: '.empty',
            suggestions: '.suggestions',
            suggestion: '.suggestions > .button',
            launcher: '[data-chat="#{id}"]',
        },

        text: {
            title: 'Assistant',
            conversation: 'Conversation',
            close: 'Close',
            newChat: 'New conversation',
            placeholder: 'Write a message',
            send: 'Send',
            thinking: 'Thinking…',
            empty: 'Ask me anything about the platform.',
            notices: {
                scope: 'That is outside what I can help with.',
                unavailable: 'The assistant is not available right now.',
                error: 'Something went wrong. Try again.',
            },
        },

        templates: {
            deQuote: function (string, encode) {
                return String(string).replace(/"/g, encode ? '&quot;' : '');
            },
            header: function (text, className) {
                var q = $.fn.chat.settings.templates.deQuote;

                return '<div class="' + className.header + '">'
                    + '<h2 class="' + className.title + '"></h2>'
                    + '<button type="button" class="ui tertiary icon button new" aria-label="' + q(text.newChat, true) + '" data-tooltip="' + q(text.newChat, true) + '" data-position="bottom right"><i class="edit outline icon" aria-hidden="true"></i></button>'
                    + '<button type="button" class="ui tertiary icon button close" aria-label="' + q(text.close, true) + '"><i class="close icon" aria-hidden="true"></i></button>'
                    + '</div>';
            },
            composer: function (text, className, settings) {
                var q = $.fn.chat.settings.templates.deQuote;

                return '<form class="ui form ' + className.composer + '">'
                    + '<div class="field"><textarea rows="1" placeholder="' + q(text.placeholder, true) + '"></textarea><span class="counter" aria-live="polite" hidden></span></div>'
                    + '<button type="submit" class="ui primary icon button send" aria-label="' + q(text.send, true) + '"><i class="paper plane icon" aria-hidden="true"></i></button>'
                    + '</form>';
            },
            thinking: function (text, className) {
                return '<div class="' + className.assistant + ' ' + className.thinking + ' ' + className.turn + '" role="status">'
                    + '<div class="' + className.bubble + '"><span class="ui active inline mini loader" aria-hidden="true"></span>'
                    + $('<span>').text(text.thinking).prop('outerHTML') + '</div></div>';
            },
            empty: function (text, className) {
                return '<div class="' + className.empty + '"><i class="big comments outline icon" aria-hidden="true"></i>'
                    + $('<p>').text(text.empty).prop('outerHTML')
                    + '<div class="' + className.suggestions + '"></div></div>';
            },
            suggestion: function (className) {
                return '<button type="button" class="ui basic small button"></button>';
            },
            action: function (className) {
                return '<a class="ui basic small button"></a>';
            },
        },

    };
})(jQuery, window, document);
