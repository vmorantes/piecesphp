/* Fork: componente propio, no existe en Fomantic. */
/*!
 * # Fomantic-UI 2.0.1 - Loadbar
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

    // Barra de carga global al estilo NProgress (P44): una sola por página, colgada de body (o del elemento sobre el que se
    // llame). $.loadbar('start') y $.loadbar('done') con contador, para varias cargas a la vez.
    $.loadbar = $.fn.loadbar = function (parameters) {
        var
            $allModules     = isFunction(this) ? $('body') : $(this),

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
                    ? $.extend(true, {}, $.fn.loadbar.settings, parameters)
                    : $.extend({}, $.fn.loadbar.settings),

                namespace       = settings.namespace,
                className       = settings.className,
                error           = settings.error,

                eventNamespace  = '.' + namespace,
                moduleNamespace = 'module-' + namespace,

                element         = this,
                instance        = $(this).data(moduleNamespace),

                $context        = $(this),
                $module,
                $bar,

                count           = 0,
                percent         = 0,
                active          = false,
                timers          = {},
                module
            ;

            module = {

                initialize: function () {
                    module.verbose('Initializing loadbar', settings);
                    module.create.bar();
                    if (settings.ajax) {
                        module.bind.ajax();
                    }
                    module.instantiate();
                },

                instantiate: function () {
                    instance = module;
                    $context.data(moduleNamespace, module);
                },

                destroy: function () {
                    module.verbose('Destroying loadbar');
                    module.clear.timers();
                    module.unbind.ajax();
                    if ($module) {
                        $module.remove();
                    }
                    $context.removeData(moduleNamespace);
                },

                create: {
                    bar: function () {
                        $module = $('<div>')
                            .addClass(className.progress)
                            .addClass(settings.position === 'bottom' ? className.bottom : '')
                            .addClass(settings.class)
                            .attr({
                                role: 'progressbar',
                                'aria-label': settings.text.label,
                                'aria-valuemin': 0,
                                'aria-valuemax': 100,
                                'aria-valuenow': 0,
                                'aria-hidden': 'true',
                            })
                        ;
                        $bar = $('<div>').addClass(className.bar).appendTo($module);
                        $module.appendTo($context);
                    },
                },

                bind: {
                    ajax: function () {
                        // Peticiones de jQuery (también las del módulo api), no fetch: cada una cuenta como una carga
                        $(document)
                            .on('ajaxSend' + eventNamespace, function () {
                                module.start();
                            })
                            .on('ajaxComplete' + eventNamespace, function () {
                                module.done();
                            })
                        ;
                    },
                },

                unbind: {
                    ajax: function () {
                        $(document).off(eventNamespace);
                    },
                },

                clear: {
                    timers: function () {
                        $.each(timers, function (name, timer) {
                            clearTimeout(timer);
                            clearInterval(timer);
                        });
                        timers = {};
                    },
                },

                start: function () {
                    count++;
                    module.debug('Start', count);
                    if (active) {
                        return;
                    }
                    active = true;
                    clearTimeout(timers.hide);
                    clearTimeout(timers.reset);
                    module.set.percent(settings.minimum);
                    settings.onStart.call(element);
                    // Una carga más corta que el retraso no enseña la barra (sin parpadeo)
                    if (settings.delay > 0 && !module.is.visible()) {
                        timers.show = setTimeout(module.show, settings.delay);
                    } else {
                        module.show();
                    }
                    if (settings.trickle) {
                        timers.trickle = setInterval(module.trickle, settings.trickleSpeed);
                    }
                },

                done: function (force) {
                    count = force ? 0 : Math.max(0, count - 1);
                    module.debug('Done', count);
                    if (count > 0 || !active) {
                        return;
                    }
                    active = false;
                    clearTimeout(timers.show);
                    clearInterval(timers.trickle);
                    if (!module.is.visible()) {
                        module.set.percent(0);
                        settings.onDone.call(element);

                        return;
                    }
                    module.set.percent(100);
                    settings.onDone.call(element);
                    timers.hide = setTimeout(module.hide, settings.hideDelay);
                },

                show: function () {
                    $module.addClass(className.visible).removeAttr('aria-hidden');
                    settings.onShow.call(element);
                },

                hide: function () {
                    $module.removeClass(className.visible).attr('aria-hidden', 'true');
                    settings.onHide.call(element);
                    // A cero cuando ya no se ve (el fundido dura lo que hideDelay)
                    timers.reset = setTimeout(function () {
                        if (!active) {
                            module.set.percent(0);
                        }
                    }, settings.hideDelay);
                },

                // Avance que se frena al acercarse al máximo, como NProgress: nunca llega solo al final
                trickle: function () {
                    var step = percent < 20 ? 10 : (percent < 50 ? 4 : (percent < 80 ? 2 : 0.5));
                    module.set.percent(Math.min(settings.maximum, percent + step));
                },

                set: {
                    percent: function (value) {
                        percent = Math.max(0, Math.min(100, Number(value) || 0));
                        $bar.css('width', percent + '%');
                        $module.attr('aria-valuenow', Math.round(percent));
                    },
                },

                get: {
                    percent: function () {
                        return percent;
                    },
                    count: function () {
                        return count;
                    },
                },

                is: {
                    active: function () {
                        return active;
                    },
                    visible: function () {
                        return $module.hasClass(className.visible);
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

    $.fn.loadbar.settings = {

        name: 'Loadbar',
        namespace: 'loadbar',

        silent: false,
        debug: false,
        verbose: false,
        performance: true,

        // 'top' o 'bottom' de la ventana
        position: 'top',
        // Clases añadidas a la barra: un color de Fomantic ('primary', 'red', 'accent-2'…); sin nada, el primario del tema
        class: '',
        // Milisegundos antes de enseñarla: una carga más corta no la muestra
        delay: 200,
        // Porcentaje con el que arranca y tope del avance automático (nunca llega sola al 100)
        minimum: 8,
        maximum: 99,
        // Avance automático y cada cuántos milisegundos
        trickle: true,
        trickleSpeed: 400,
        // Milisegundos al 100 % antes de irse, y del fundido
        hideDelay: 300,
        // Contar las peticiones AJAX de jQuery (y del módulo api) sin llamar a start ni a done
        ajax: false,

        onStart: function () {},
        onShow: function () {},
        onDone: function () {},
        onHide: function () {},

        error: {
            method: 'The method you called is not defined',
        },

        className: {
            progress: 'ui fixed progress',
            bottom: 'bottom',
            bar: 'bar',
            visible: 'visible',
        },

        text: {
            label: 'Loading',
        },

    };
})(jQuery, window, document);
