var UtilPieces = /** @class */ (function () {
    function UtilPieces() {
        this.nodosEliminar = [];
        this.date = /** @class */ (function () {
            function class_1() {
            }
            /**
             * @method formatDate
             *
             * Formatea una fecha.
             *
             * @param {Date} date Fecha
             * @param {string} format Formato de la fecha y = año, m = mes, d = día
             * @returns {string}
             */
            class_1.formatDate = function (date, format) {
                if (format === void 0) { format = 'y-m-d'; }
                var day = day < date.getDate() ? '0' + date.getDate().toString() : date.getDate().toString();
                var month = (date.getMonth() + 1) < 10 ? '0' + (date.getMonth() + 1) : (date.getMonth() + 1).toString();
                var year = date.getFullYear().toString();
                format = format.replace('y', year);
                format = format.replace('m', month);
                format = format.replace('d', day);
                return format;
            };
            /**
             * @method reformatStringDate
             *
             * Reformatea una fecha.
             *
             * @param {string} dateString String que representa la fecha
             * @param {string} format Formato actual del string de la fecha y = año, m = mes, d = día
             * @param {string} separator Separador actual del string de la fecha
             * @param {string} newFormat Formato deseado del string de la fecha y = año, m = mes, d = día
             * @returns {string}
             */
            class_1.reformatStringDate = function (dateString, format, separator, newFormat) {
                if (format === void 0) { format = 'y-m-d'; }
                if (separator === void 0) { separator = '-'; }
                if (newFormat === void 0) { newFormat = 'y-m-d'; }
                var dateParts = dateString.split(separator);
                var formatParts = format.split(separator);
                var year = dateParts[formatParts.indexOf('y')];
                var month = dateParts[formatParts.indexOf('m')];
                var day = dateParts[formatParts.indexOf('d')];
                newFormat = newFormat.replace('y', year);
                newFormat = newFormat.replace('m', month);
                newFormat = newFormat.replace('d', day);
                return newFormat;
            };
            return class_1;
        }());
        this.number = /** @class */ (function () {
            function class_2() {
            }
            /**
            * @method getMilisegundos
            *
            * Retorna la cantidad en milisegundos del número proporcionado
            * según la magnitud escogida.
            * s = segundos
            * m = minutos
            * h = horas
            *
            * @param {Number} numero El número que representa la cantidad deseada según la magnitud escogida
            * @param {String} magnitud 's'|'m'|'h' (Si se escoge un valor diferente por defecto asignara segundos)
            * @returns {Number} La cantidad en milisegundos
            */
            class_2.getMilisegundos = function (numero, magnitud) {
                if (numero === void 0) { numero = 1; }
                if (magnitud === void 0) { magnitud = 's'; }
                var milisegundos = numero * 1000;
                switch (magnitud) {
                    case 's':
                        return milisegundos;
                    case 'm':
                        return milisegundos * 60;
                    case 'h':
                        return milisegundos * 60 * 60;
                    default:
                        return milisegundos;
                }
            };
            /**
             * @method numberFormat
             *
             * @description Formatea un número
             *
             * @param {number} number Número para formatear
             * @param {number} decimals Cantidad de decimales
             * @param {string} decimal_separator Separador de decimales
             * @param {string} thousands_separator Separador de miles
             */
            class_2.numberFormat = function (number, decimals, decimal_separator, thousands_separator) {
                // Strip all characters but numerical ones.
                var numberString = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                var n = !isFinite(+numberString) ? 0 : +numberString, prec = !isFinite(+decimals) ? 0 : Math.abs(decimals), sep = (typeof thousands_separator === 'undefined') ? ',' : thousands_separator, dec = (typeof decimal_separator === 'undefined') ? '.' : decimal_separator, s = [], toFixedFix = function (n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
                // Fix for IE parseFloat(0.55).toFixed(0) = 0;
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            };
            /**
             * @method parseNumber
             *
             * @description Convierte un string en un número
             *
             * @param {number} number String del número
             * @param {string} decimal_separator Separador de decimales
             * @param {string} thousands_separator Separador de miles
             * @param {bool} float Establece si devolverá un float o un entero
             */
            class_2.parseNumber = function (number, decimal_separator, thousands_separator, float) {
                if (decimal_separator === void 0) { decimal_separator = ','; }
                if (thousands_separator === void 0) { thousands_separator = '.'; }
                if (float === void 0) { float = true; }
                var numberString = number.toString();
                while (numberString.lastIndexOf(thousands_separator) != -1) {
                    numberString = numberString.replace(thousands_separator, '');
                }
                numberString = numberString.replace(decimal_separator, '.');
                if (float === true) {
                    return parseFloat(numberString);
                }
                else {
                    return parseInt(numberString);
                }
            };
            return class_2;
        }());
        this.components = /** @class */ (function () {
            function class_3() {
            }
            /**
             * @method configContadores
             *
             * @description Contador animado
             * Nota: el elemento debe tener el atributo data-counter con el número al que llegará
             * el contador.
             * Ejemplo:
             *
             * <span class='contador' data-counter='10000'>0</span>
             *
             * @param {string} selector Selector del elemento HTML del contador
             * @param {number} step Cantidad que aumentará cada ciclo
             * @param {number} decimals Cantidad de decimales
             * @param {string} decimal_separator Separador de decimales
             * @param {string} thousands_separator Separador de miles
             * @param {number} delay Retraso de los ciclos en milisegundos
             */
            class_3.configContadores = function (selector, step, decimals, decimal_separator, thousands_separator, delay) {
                if (step === void 0) { step = 1; }
                if (decimals === void 0) { decimals = 0; }
                if (decimal_separator === void 0) { decimal_separator = ','; }
                if (thousands_separator === void 0) { thousands_separator = '.'; }
                if (delay === void 0) { delay = 1; }
                var util = new UtilPieces();
                var contadores = document.querySelectorAll(selector);
                if (contadores.length > 0) {
                    for (var _i = 0, contadores_1 = contadores; _i < contadores_1.length; _i++) {
                        var element = contadores_1[_i];
                        var cantidad = parseFloat(element.getAttribute('data-counter'));
                        var interval = setInterval(function () {
                            var actual = util.number.parseNumber(parseInt(element.innerHTML));
                            var nuevoValor = (actual + step).toString();
                            if (actual < cantidad) {
                                if (parseInt(nuevoValor) > cantidad) {
                                    nuevoValor = util.number.numberFormat(cantidad, decimals, decimal_separator, thousands_separator);
                                }
                                else {
                                    nuevoValor = util.number.numberFormat(parseInt(nuevoValor), decimals, decimal_separator, thousands_separator);
                                }
                                element.innerHTML = nuevoValor;
                            }
                            else {
                                clearInterval(interval);
                            }
                        }, delay);
                    }
                }
            };
            /**
             * @method slider
             *
             * @description Slider (usa jquery)
             *
             * Nota: el elemento debe tener el atributo data-id al igual que los botones de navegación
             * <div class="slider">
                    <div class="nav-slider">
                        <span data-id="1" class="active"></span>
                        <span data-id="2"></span>
                        <span data-id="3"></span>
                    </div>
                    <div data-id="1" class="elemento-slider active">
                        <img src="src/image.png">
                    </div>
                    <div data-id="2" class="elemento-slider">
                        <img src="src/image.png">
                    </div>
                    <div data-id="3" class="elemento-slider">
                        <img src="src/image.png">
                    </div>
                </div>
             * Ejemplo:
             *
             * @param {HTMLElement|String|JQuery} slider
             * @param {*} delay
             * @param {*} navItemSelector
             * @param {*} elementSliderSelector
             * @param {*} activeClass
             * @returns {void}
             */
            class_3.slider = function (slider, delay, navItemSelector, elementSliderSelector, activeClass) {
                var _this = this;
                if (delay === void 0) { delay = 2500; }
                if (navItemSelector === void 0) { navItemSelector = '.nav-slider span'; }
                if (elementSliderSelector === void 0) { elementSliderSelector = '.elemento-slider'; }
                if (activeClass === void 0) { activeClass = 'active'; }
                var $;
                if (!('sliderNS' in window)) {
                    window['sliderNS'] = {};
                }
                slider = $(slider);
                window['sliderNS'] = {
                    nav_icons: slider.find(navItemSelector),
                    elementos: slider.find(elementSliderSelector),
                    last_nav: 0,
                    interval: null,
                    debug: false
                };
                window['sliderNS'].interval = setInterval(function () {
                    if (window['sliderNS'].last_nav < window['sliderNS'].nav_icons.length) {
                        window['sliderNS'].nav_icons[window['sliderNS'].last_nav].click();
                        window['sliderNS'].last_nav++;
                    }
                    else {
                        window['sliderNS'].last_nav = 0;
                    }
                }, delay);
                window['sliderNS'].nav_icons.click(function (e) {
                    e.stopPropagation();
                    var id = $(_this).attr("data-id");
                    if (!$(_this).hasClass(activeClass)) {
                        window['sliderNS'].nav_icons.each(function (e) {
                            if ($(_this).attr('data-id') == id) {
                                $(_this).addClass(activeClass);
                                clearInterval(window['sliderNS'].interval);
                                window['sliderNS'].last_nav = id;
                                window['sliderNS'].interval = setInterval(function () {
                                    if (window['sliderNS'].last_nav < window['sliderNS'].nav_icons.length) {
                                        window['sliderNS'].nav_icons[window['sliderNS'].last_nav].click();
                                        window['sliderNS'].last_nav++;
                                    }
                                    else {
                                        window['sliderNS'].last_nav = 0;
                                    }
                                }, delay);
                            }
                            else {
                                $(_this).removeClass(activeClass);
                            }
                        });
                        window['sliderNS'].elementos.each(function (e) {
                            if ($(_this).attr('data-id') == id) {
                                $(_this).addClass(activeClass);
                            }
                            else {
                                $(_this).removeClass(activeClass);
                            }
                        });
                    }
                });
            };
            return class_3;
        }());
        this.file = /** @class */ (function () {
            function class_4() {
            }
            /**
             * @method base64ToBlob
             *
             * Convierte una cadena base64 en un blob
             *
             * @param {string} dataURL Una cadena base64 o tipo dataUri que contiene la información del archivo
             * @returns {Blob}
             */
            class_4.dataURLToBlob = function (dataURL) {
                var util = new UtilPieces();
                dataURL = dataURL.toString();
                var fileData = util.string.proccessDataURL(dataURL);
                var fileString = fileData.string;
                var fileType = fileData.mime;
                var fileBytes = util.string.stringToBytes(fileString).dec;
                return new Blob([new Uint8Array(fileBytes)], { type: fileType });
            };
            /**
             * @method dataURLToFile
             *
             * @param {string} dataURL
             * @param {string} filename
             * @returns {File}
             */
            class_4.dataURLToFile = function (dataURL, filename) {
                if (filename === void 0) { filename = 'name'; }
                var util = new UtilPieces();
                var info = util.string.proccessDataURL(dataURL);
                var mime = info.mime;
                var bytes = util.string.stringToBytesUnclear(info.string).dec;
                var file = new File([new Blob([new Uint8Array(bytes)])], filename, { type: mime });
                return file;
            };
            return class_4;
        }());
        this.string = /** @class */ (function () {
            function class_5() {
            }
            /**
             * @method proccessDataURL
             *
             * Devuelve un objeto con el archivo en base64, el tipo mime y el dataURL recibido.
             *
             * {b64:dataURL, mime:...,string:file...}
             *
             * @param {String} dataURL Data url de un archivo
             * @returns {{ b64: string, mime: string, string: string }}
             */
            class_5.proccessDataURL = function (dataURL) {
                var mimeType = dataURL.replace(/data\:(.*)\;base64\,.*/g, '$1');
                var fileString = window.atob(dataURL.replace(/data\:.*base64\,(.*)/g, '$1'));
                return {
                    b64: dataURL,
                    mime: mimeType,
                    string: fileString
                };
            };
            /**
             * @method stringClear
             *
             * Elimina espacios y pasa todo a minúsculas.
             *
             * @param {string} str
             * @returns {string}
             */
            class_5.stringClear = function (str) {
                return (new String(str).replace(/\s*/g, ''));
            };
            /**
             * @method stringLowerClear
             *
             * Elimina espacios y pasa todo a minúsculas.
             *
             * @param {string} str
             * @returns {string}
             */
            class_5.stringLowerClear = function (str) {
                return this.stringClear(str).toLowerCase();
            };
            /**
             * @method trim
             *
             * Elimina espacios y pasa todo a minúsculas.
             *
             * @param {string} str
             * @returns {string}
             */
            class_5.trim = function (str) {
                return (new String(str).trim());
            };
            /**
             * @method stringToBytesToLower
             *
             * Devuelve un objeto con arrays de los códigos en DEC y en HEX de cada
             * carácter de una cadena previamente transformado en minúsculas.
             *
             * @param {string} string
             * @param {boolean} verbose
             * @returns {dec: Array<number>, hex: Array<string>}
             */
            class_5.stringToBytesToLower = function (string, verbose) {
                if (verbose === void 0) { verbose = false; }
                string = string.toString().replace(/(\r|\n|\t)*/gim, '').trim().toLowerCase();
                var lengthString = string.length;
                var bytesDec = [];
                var bytesHex = [];
                if (verbose)
                    console.log('============');
                for (var i = 0; i < lengthString; i++) {
                    var charCode = string.charCodeAt(i);
                    bytesDec[i] = charCode;
                    bytesHex[i] = charCode.toString(16);
                    if (verbose)
                        console.log(string[i] + '===' + charCode + '===' + bytesHex[i]);
                }
                if (verbose)
                    console.log('============');
                return { dec: bytesDec, hex: bytesHex };
            };
            /**
             * @method stringToBytes
             *
             * Devuelve un objeto con arrays de los códigos en DEC y en HEX de cada
             * carácter de una cadena. Elimina \r, \n, \t y espacios sobrantes a
             * la cadena de entrada.
             *
             * @param {String} string
             * @param {Boolean} trim
             * @param {Boolean} verbose
             * @returns {dec: Array<number>, hex: Array<string>}
             */
            class_5.stringToBytes = function (string, trim, verbose) {
                if (trim === void 0) { trim = false; }
                if (verbose === void 0) { verbose = false; }
                if (trim === true) {
                    string = string.toString().replace(/(\r|\n|\t)*/gim, '').trim();
                }
                var lengthString = string.length;
                var bytesDec = [];
                var bytesHex = [];
                if (verbose)
                    console.log('============');
                for (var i = 0; i < lengthString; i++) {
                    var charCode = string.charCodeAt(i);
                    bytesDec[i] = charCode;
                    bytesHex[i] = charCode.toString(16);
                    if (verbose)
                        console.log(string[i] + '===' + charCode + '===' + bytesHex[i]);
                }
                if (verbose)
                    console.log('============');
                return { dec: bytesDec, hex: bytesHex };
            };
            /**
             * @method stringToBytesUnclear
             *
             * Devuelve un objeto con arrays de los códigos en DEC y en HEX de cada
             * carácter de una cadena. No modifica la cadena de entrada.
             *
             * @param {String} string
             * @param {Boolean} verbose
             * @returns {Object}
             */
            class_5.stringToBytesUnclear = function (string, verbose) {
                if (verbose === void 0) { verbose = false; }
                var lengthString = string.length;
                var bytesDec = [];
                var bytesHex = [];
                if (verbose)
                    console.log('============');
                for (var i = 0; i < lengthString; i++) {
                    var charCode = string.charCodeAt(i);
                    bytesDec[i] = charCode;
                    bytesHex[i] = charCode.toString(16);
                    if (verbose)
                        console.log(string[i] + '===' + charCode + '===' + bytesHex[i]);
                }
                if (verbose)
                    console.log('============');
                return { dec: bytesDec, hex: bytesHex };
            };
            /**
             * @method arrayToString
             *
             * Toma un array y lo convierte en una cadena
             * con la extensión determinada.
             *
             * @param {Array<string>} array Array a convertir
             * @param {number} length Tamaño de la cadena retornada
             * @returns {string}
             */
            class_5.arrayToString = function (array, length) {
                var string = '';
                var arrayLength = array.length;
                length = arrayLength >= length ? length : arrayLength;
                for (var i = 0; i < arrayLength; i++) {
                    string += array[i];
                }
                string = string.substring(0, length);
                return string.substring(0, length);
            };
            return class_5;
        }());
        this.validate = /** @class */ (function () {
            function class_6() {
            }
            /**
             * @method mimeType
             *
             * Valida el tipo de un archivo
             *
             * Tipos implementados para el parámetro allowed:
             * xlsx, pdf, html, jpg, jpeg, png
             *
             * @param {string} dataURL DataURL leído por FileReader
             * @param {Array<string>} allowed Array con los formatos permitidos
             * @param {boolean} verbose Mensajes de información
             * @returns {boolean} Devuelve true si el tipo del archivo pasado corresponde a algunos de los definidos
             * en allowed, de lo contrario devuelve false.
             */
            class_6.mimeType = function (dataURL, allowed, verbose) {
                if (allowed === void 0) { allowed = []; }
                if (verbose === void 0) { verbose = false; }
                var util = new UtilPieces();
                var signaturesList = {
                    xlsx: [
                        util.string.arrayToString(util.string.stringToBytes('PK..', true).hex, ('PK..').length)
                    ],
                    pdf: [
                        util.string.arrayToString(util.string.stringToBytes('%PDF-', true).hex, ('%PDF-').length)
                    ],
                    html: [
                        util.string.arrayToString(util.string.stringToBytes('<!DOCTYPE HTML', true).hex, ('<!DOCTYPE HTML').length)
                    ],
                    jpg: [
                        util.string.arrayToString(util.string.stringToBytes('ÿØÿà ..J', true).hex, ('ÿØÿà ..J').length),
                        util.string.arrayToString(util.string.stringToBytes('F IF..', true).hex, ('F IF..').length)
                    ],
                    png: [
                        util.string.arrayToString(['89', '70', '6e', '67', '1a', '0'], ['89', '70', '6e', '67', '1a', '0'].length)
                    ]
                };
                var mimeSignatures = {
                    xlsx: {
                        string: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        extension: '.xlsx',
                        signatures: signaturesList.xlsx
                    },
                    pdf: {
                        string: 'application/pdf',
                        extension: '.pdf',
                        signatures: signaturesList.pdf
                    },
                    html: {
                        string: 'text/html',
                        extension: '.html',
                        signatures: signaturesList.html
                    },
                    jpg: {
                        string: 'image/jpeg',
                        extension: '.jpg',
                        signatures: signaturesList.jpg
                    },
                    jpeg: {
                        string: 'image/jpeg',
                        extension: '.jpeg',
                        signatures: signaturesList.jpg
                    },
                    png: {
                        string: 'image/png',
                        extension: '.png',
                        signatures: signaturesList.png
                    },
                };
                var data = util.string.proccessDataURL(dataURL);
                var bytes = util.string.stringToBytes(data.string, true).hex;
                for (var i in mimeSignatures) {
                    var mime = mimeSignatures[i];
                    var signatures = mime.signatures;
                    for (var j in signatures) {
                        var signature = signatures[j];
                        var header = util.string.arrayToString(bytes, signature.length);
                        if (verbose === true) {
                            var verboseStrings = [];
                            verboseStrings[0] = "Comparing headers '${header}' == ${signature} result: " + (header == signature);
                            verboseStrings[0] = verboseStrings[0].replace('${header}', header).replace('${signature}', signature);
                            verboseStrings[1] = "Comparing mime type '${data.mime}' == ${mime.string} result: " + (data.mime == mime.string);
                            verboseStrings[1] = verboseStrings[1].replace('${data.mime}', data.mime).replace('${mime.string}', mime.string);
                            verboseStrings[2] = 'Valid: ' + (header == signature && data.mime == mime.string);
                            console.log(verboseStrings);
                        }
                        if (header == signature && data.mime == mime.string) {
                            if (allowed.indexOf(i) > -1) {
                                return true;
                            }
                        }
                    }
                }
                return false;
            };
            return class_6;
        }());
        this.uri = /** @class */ (function () {
            function class_7() {
            }
            /**
             * @method getURIPart
             *
             * @description Obtiene una parte de la URI actual
             *
             * @param {number} part El segmento deseado de la URI actual (Si es 0, devolverá la última parte)
             *
             * @returns {string} El segmento deseado
             */
            class_7.getURIPart = function (part) {
                if (part === void 0) { part = 0; }
                var split_url = window.location.pathname.split('/');
                split_url = (function () {
                    var tmp = [];
                    for (var i = 0; i < split_url.length; i++) {
                        if (split_url[i].length > 0) {
                            tmp.push(split_url[i]);
                        }
                    }
                    return tmp;
                })();
                var partes = split_url.length;
                part = part <= partes ? (part === 0 ? partes - 1 : part - 1) : partes - 1;
                return split_url[part];
            };
            return class_7;
        }());
        this.html = /** @class */ (function () {
            function class_8() {
            }
            /**
            * @method createElement
            *
            * Acortamiento de document.createElement
            * @param {string} tag
            * @param {ElementCreationOptions} options
            * @returns {HTMLElement}
            */
            class_8.createElement = function (tag, options) {
                if (options === void 0) { options = null; }
                return document.createElement(tag, options);
            };
            /**
             * @method createTagInnerHTML
             *
             * Crea un elemento y le asigna un innerHTML
             * @param {string} tag
             * @param {string} innerHTML
             * @param {ElementCreationOptions} options
             * @returns {HTMLElement}
             */
            class_8.createTagInnerHTML = function (tag, innerHTML, options) {
                if (innerHTML === void 0) { innerHTML = ''; }
                if (options === void 0) { options = null; }
                var element = this.createElement(tag, options);
                element.innerHTML = innerHTML;
                return element;
            };
            /**
             * @method create
             *
             * @param {string} tag Etiquete HTML
             * @param {Array<{attr:string,value:string}>} attributes Array con objectos {attr:'',value:''} para agregar
             * atributos
             * @param {HTMLElement|HTMLElement[]} childs Elementos hijo
             * @param {string} innerHTML innerHTML introducido antes de agregar el hijo
             * @param {ElementCreationOptions} options
             * @returns {HTMLElement}
             */
            class_8.create = function (tag, attributes, childs, innerHTML, options) {
                if (attributes === void 0) { attributes = null; }
                if (childs === void 0) { childs = null; }
                if (innerHTML === void 0) { innerHTML = ''; }
                if (options === void 0) { options = null; }
                var element = this.createTagInnerHTML(tag, innerHTML, options);
                for (var _i = 0, attributes_1 = attributes; _i < attributes_1.length; _i++) {
                    var i = attributes_1[_i];
                    if (typeof i.value == 'string') {
                        element.setAttribute(i.attr, i.value.trim());
                    }
                    else if (Array.isArray(i.value)) {
                        element.setAttribute(i.attr, i.value.join(' ').trim());
                    }
                }
                if (childs !== null) {
                    if (Array.isArray(childs)) {
                        for (var _a = 0, childs_1 = childs; _a < childs_1.length; _a++) {
                            var i = childs_1[_a];
                            element.appendChild(i);
                        }
                    }
                    else {
                        element.appendChild(childs);
                    }
                }
                return element;
            };
            return class_8;
        }());
    }
    /**
     * @method clearTextNodes
     *
     * @description Elimina los nodos de texto vacíos y comentarios
     *
     * @return {void}
     */
    UtilPieces.prototype.clearTextNodes = function () {
        var elementos = document.getElementsByTagName('*');
        for (var k = 0; k < elementos.length; k++) {
            for (var i = 0; i < elementos[k].childNodes.length; i++) {
                var hijo = elementos[k].childNodes[i];
                if ((hijo.nodeType == 3 && !/\S/.test(hijo.nodeValue)) || (hijo.nodeType == 8)) {
                    this.nodosEliminar[this.nodosEliminar.length] = hijo;
                }
            }
        }
        for (var d = 0; d < this.nodosEliminar.length; d++) {
            this.nodosEliminar[d].parentNode.removeChild(this.nodosEliminar[d]);
        }
    };
    return UtilPieces;
}());

//# sourceMappingURL=util-pieces.js.map
