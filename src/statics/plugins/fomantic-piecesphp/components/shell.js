/* Fork: componente propio, no existe en Fomantic. */
/*!
 * # Fomantic-UI 0.10.1 - Shell
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

    $.fn.shell = function (parameters) {
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
                    ? $.extend(true, {}, $.fn.shell.settings, parameters)
                    : $.extend({}, $.fn.shell.settings),

                namespace       = settings.namespace,
                className       = settings.className,
                error           = settings.error,
                selector        = settings.selector,
                cssVars         = settings.cssVars,
                modes           = settings.modes,
                text            = settings.text,

                eventNamespace  = '.' + namespace,
                moduleNamespace = 'module-' + namespace,

                element         = this,
                instance        = $(this).data(moduleNamespace),

                $module         = $(this),
                $document       = $(document),
                $window         = $(window),
                $navigation,
                $main,
                $menu,
                $toggle,
                $drawerToggle,

                id              = Math.random().toString(16).slice(2, 10),
                currentMode     = null,
                preference      = false,
                lastFocus       = null,
                bodyOverflow    = null,
                resizeFrame     = null,
                module
            ;

            module = {

                initialize: function () {
                    module.verbose('Initializing shell', settings);
                    $module.addClass(className.initializing);
                    module.refresh();
                    if ($navigation.length === 0) {
                        module.error(error.noNavigation, element);
                    }
                    module.setup.navigation();
                    module.setup.accordion();
                    preference = module.get.storedState();
                    module.check.mode();
                    module.set.aria();
                    module.bind.events();
                    module.instantiate();
                    // Sin transición hasta que el estado inicial está pintado
                    setTimeout(function () {
                        $module.removeClass(className.initializing);
                    }, 0);
                },

                instantiate: function () {
                    module.verbose('Storing instance of shell', module);
                    instance = module;
                    $module.data(moduleNamespace, module);
                },

                destroy: function () {
                    module.verbose('Destroying previous instance of shell');
                    if (module.is.drawerOpen()) {
                        module.hide.drawer(false);
                    }
                    module.unpin();
                    module.unbind.events();
                    if (resizeFrame) {
                        window.cancelAnimationFrame(resizeFrame);
                    }
                    $module
                        .removeClass(className.initializing)
                        .removeData(moduleNamespace)
                    ;
                },

                refresh: function () {
                    $navigation = $module.children(selector.navigation);
                    $main = $module.children(selector.main);
                    $menu = $navigation.children(selector.menu);
                    $toggle = $navigation.children(selector.toggle).not(selector.drawerToggle);
                    $drawerToggle = $module.find(selector.drawerToggle);
                },

                setup: {
                    navigation: function () {
                        if (!$navigation.attr('id')) {
                            $navigation.attr('id', namespace + '-navigation-' + id);
                        }
                    },
                    accordion: function () {
                        var
                            $accordion = $menu.filter(selector.accordion)
                        ;
                        if (!settings.accordion || $accordion.length === 0 || !isFunction($.fn.accordion)) {
                            return;
                        }
                        if ($accordion.data('module-accordion') === undefined) {
                            module.debug('Initializing navigation accordion');
                            $accordion.accordion(settings.accordion);
                        }
                    },
                },

                bind: {
                    events: function () {
                        module.debug('Binding events');
                        $toggle.on('click' + eventNamespace, module.event.toggle.click);
                        $module
                            .on('click' + eventNamespace, selector.drawerToggle, module.event.drawerToggle.click)
                            .on('click' + eventNamespace, module.event.veil.click)
                        ;
                        $navigation
                            .on('click' + eventNamespace, selector.title, module.event.title.click)
                            .on('click' + eventNamespace, selector.link, module.event.link.click)
                            .on('keydown' + eventNamespace, module.event.navigation.keydown)
                        ;
                        $document
                            .on('keydown' + eventNamespace + id, module.event.document.keydown)
                            .on('mousedown' + eventNamespace + id + ' touchstart' + eventNamespace + id, module.event.document.pointerdown)
                        ;
                        $window.on('resize' + eventNamespace + id, module.event.window.resize);
                    },
                },

                unbind: {
                    events: function () {
                        $toggle.off(eventNamespace);
                        $module.off(eventNamespace);
                        $navigation.off(eventNamespace);
                        $document.off(eventNamespace + id);
                        $window.off(eventNamespace + id);
                    },
                },

                event: {
                    toggle: {
                        click: function (event) {
                            event.preventDefault();
                            module.toggle();
                        },
                    },
                    drawerToggle: {
                        click: function (event) {
                            event.preventDefault();
                            module.toggleDrawer();
                        },
                    },
                    veil: {
                        click: function (event) {
                            // El velo es ::before del propio shell: el clic llega con target = shell
                            if (event.target === element && module.is.drawerOpen()) {
                                module.hide.drawer();
                            }
                        },
                    },
                    title: {
                        click: function () {
                            var
                                $item = $(this).closest(selector.item)
                            ;
                            if (!module.is.collapsed()) {
                                return;
                            }
                            // En el riel el acordeón no se abre: el título fija el flotante
                            if ($item.hasClass(className.pinned)) {
                                $item.removeClass(className.pinned);
                            } else {
                                module.unpin();
                                $item.addClass(className.pinned);
                            }
                        },
                    },
                    link: {
                        click: function () {
                            if (settings.closeOnNavigate && module.is.drawerOpen()) {
                                module.hide.drawer(false);
                            }
                        },
                    },
                    navigation: {
                        keydown: function (event) {
                            if (event.key === 'Tab' && module.is.drawerOpen()) {
                                module.trap.focus(event);
                            }
                        },
                    },
                    document: {
                        keydown: function (event) {
                            var
                                $pinned,
                                $search
                            ;
                            // Ctrl/⌘ + tecla: enfoca el buscador de la barra
                            if (settings.searchShortcut && (event.ctrlKey || event.metaKey) && !event.altKey
                                && String(event.key).toLowerCase() === settings.searchShortcut) {
                                $search = $main.find(selector.search).filter(':visible').first();
                                if ($search.length > 0) {
                                    event.preventDefault();
                                    $search.trigger('focus').trigger('select');
                                }

                                return;
                            }
                            if (event.key !== 'Escape' || !settings.closeOnEscape) {
                                return;
                            }
                            if (module.is.drawerOpen()) {
                                module.hide.drawer();

                                return;
                            }
                            $pinned = $navigation.find(selector.item + '.' + className.pinned);
                            if ($pinned.length > 0 || ($navigation.has(document.activeElement).length > 0 && module.is.collapsed())) {
                                module.unpin();
                                if ($.contains($navigation[0], document.activeElement)) {
                                    $(document.activeElement).closest(selector.item).children(selector.title).trigger('focus');
                                }

                                return;
                            }
                            if (module.is.overlay()) {
                                module.collapse();
                                $toggle.first().trigger('focus');
                            }
                        },
                        pointerdown: function (event) {
                            if ($.contains($navigation[0], event.target)) {
                                return;
                            }
                            module.unpin();
                            // En tableta la navegación expandida flota: un clic fuera la recoge
                            if (module.is.overlay()) {
                                module.collapse();
                            }
                        },
                    },
                    window: {
                        resize: function () {
                            if (resizeFrame) {
                                return;
                            }
                            resizeFrame = window.requestAnimationFrame(function () {
                                resizeFrame = null;
                                module.check.mode();
                            });
                        },
                    },
                },

                check: {
                    mode: function () {
                        var
                            mode = module.get.mode()
                        ;
                        if (mode === currentMode) {
                            return;
                        }
                        module.debug('Layout mode changed', currentMode, mode);
                        // Al iniciar se respeta el marcado (p. ej. un .pinned de ejemplo)
                        if (currentMode !== null) {
                            module.unpin();
                        }
                        currentMode = mode;
                        if (module.is.drawerOpen()) {
                            module.hide.drawer(false);
                        }
                        if (mode === modes.rail) {
                            $module.addClass(className.collapsed);
                        } else if (mode === modes.drawer) {
                            $module.removeClass(className.collapsed);
                        } else {
                            $module.toggleClass(className.collapsed, preference);
                        }
                        module.set.aria();
                    },
                },

                collapse: function () {
                    if (currentMode === modes.drawer) {
                        module.debug('Collapse ignored in drawer mode');

                        return;
                    }
                    if ($module.hasClass(className.collapsed)) {
                        return;
                    }
                    module.debug('Collapsing navigation');
                    $module.addClass(className.collapsed);
                    if (currentMode === modes.full) {
                        module.save.state(true);
                    }
                    module.set.aria();
                    settings.onCollapse.call(element);
                },

                expand: function () {
                    if (currentMode === modes.drawer) {
                        module.debug('Expand ignored in drawer mode');

                        return;
                    }
                    if (!$module.hasClass(className.collapsed)) {
                        return;
                    }
                    module.debug('Expanding navigation');
                    module.unpin();
                    $module.removeClass(className.collapsed);
                    if (currentMode === modes.full) {
                        module.save.state(false);
                    }
                    module.set.aria();
                    settings.onExpand.call(element);
                },

                toggle: function () {
                    if (module.is.collapsed()) {
                        module.expand();
                    } else {
                        module.collapse();
                    }
                },

                toggleDrawer: function () {
                    if (module.is.drawerOpen()) {
                        module.hide.drawer();
                    } else {
                        module.show.drawer();
                    }
                },

                show: {
                    drawer: function () {
                        if (currentMode !== modes.drawer) {
                            module.debug('Drawer only exists in drawer mode');

                            return;
                        }
                        if (module.is.drawerOpen()) {
                            return;
                        }
                        module.debug('Showing drawer');
                        lastFocus = document.activeElement;
                        $module.addClass(className.open);
                        bodyOverflow = document.body.style.overflow;
                        document.body.style.overflow = 'hidden';
                        $main.prop('inert', true);
                        module.set.aria();
                        // La navegación ya es visible (visibility sin transición al abrir)
                        setTimeout(function () {
                            var
                                $focusable = module.get.focusable()
                            ;
                            if ($focusable.length > 0) {
                                $focusable.first().trigger('focus');
                            } else {
                                $navigation.attr('tabindex', '-1').trigger('focus');
                            }
                        }, 0);
                        settings.onDrawerShow.call(element);
                    },
                },

                hide: {
                    // returnFocus: false al navegar o al cambiar de modo
                    drawer: function (returnFocus) {
                        if (!module.is.drawerOpen()) {
                            return;
                        }
                        module.debug('Hiding drawer');
                        $module.removeClass(className.open);
                        document.body.style.overflow = bodyOverflow || '';
                        bodyOverflow = null;
                        $main.prop('inert', false);
                        module.set.aria();
                        if (returnFocus !== false) {
                            if (lastFocus && document.contains(lastFocus) && !$.contains($navigation[0], lastFocus)) {
                                lastFocus.focus();
                            } else {
                                $main.find(selector.drawerToggle).first().trigger('focus');
                            }
                        }
                        lastFocus = null;
                        settings.onDrawerHide.call(element);
                    },
                },

                unpin: function () {
                    $navigation.find(selector.item + '.' + className.pinned).removeClass(className.pinned);
                },

                trap: {
                    focus: function (event) {
                        var
                            $focusable = module.get.focusable(),
                            first      = $focusable.get(0),
                            last       = $focusable.get($focusable.length - 1)
                        ;
                        if ($focusable.length === 0) {
                            event.preventDefault();

                            return;
                        }
                        if (event.shiftKey && document.activeElement === first) {
                            event.preventDefault();
                            last.focus();
                        } else if (!event.shiftKey && document.activeElement === last) {
                            event.preventDefault();
                            first.focus();
                        }
                    },
                },

                set: {
                    aria: function () {
                        var
                            collapsed  = module.is.collapsed(),
                            open       = module.is.drawerOpen(),
                            controls   = $navigation.attr('id')
                        ;
                        $toggle.attr({
                            'aria-controls': controls,
                            'aria-expanded': String(!collapsed),
                            'aria-label': collapsed ? text.expand : text.collapse,
                        });
                        $drawerToggle.each(function () {
                            var
                                inside = $.contains($navigation[0], this)
                            ;
                            $(this).attr({
                                'aria-controls': controls,
                                'aria-expanded': String(open),
                                'aria-label': inside || open ? text.closeMenu : text.openMenu,
                            });
                        });
                    },
                },

                save: {
                    state: function (collapsed) {
                        preference = collapsed;
                        if (!settings.rememberState) {
                            return;
                        }
                        try {
                            window.localStorage.setItem(settings.storageKey, collapsed ? '1' : '0');
                        } catch (error) {
                            module.debug('Cannot store shell state', error);
                        }
                    },
                },

                get: {
                    mode: function () {
                        var
                            value = window.getComputedStyle(element).getPropertyValue(cssVars.mode).trim()
                        ;

                        return value === modes.rail || value === modes.drawer
                            ? value
                            : modes.full;
                    },
                    storedState: function () {
                        var
                            stored = null
                        ;
                        if (settings.rememberState) {
                            try {
                                stored = window.localStorage.getItem(settings.storageKey);
                            } catch (error) {
                                module.debug('Cannot read shell state', error);
                            }
                        }

                        return stored === null
                            ? $module.hasClass(className.collapsed)
                            : stored === '1';
                    },
                    focusable: function () {
                        return $navigation.find(selector.focusable).filter(function () {
                            return this.offsetWidth > 0 || this.offsetHeight > 0 || this === document.activeElement;
                        });
                    },
                },

                is: {
                    collapsed: function () {
                        return currentMode !== modes.drawer && $module.hasClass(className.collapsed);
                    },
                    drawer: function () {
                        return currentMode === modes.drawer;
                    },
                    drawerOpen: function () {
                        return $module.hasClass(className.open);
                    },
                    overlay: function () {
                        return currentMode === modes.rail && !$module.hasClass(className.collapsed);
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

    $.fn.shell.settings = {

        name: 'Shell',
        namespace: 'shell',

        silent: false,
        debug: false,
        verbose: false,
        performance: true,

        // Recordar en localStorage si la navegación está contraída (solo en escritorio)
        rememberState: false,
        storageKey: 'fomantic-shell-collapsed',
        // Cerrar el drawer al seguir un enlace de la navegación
        closeOnNavigate: true,
        // Esc cierra el drawer, los flotantes y la navegación superpuesta de tableta
        closeOnEscape: true,
        // Ctrl/⌘ + esta tecla enfoca el buscador de la barra (selector.search); false: sin atajo
        searchShortcut: 'k',
        // Ajustes del acordeón de la navegación si el shell lo inicializa (false: no tocarlo).
        // En el riel los títulos no lo abren: abren su flotante.
        accordion: {
            exclusive: false,
            // La transición de los hijos dejaría visible, en línea y con !important, la cabecera
            // del flotante (`.content > .header`), que solo existe para el riel
            animateChildren: false,
            selector: {
                ignore: '.ui.dropdown, .ui.collapsed.shell > .navigation .title',
            },
        },

        onCollapse: function () {},
        onExpand: function () {},
        onDrawerShow: function () {},
        onDrawerHide: function () {},

        error: {
            method: 'The method you called is not defined',
            noNavigation: 'Shell has no .navigation child',
        },

        className: {
            collapsed: 'collapsed',
            open: 'open',
            pinned: 'pinned',
            initializing: 'initializing',
        },

        cssVars: {
            mode: '--shell-mode',
        },

        modes: {
            full: 'full',
            rail: 'rail',
            drawer: 'drawer',
        },

        selector: {
            navigation: '.navigation',
            main: '.main',
            menu: '.ui.menu',
            accordion: '.ui.accordion',
            toggle: '.toggle',
            drawerToggle: '.drawer.toggle',
            item: '.item',
            title: '.title',
            link: 'a[href]',
            search: '.bar .ui.search .prompt, .bar input[type="search"]',
            focusable: 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        },

        text: {
            collapse: 'Collapse menu',
            expand: 'Expand menu',
            openMenu: 'Open menu',
            closeMenu: 'Close menu',
        },

    };
})(jQuery, window, document);
