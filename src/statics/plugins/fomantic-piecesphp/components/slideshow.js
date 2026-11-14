/* Fork: componente propio, no existe en Fomantic. */
/*!
 * # Fomantic-UI 0.13.0 - Slideshow
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

    $.fn.slideshow = function (parameters) {
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
                    ? $.extend(true, {}, $.fn.slideshow.settings, parameters)
                    : $.extend({}, $.fn.slideshow.settings),

                namespace       = settings.namespace,
                className       = settings.className,
                error           = settings.error,
                metadata        = settings.metadata,
                selector        = settings.selector,
                cssVars         = settings.cssVars,
                text            = settings.text,
                templates       = settings.templates,

                eventNamespace  = '.' + namespace,
                moduleNamespace = 'module-' + namespace,

                element         = this,
                instance        = $(this).data(moduleNamespace),

                $module         = $(this),
                $viewport,
                $slides,
                $slide,
                $previous,
                $next,
                $toggle,
                $indicators,
                $indicator,

                id              = Math.random().toString(16).slice(2, 10),
                currentIndex    = 0,
                timer           = null,
                hovered         = false,
                focused         = false,
                pointerStart    = null,
                module
            ;

            module = {

                initialize: function () {
                    module.verbose('Initializing slideshow', settings);
                    $module.addClass(className.initializing);
                    module.refresh();
                    if ($slide.length === 0) {
                        module.error(error.noSlides, element);
                    }
                    module.setup.layout();
                    module.setup.aria();
                    module.bind.events();
                    module.set.index(module.get.initialIndex());
                    if (module.is.autoplay()) {
                        module.play();
                    } else {
                        module.set.live();
                    }
                    module.instantiate();
                    // Sin transición hasta que la primera posición está pintada
                    setTimeout(function () {
                        $module.removeClass(className.initializing);
                    }, 0);
                },

                instantiate: function () {
                    module.verbose('Instantiating module', settings);
                    instance = module;
                    $module.data(moduleNamespace, module);
                },

                destroy: function () {
                    module.verbose('Destroying previous instance', instance);
                    module.stop.timer();
                    module.remove.events();
                    $module.removeData(moduleNamespace);
                },

                refresh: function () {
                    $viewport = $module.children(selector.viewport);
                    $slides = $module.find(selector.slides).first();
                    $slide = $slides.children(selector.slide);
                    $previous = $module.children(selector.previous);
                    $next = $module.children(selector.next);
                    $toggle = $module.children(selector.toggle);
                    $indicators = $module.find(selector.indicators).first();
                    $indicator = $indicators.children(selector.indicator);
                },

                setup: {
                    layout: function () {
                        if ($viewport.length === 0 && $slides.length > 0) {
                            module.debug('Wrapping slides in a viewport');
                            $slides.wrap(templates.viewport(className.viewport));
                        }
                        if (settings.controls && $previous.length === 0) {
                            $module.append(templates.control(className.previous, settings.icons.previous));
                        }
                        if (settings.controls && $next.length === 0) {
                            $module.append(templates.control(className.next, settings.icons.next));
                        }
                        if (module.is.autoplay() && settings.toggle && $toggle.length === 0) {
                            $module.append(templates.control(className.toggle, settings.icons.pause));
                        }
                        if (settings.indicators && $indicators.length === 0) {
                            $module.append(templates.indicators($slide.length, className));
                        }
                        module.refresh();
                    },
                    aria: function () {
                        var
                            slidesId = $slides.attr('id') || namespace + '-' + id
                        ;
                        $module.attr({
                            role: 'region',
                            'aria-roledescription': text.roleDescription,
                        });
                        if (!$module.attr('aria-label') && !$module.attr('aria-labelledby')) {
                            $module.attr('aria-label', text.label);
                        }
                        $slides.attr('id', slidesId);
                        $slide.each(function (index) {
                            var
                                $currentSlide = $(this)
                            ;
                            $currentSlide.attr({
                                role: 'group',
                                'aria-roledescription': text.slideRoleDescription,
                            });
                            if (!$currentSlide.attr('aria-label')) {
                                $currentSlide.attr('aria-label', module.get.text(text.slide, index));
                            }
                        });
                        $previous.add($next).add($toggle).filter('button').attr('type', 'button');
                        $previous.attr({ 'aria-controls': slidesId, 'aria-label': text.previous });
                        $next.attr({ 'aria-controls': slidesId, 'aria-label': text.next });
                        $toggle.attr('aria-controls', slidesId);
                        $indicator.each(function (index) {
                            $(this).attr({
                                'aria-controls': slidesId,
                                'aria-label': module.get.text(text.indicator, index),
                            }).filter('button').attr('type', 'button');
                        });
                    },
                },

                bind: {
                    events: function () {
                        module.verbose('Binding events');
                        $module
                            .on('click' + eventNamespace, selector.previous, module.event.previous)
                            .on('click' + eventNamespace, selector.next, module.event.next)
                            .on('click' + eventNamespace, selector.toggle, module.event.toggle)
                            .on('click' + eventNamespace, selector.indicator, module.event.indicator)
                            .on('mouseenter' + eventNamespace, module.event.mouseenter)
                            .on('mouseleave' + eventNamespace, module.event.mouseleave)
                            .on('focusin' + eventNamespace, module.event.focusin)
                            .on('focusout' + eventNamespace, module.event.focusout)
                            .on('pointerdown' + eventNamespace, module.event.pointerdown)
                            .on('pointerup' + eventNamespace, module.event.pointerup)
                        ;
                        if (settings.keyboard) {
                            $module.on('keydown' + eventNamespace, module.event.keydown);
                        }
                    },
                },

                remove: {
                    events: function () {
                        module.verbose('Removing events');
                        $module.off(eventNamespace);
                    },
                },

                event: {
                    previous: function (event) {
                        event.preventDefault();
                        module.previous();
                    },
                    next: function (event) {
                        event.preventDefault();
                        module.next();
                    },
                    toggle: function (event) {
                        event.preventDefault();
                        if (module.is.playing()) {
                            module.pause();
                        } else {
                            module.play();
                        }
                    },
                    indicator: function (event) {
                        event.preventDefault();
                        module.goTo($indicator.index(this));
                    },
                    keydown: function (event) {
                        var
                            tag = (event.target.tagName || '').toLowerCase()
                        ;
                        if (tag === 'input' || tag === 'textarea' || tag === 'select' || event.target.isContentEditable) {
                            return;
                        }
                        switch (event.key) {
                            case 'ArrowLeft': {
                                module.previous();

                                break;
                            }
                            case 'ArrowRight': {
                                module.next();

                                break;
                            }
                            case 'Home': {
                                module.goTo(0);

                                break;
                            }
                            case 'End': {
                                module.goTo($slide.length - 1);

                                break;
                            }
                            default: {
                                return;
                            }
                        }
                        event.preventDefault();
                    },
                    mouseenter: function () {
                        hovered = true;
                        if (settings.pauseOnHover) {
                            module.stop.timer();
                            module.set.live();
                        }
                    },
                    mouseleave: function () {
                        hovered = false;
                        module.resume();
                    },
                    focusin: function () {
                        focused = true;
                        if (settings.pauseOnFocus) {
                            module.stop.timer();
                            module.set.live();
                        }
                    },
                    focusout: function (event) {
                        if (!event.relatedTarget || !$.contains(element, event.relatedTarget)) {
                            focused = false;
                            module.resume();
                        }
                    },
                    pointerdown: function (event) {
                        pointerStart = event.pointerType === 'mouse' ? null : event.clientX;
                    },
                    pointerup: function (event) {
                        var
                            distance = pointerStart === null ? 0 : event.clientX - pointerStart
                        ;
                        pointerStart = null;
                        if (Math.abs(distance) >= settings.swipeThreshold) {
                            if (distance < 0) {
                                module.next();
                            } else {
                                module.previous();
                            }
                        }
                    },
                },

                next: function () {
                    var
                        index = currentIndex + 1
                    ;
                    if (index >= $slide.length) {
                        if (!settings.loop) {
                            return;
                        }
                        index = 0;
                    }
                    module.goTo(index);
                },

                previous: function () {
                    var
                        index = currentIndex - 1
                    ;
                    if (index < 0) {
                        if (!settings.loop) {
                            return;
                        }
                        index = $slide.length - 1;
                    }
                    module.goTo(index);
                },

                goTo: function (index) {
                    var
                        previousIndex = currentIndex
                    ;
                    index = parseInt(index, 10);
                    if (Number.isNaN(index) || index < 0 || index >= $slide.length) {
                        module.error(error.index, index);

                        return;
                    }
                    if (index === currentIndex) {
                        return;
                    }
                    if (settings.onBeforeChange.call(element, index, previousIndex) === false) {
                        module.debug('Change cancelled by onBeforeChange', index);

                        return;
                    }
                    module.set.index(index);
                    settings.onChange.call(element, index, previousIndex);
                    if (module.is.rotating()) {
                        module.start.timer();
                    }
                },

                play: function () {
                    module.debug('Starting autoplay');
                    $module.addClass(className.playing);
                    module.set.toggle(true);
                    module.set.live();
                    if (module.is.rotating()) {
                        module.start.timer();
                    }
                },

                pause: function () {
                    module.debug('Pausing autoplay');
                    module.stop.timer();
                    $module.removeClass(className.playing);
                    module.set.toggle(false);
                    module.set.live();
                },

                resume: function () {
                    module.set.live();
                    if (module.is.rotating()) {
                        module.start.timer();
                    }
                },

                start: {
                    timer: function () {
                        module.stop.timer();
                        timer = setTimeout(function () {
                            timer = null;
                            if (!settings.loop && currentIndex >= $slide.length - 1) {
                                module.pause();

                                return;
                            }
                            module.next();
                        }, module.get.interval());
                    },
                },

                stop: {
                    timer: function () {
                        clearTimeout(timer);
                        timer = null;
                    },
                },

                set: {
                    index: function (index) {
                        currentIndex = index;
                        if ($slides.length > 0) {
                            $slides[0].style.setProperty(cssVars.index, String(index));
                        }
                        $slide
                            .removeClass(className.active)
                            .attr({ 'aria-hidden': 'true', inert: '' })
                            .eq(index)
                            .addClass(className.active)
                            .removeAttr('aria-hidden inert')
                        ;
                        $indicator
                            .removeClass(className.active)
                            .removeAttr('aria-current')
                            .eq(index)
                            .addClass(className.active)
                            .attr('aria-current', 'true')
                        ;
                        if (!settings.loop) {
                            module.set.disabled($previous, index === 0);
                            module.set.disabled($next, index === $slide.length - 1);
                        }
                    },
                    disabled: function ($control, disabled) {
                        $control.toggleClass(className.disabled, disabled);
                        if ($control.is('button')) {
                            $control.prop('disabled', disabled);
                        } else {
                            $control.attr('aria-disabled', disabled ? 'true' : 'false');
                        }
                    },
                    live: function () {
                        // Mientras rota sola no se anuncia cada cambio (WAI-ARIA APG, carrusel)
                        $slides.attr('aria-live', module.is.rotating() ? 'off' : 'polite');
                    },
                    toggle: function (playing) {
                        $toggle
                            .attr('aria-label', playing ? text.pause : text.play)
                            .html(templates.icon(playing ? settings.icons.pause : settings.icons.play))
                        ;
                    },
                },

                get: {
                    index: function () {
                        return currentIndex;
                    },
                    total: function () {
                        return $slide.length;
                    },
                    initialIndex: function () {
                        var
                            activeIndex = $slide.index($slide.filter('.' + className.active).first())
                        ;

                        return activeIndex >= 0 ? activeIndex : Math.min(settings.startIndex, Math.max($slide.length - 1, 0));
                    },
                    interval: function () {
                        var
                            interval = parseInt($module.data(metadata.interval), 10)
                        ;

                        return interval > 0 ? interval : settings.interval;
                    },
                    text: function (template, index) {
                        return String(template)
                            .replace('{index}', index + 1)
                            .replace('{total}', $slide.length)
                        ;
                    },
                },

                is: {
                    autoplay: function () {
                        var
                            value = $module.data(metadata.autoplay)
                        ;

                        return value !== undefined ? value !== false && value !== 'false' : settings.autoplay;
                    },
                    playing: function () {
                        return $module.hasClass(className.playing);
                    },
                    rotating: function () {
                        return module.is.playing()
                            && !(hovered && settings.pauseOnHover)
                            && !(focused && settings.pauseOnFocus);
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

    $.fn.slideshow.settings = {

        name: 'Slideshow',
        namespace: 'slideshow',

        silent: false,
        debug: false,
        verbose: false,
        performance: true,

        // Diapositiva inicial si ninguna lleva la clase active
        startIndex: 0,
        // Volver al principio tras la última
        loop: true,
        // Rotación automática (también data-autoplay) y su intervalo en ms (data-interval)
        autoplay: false,
        interval: 5000,
        pauseOnHover: true,
        pauseOnFocus: true,
        // Botón de reproducir/pausar cuando hay autoplay (necesario para WCAG 2.2.2)
        toggle: true,
        // Generar controles y puntos si el marcado no los trae
        controls: true,
        indicators: true,
        // Flechas, Inicio y Fin con el foco dentro
        keyboard: true,
        // Distancia mínima en px para pasar con el dedo
        swipeThreshold: 40,

        onBeforeChange: function (index, previousIndex) {},
        onChange: function (index, previousIndex) {},

        error: {
            method: 'The method you called is not defined',
            noSlides: 'Slideshow has no slides',
            index: 'Slide index out of range',
        },

        metadata: {
            autoplay: 'autoplay',
            interval: 'interval',
        },

        className: {
            active: 'active',
            disabled: 'disabled',
            initializing: 'initializing',
            playing: 'playing',
            viewport: 'viewport',
            previous: 'previous',
            next: 'next',
            toggle: 'toggle',
            indicators: 'indicators',
            indicator: 'indicator',
        },

        cssVars: {
            index: '--slideshow-index',
        },

        selector: {
            viewport: '.viewport',
            slides: '.slides',
            slide: '.slide',
            previous: '.previous.control',
            next: '.next.control',
            toggle: '.toggle.control',
            indicators: '.indicators',
            indicator: '.indicator',
        },

        icons: {
            previous: 'chevron left',
            next: 'chevron right',
            play: 'play',
            pause: 'pause',
        },

        text: {
            label: 'Slideshow',
            roleDescription: 'carousel',
            slideRoleDescription: 'slide',
            slide: '{index} of {total}',
            indicator: 'Go to slide {index}',
            previous: 'Previous slide',
            next: 'Next slide',
            play: 'Start slide rotation',
            pause: 'Stop slide rotation',
        },

        templates: {
            deQuote: function (string, encode) {
                return String(string).replace(/"/g, encode ? '&quot;' : '');
            },
            icon: function (iconClass) {
                return '<i class="' + $.fn.slideshow.settings.templates.deQuote(iconClass) + ' icon" aria-hidden="true"></i>';
            },
            viewport: function (viewportClass) {
                return '<div class="' + viewportClass + '"></div>';
            },
            control: function (controlClass, iconClass) {
                return '<button type="button" class="' + controlClass + ' control">'
                    + $.fn.slideshow.settings.templates.icon(iconClass)
                    + '</button>';
            },
            indicators: function (total, classNames) {
                var
                    html = '<div class="' + classNames.indicators + '">',
                    index = 0
                ;
                while (index < total) {
                    html += '<button type="button" class="' + classNames.indicator + '"></button>';
                    index++;
                }

                return html + '</div>';
            },
        },

    };
})(jQuery, window, document);
