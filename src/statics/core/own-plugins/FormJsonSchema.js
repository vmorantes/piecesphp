var BaseAttributeSchema = /** @class */ (function () {
    function BaseAttributeSchema(options) {
        options = this.validateOptions(options);
        this.name = options.name;
        this.value = options.value;
    }
    /**
     * @method validateOptions
     * @description Valida las opciones
     * @param options Opciones a validar
     * @returns {object} Las opciones con valores por defecto en caso de necesitarlos
     */
    BaseAttributeSchema.prototype.validateOptions = function (options) {
        if (options === undefined || typeof options != 'object') {
            throw new TypeError("Se esperaba que options fuera tipo object");
        }
        options.value = options.value == undefined ? '' : options.value;
        if (options.name === undefined || typeof options.name != 'string') {
            throw new TypeError("Se esperaba que name fuera tipo string");
        }
        if (typeof options.value != 'string') {
            throw new TypeError("Se esperaba que value fuera tipo string");
        }
        options.name = options.name.trim();
        options.value = options.value.trim();
        return options;
    };
    BaseAttributeSchema.isAttribute = function (attr) {
        if (attr.name !== undefined && typeof attr.name == 'string') {
            return true;
        }
        return false;
    };
    BaseAttributeSchema.isAttributeArray = function (array) {
        if (Array.isArray(array)) {
            for (var _i = 0, array_1 = array; _i < array_1.length; _i++) {
                var i = array_1[_i];
                if (!this.isAttribute(i)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    };
    BaseAttributeSchema.attrsValidate = function (attrs) {
        if (!this.isAttribute(attrs) && !this.isAttributeArray(attrs)) {
            return false;
        }
        else {
            return true;
        }
    };
    return BaseAttributeSchema;
}());
var BaseFieldCheckboxSchema = /** @class */ (function () {
    function BaseFieldCheckboxSchema(name, options, label, required) {
        if (label === void 0) { label = ''; }
        if (required === void 0) { required = false; }
        if (name === undefined)
            throw new Error('Falta el argumento name');
        if (typeof name != 'string')
            throw new TypeError('El argumento name debe ser tipo string');
        this.name = name;
        options = this.clearOptions(options);
        this.options = [];
        for (var _i = 0, options_1 = options; _i < options_1.length; _i++) {
            var option = options_1[_i];
            var _option = new BaseFieldSchema({
                name: this.name,
                label: option.display,
                value: option.value,
                type: '_checkbox'
            });
            this.options.push(_option);
        }
        if (label != undefined && typeof label != 'string')
            throw new TypeError('El argumento label debe ser tipo string');
        this.label = label;
        if (required != undefined && typeof required != 'boolean')
            throw new TypeError('El argumento label debe ser tipo boolean');
        this.required = required;
    }
    Object.defineProperty(BaseFieldCheckboxSchema.prototype, "elements", {
        get: function () {
            this.configElements();
            return this._elements;
        },
        enumerable: true,
        configurable: true
    });
    BaseFieldCheckboxSchema.prototype.clearOptions = function (options) {
        options = Array.isArray(options) ? options : [options];
        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            if (option.value === undefined)
                throw new TypeError('Se esperaba que option tuviera la propiedad value');
            if (typeof option.value != 'string')
                throw new TypeError('La propiedad value de option debe ser tipo string');
            if (option.display != undefined && typeof option.display != 'string')
                throw new TypeError('La propiedad display de option debe ser tipo string');
            if (option.display == undefined || option.display.length < 1) {
                options[i].display = option.value;
            }
        }
        return options;
    };
    BaseFieldCheckboxSchema.prototype.configElements = function () {
        var options = this.options;
        var _options = [];
        var wrapperClasses = ['field'];
        if (this.required) {
            //wrapperClasses.push('required')
        }
        for (var _i = 0, options_2 = options; _i < options_2.length; _i++) {
            var option = options_2[_i];
            var wrapper = document.createElement('div');
            var inputWrapper = document.createElement('div');
            inputWrapper.setAttribute('class', 'ui toggle checkbox');
            inputWrapper.appendChild(option.element);
            if (option.label.length > 0) {
                var label = document.createElement('label');
                label.innerHTML = option.label;
                inputWrapper.appendChild(label);
            }
            wrapper.appendChild(inputWrapper);
            wrapper.setAttribute('class', wrapperClasses.join(' '));
            _options.push(wrapper);
        }
        this._elements = _options;
    };
    return BaseFieldCheckboxSchema;
}());
var BaseFieldRadioSchema = /** @class */ (function () {
    function BaseFieldRadioSchema(name, options, label, required) {
        if (label === void 0) { label = ''; }
        if (required === void 0) { required = false; }
        if (name === undefined)
            throw new Error('Falta el argumento name');
        if (typeof name != 'string')
            throw new TypeError('El argumento name debe ser tipo string');
        this.name = name;
        options = this.clearOptions(options);
        this.options = [];
        for (var _i = 0, options_3 = options; _i < options_3.length; _i++) {
            var option = options_3[_i];
            var _option = new BaseFieldSchema({
                name: this.name,
                label: option.display,
                value: option.value,
                type: '_radio'
            });
            this.options.push(_option);
        }
        if (label != undefined && typeof label != 'string')
            throw new TypeError('El argumento label debe ser tipo string');
        this.label = label;
        if (required != undefined && typeof required != 'boolean')
            throw new TypeError('El argumento label debe ser tipo boolean');
        this.required = required;
    }
    Object.defineProperty(BaseFieldRadioSchema.prototype, "elements", {
        get: function () {
            this.configElements();
            return this._elements;
        },
        enumerable: true,
        configurable: true
    });
    BaseFieldRadioSchema.prototype.clearOptions = function (options) {
        options = Array.isArray(options) ? options : [options];
        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            if (option.value === undefined)
                throw new TypeError('Se esperaba que option tuviera la propiedad value');
            if (typeof option.value != 'string')
                throw new TypeError('La propiedad value de option debe ser tipo string');
            if (option.display != undefined && typeof option.display != 'string')
                throw new TypeError('La propiedad display de option debe ser tipo string');
            if (option.display == undefined || option.display.length < 1) {
                options[i].display = option.value;
            }
        }
        return options;
    };
    BaseFieldRadioSchema.prototype.configElements = function () {
        var options = this.options;
        var _options = [];
        var wrapperClasses = ['field'];
        if (this.required) {
            //wrapperClasses.push('required')
        }
        for (var _i = 0, options_4 = options; _i < options_4.length; _i++) {
            var option = options_4[_i];
            var wrapper = document.createElement('div');
            var inputWrapper = document.createElement('div');
            inputWrapper.setAttribute('class', 'ui slider checkbox');
            inputWrapper.appendChild(option.element);
            if (option.label.length > 0) {
                var label = document.createElement('label');
                label.innerHTML = option.label;
                inputWrapper.appendChild(label);
            }
            wrapper.appendChild(inputWrapper);
            wrapper.setAttribute('class', wrapperClasses.join(' '));
            _options.push(wrapper);
        }
        this._elements = _options;
    };
    return BaseFieldRadioSchema;
}());
/**
 * @class BaseFieldSchema
 * @description Esquema de un campo de formulario
 */
var BaseFieldSchema = /** @class */ (function () {
    /**
     * @method constructor
     * @description Constructor
     * @param {Object} options
     * @return {ThisType}
     */
    function BaseFieldSchema(options) {
        var validOptions = this.validateOptions(options);
        this.name = validOptions.name;
        this.value = validOptions.value;
        this.label = validOptions.label;
        this.type = validOptions.type;
        this.required = validOptions.required;
        this.attributes = this.validateOptionAttribute(options.attributes, validOptions.required, validOptions.name);
        this.choices = [];
        this.id = this.name + '_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        this._element = null;
    }
    Object.defineProperty(BaseFieldSchema.prototype, "element", {
        get: function () {
            if (this._element === null)
                this.configElement();
            return this._element;
        },
        enumerable: true,
        configurable: true
    });
    BaseFieldSchema.prototype.setChoices = function (choices) {
        this.choices = choices;
    };
    BaseFieldSchema.prototype.configElement = function () {
        var field = document.getElementById("" + this.id);
        if (field === null) {
            field = document.createElement(this.tag.tag);
            field.setAttribute('id', this.id);
            var attrs = this.attributes;
            for (var _i = 0, attrs_1 = attrs; _i < attrs_1.length; _i++) {
                var attr = attrs_1[_i];
                field.setAttribute(attr.name, attr.value);
            }
            if (this.type == 'select') {
                var select = this.selectOptions();
                var childs = select.elements;
                var hasSelected = false;
                for (var _a = 0, childs_1 = childs; _a < childs_1.length; _a++) {
                    var child = childs_1[_a];
                    var _child = child;
                    var value = void 0;
                    try {
                        if (typeof this.value == 'string') {
                            value = JSON.parse(this.value);
                        }
                        else {
                            value = this.value;
                        }
                    }
                    catch (e) {
                        value = this.value;
                    }
                    if (!hasSelected || field.hasAttribute('multiple')) {
                        if (!Array.isArray(value)) {
                            if (_child.value == value && _child.value.toString().trim().length > 0) {
                                _child.setAttribute('selected', '');
                                hasSelected = true;
                            }
                        }
                        else {
                            var selecting = (function (pajar, aguja) {
                                for (var _i = 0, pajar_1 = pajar; _i < pajar_1.length; _i++) {
                                    var paja = pajar_1[_i];
                                    if (paja == aguja && aguja.toString().length > 0) {
                                        return true;
                                    }
                                }
                                return false;
                            })(value, _child.value);
                            if (selecting) {
                                _child.setAttribute('selected', '');
                                hasSelected = true;
                            }
                        }
                    }
                    field.appendChild(_child);
                }
            }
            else if (this.type == 'choice-radio') {
                var choices = this.radio();
                var childs = choices.elements;
                var hasSelected = false;
                for (var _b = 0, childs_2 = childs; _b < childs_2.length; _b++) {
                    var child = childs_2[_b];
                    var _child = child.querySelector('input');
                    var value = void 0;
                    try {
                        if (typeof this.value == 'string') {
                            value = JSON.parse(this.value);
                        }
                        else {
                            value = this.value;
                        }
                    }
                    catch (e) {
                        value = this.value;
                    }
                    if (!Array.isArray(value)) {
                        if (_child.value == value && _child.value.toString().trim().length > 0) {
                            _child.setAttribute('checked', '');
                            hasSelected = true;
                        }
                    }
                    field.appendChild(child);
                }
            }
            else if (this.type == 'choice-checkbox') {
                var choices = this.checkbox();
                var childs = choices.elements;
                var hasSelected = false;
                for (var _c = 0, childs_3 = childs; _c < childs_3.length; _c++) {
                    var child = childs_3[_c];
                    var _child = child.querySelector('input');
                    var value = void 0;
                    try {
                        if (typeof this.value == 'string') {
                            value = JSON.parse(this.value);
                        }
                        else {
                            value = this.value;
                        }
                    }
                    catch (e) {
                        value = this.value;
                    }
                    if (!Array.isArray(value)) {
                        if (_child.value == value && _child.value.toString().trim().length > 0) {
                            _child.setAttribute('checked', '');
                            hasSelected = true;
                        }
                    }
                    else {
                        var selecting = (function (pajar, aguja) {
                            for (var _i = 0, pajar_2 = pajar; _i < pajar_2.length; _i++) {
                                var paja = pajar_2[_i];
                                if (paja == aguja && aguja.toString().length > 0) {
                                    return true;
                                }
                            }
                            return false;
                        })(value, _child.value);
                        if (selecting) {
                            _child.setAttribute('checked', '');
                            hasSelected = true;
                        }
                    }
                    field.appendChild(child);
                }
            }
            else if (this.type == 'textarea') {
                field.innerHTML = this.value.toString().trim();
            }
            if (this.tag.type !== undefined) {
                field.setAttribute('type', this.tag.type);
            }
            if (this.type == 'submit') {
                field.innerHTML = this.label;
            }
            else {
                var notSetName = ['option', 'choice-radio', 'choice-checkbox'];
                var notSetValue = ['select', 'textarea'];
                if (notSetValue.indexOf(this.type) == -1) {
                    field.setAttribute('value', this.value.toString());
                }
                if (notSetName.indexOf(this.type) == -1) {
                    field.setAttribute('name', this.name);
                }
                if (this.type == 'option') {
                    field.innerHTML = this.label;
                }
            }
        }
        this._element = field;
    };
    BaseFieldSchema.prototype.radio = function () {
        return new BaseFieldRadioSchema(this.name, this.getChoices(), this.label, this.required);
    };
    BaseFieldSchema.prototype.checkbox = function () {
        return new BaseFieldCheckboxSchema(this.name, this.getChoices(), this.label, this.required);
    };
    BaseFieldSchema.prototype.selectOptions = function () {
        return new BaseSelectOptionsSchema(this.name, this.getChoices(), this.label, this.required);
    };
    BaseFieldSchema.prototype.getChoices = function () {
        return this.choices;
    };
    /**
     * @method validateOptions
     * @description Valida las opciones
     * @param options Opciones a validar
     * @returns {object} Las opciones con valores por defecto en caso de necesitarlos
     */
    BaseFieldSchema.prototype.validateOptions = function (options) {
        if (options === undefined || typeof options != 'object') {
            throw new TypeError("Se esperaba que options fuera tipo object");
        }
        options.value = options.value == undefined ? '' : options.value;
        options.label = options.label == undefined ? '' : options.label;
        options.type = options.type == undefined ? 'text' : options.type;
        options.required = options.required == undefined ? false : options.required;
        if (options.name === undefined || typeof options.name != 'string') {
            throw new TypeError("Se esperaba que name fuera tipo string");
        }
        if (Array.isArray(options.value)) {
            options.value = JSON.stringify(options.value);
        }
        if (!BaseFieldSchema.isScalar(options.value)) {
            throw new TypeError("Se esperaba que value fuera tipo scalar");
        }
        if (typeof options.label != 'string') {
            throw new TypeError("Se esperaba que label fuera tipo string");
        }
        if (typeof options.type != 'string') {
            throw new TypeError("Se esperaba que type fuera tipo string");
        }
        if (!BaseFieldSchema.types.hasOwnProperty(options.type)) {
            throw new TypeError("El tipo de campo '" + options.type + "' no est\u00E1 implementado");
        }
        if (typeof options.required != 'boolean') {
            throw new TypeError("Se esperaba que required fuera tipo boolean");
        }
        this.tag = BaseFieldSchema.types[options.type];
        return options;
    };
    BaseFieldSchema.prototype.validateOptionAttribute = function (attributes, isRequired, fieldName) {
        attributes = attributes == undefined ? [] : attributes;
        if (!BaseAttributeSchema.attrsValidate(attributes)) {
            throw new TypeError("Se esperaba que attributes fuera tipo BaseAttributeSchema o BaseAttributeSchema[]");
        }
        var attrs = [];
        var hasRequired = false;
        var hasClasses = false;
        var selectClasses = [
            'ui',
            'dropdown',
            'search',
        ];
        attributes = (Array.isArray(attributes) ? attributes : [attributes]);
        for (var _i = 0, attributes_1 = attributes; _i < attributes_1.length; _i++) {
            var attr = attributes_1[_i];
            if (attr.name == 'class') {
                hasClasses = true;
                break;
            }
        }
        if (attributes.length == 0 || !hasClasses) {
            switch (this.type) {
                case 'choice-radio':
                    break;
                case 'choice-checkbox':
                    break;
                case 'select':
                    if (!hasClasses) {
                        attributes.push(new BaseAttributeSchema({ name: 'class', value: selectClasses.join(' ') }));
                    }
                    break;
            }
        }
        for (var _a = 0, attributes_2 = attributes; _a < attributes_2.length; _a++) {
            var attr = attributes_2[_a];
            var name_1 = attr.name.trim();
            var value = attr.value;
            switch (this.type) {
                case 'choice-radio':
                    break;
                case 'choice-checkbox':
                    break;
                case 'select':
                    if (name_1 == 'class') {
                        value = value.trim().split(' ');
                        for (var _b = 0, value_1 = value; _b < value_1.length; _b++) {
                            var c = value_1[_b];
                            if (selectClasses.indexOf(c) == -1) {
                                selectClasses.push(c);
                            }
                        }
                        value = selectClasses.join(' ');
                    }
                    break;
            }
            if (name_1 == 'type') {
                if (this.tag.type !== undefined) {
                    value = this.tag.type;
                }
            }
            else if (name_1 == 'name') {
                value = fieldName;
            }
            else if (name_1 == 'required') {
                hasRequired = true;
                if (isRequired) {
                    value = '';
                }
                else {
                    continue;
                }
            }
            else if (name_1 == 'id') {
                value = this.id;
            }
            var _attr = {
                name: name_1,
                value: value == undefined ? '' : value.trim(),
            };
            attrs.push(new BaseAttributeSchema(_attr));
        }
        if (!hasRequired) {
            if (isRequired) {
                attrs.push(new BaseAttributeSchema({ name: 'required' }));
            }
        }
        return attrs;
    };
    BaseFieldSchema.isField = function (field) {
        if (field.name !== undefined) {
            return true;
        }
        return false;
    };
    BaseFieldSchema.isFieldArray = function (array) {
        if (Array.isArray(array)) {
            for (var _i = 0, array_2 = array; _i < array_2.length; _i++) {
                var i = array_2[_i];
                if (!this.isField(i)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    };
    BaseFieldSchema.fieldsValidate = function (fields) {
        if (!this.isField(fields) && !this.isFieldArray(fields)) {
            return false;
        }
        else {
            return true;
        }
    };
    /**
     * @method isScalar
     * @description Valida que el valor sea escalar
     * @param {any} value
     * @returns {boolean}
     */
    BaseFieldSchema.isScalar = function (value) {
        return /boolean|number|string/.test(typeof value);
    };
    BaseFieldSchema.types = {
        'submit': {
            'tag': 'button',
            'type': 'submit',
        },
        'text': {
            'tag': 'input',
            'type': 'text',
        },
        'number': {
            'tag': 'input',
            'type': 'number',
        },
        'tel': {
            'tag': 'input',
            'type': 'tel',
        },
        'email': {
            'tag': 'input',
            'type': 'email',
        },
        'date': {
            'tag': 'input',
            'type': 'date',
        },
        'datetime': {
            'tag': 'input',
            'type': 'datetime',
        },
        'url': {
            'tag': 'input',
            'type': 'url',
        },
        'hidden': {
            'tag': 'input',
            'type': 'hidden',
        },
        'checkbox': {
            'tag': 'input',
            'type': 'checkbox',
        },
        'radio': {
            'tag': 'input',
            'type': 'radio',
        },
        '_checkbox': {
            'tag': 'input',
            'type': 'checkbox',
        },
        '_radio': {
            'tag': 'input',
            'type': 'radio',
        },
        'textarea': {
            'tag': 'textarea',
        },
        'option': {
            'tag': 'option',
        },
        'select': {
            'tag': 'select',
        },
        'choice-radio': {
            'tag': 'div',
        },
        'choice-checkbox': {
            'tag': 'div',
        },
    };
    return BaseFieldSchema;
}());
var BaseFormSchema = /** @class */ (function () {
    function BaseFormSchema(options) {
        this.validations = {};
        this.fieldsConfigurations = {};
        this.fieldsValueOnEmpty = {};
        this.fieldsDuplicables = {};
        this.fieldsDuplicablesValues = {};
        this.fieldsDuplicablesClones = {};
        this.fieldsRemovibles = {};
        this.dependFields = {};
        this.rules = {};
        this.validateOptions(options);
        this._form = $(this.element());
        var _loop_1 = function (field) {
            var type = field.type;
            var name_2 = field.name;
            var config = {};
            if (this_1.fieldsConfigurations[name_2] !== undefined) {
                config = this_1.fieldsConfigurations[name_2];
            }
            var applyCheckbox = ['choice-radio', 'choice-checkbox'];
            if (applyCheckbox.indexOf(type) != -1) {
                var selector = "[name='" + name_2 + "']";
                var element = this_1._form.find(selector);
                element.parent().checkbox(config);
            }
            if (type == 'select') {
                if (config.message == undefined) {
                    config.message = {
                        addResult: 'Agregar <b>{term}</b>',
                        count: '{count} seleccionados',
                        maxSelections: 'La cantidad máxima de selecciones es {maxCount}',
                        noResults: 'No hay resultados.'
                    };
                }
                var selector = "[name='" + name_2 + "']";
                var element_1 = this_1._form.find(selector);
                var dropdownSet = false;
                var isMultiple = element_1.attr('multiple') !== undefined;
                var selectedOptions = element_1.find('option[selected]');
                var countSelected = selectedOptions.length;
                var hasSelected = countSelected > 0;
                if (isMultiple) {
                    if (hasSelected) {
                        if (countSelected == 1) {
                            $(document).ready(function (e) {
                                element_1.dropdown(config);
                            });
                            dropdownSet = true;
                        }
                    }
                }
                if (!dropdownSet) {
                    element_1.dropdown(config);
                }
            }
        };
        var this_1 = this;
        for (var _i = 0, _a = this.fields; _i < _a.length; _i++) {
            var field = _a[_i];
            _loop_1(field);
        }
    }
    Object.defineProperty(BaseFormSchema.prototype, "form", {
        get: function () {
            return this._form;
        },
        enumerable: true,
        configurable: true
    });
    BaseFormSchema.prototype.initValidations = function () {
        var _fields = {};
        var fields = this.fields;
        var form = this._form;
        var _loop_2 = function (field) {
            if (field.type == 'submit')
                return "continue";
            var validations = this_2.validations[field.name];
            if (validations === undefined)
                return "continue";
            var rules_1 = validations.rules;
            var prompts = validations.prompts;
            var _field = {
                identifier: '',
                rules: []
            };
            _field.identifier = field.name;
            var _loop_3 = function (i) {
                var rule = rules_1[i];
                var prompt_1 = prompts[i];
                var _rule = {
                    type: '',
                };
                if (typeof rule == 'function') {
                    var ruleName = '__function_rule_' + Date.now().toString();
                    $.fn.form.settings.rules[ruleName] = function (value) {
                        var fieldElement = form.find("[name='" + field.name + "']");
                        return rule(value, fieldElement);
                    };
                    if (prompt_1 != null) {
                        _rule.prompt = prompt_1;
                    }
                    _rule.type = ruleName;
                }
                else {
                    _rule.type = rule;
                    if (prompt_1 != null) {
                        _rule.prompt = prompt_1;
                    }
                }
                _field.rules.push(_rule);
            };
            for (var i = 0; i < rules_1.length; i++) {
                _loop_3(i);
            }
            this_2.rules[field.name] = _field;
            _fields[field.name] = _field;
            var clones = this_2.fieldsDuplicablesClones[field.name];
            for (var _i = 0, clones_1 = clones; _i < clones_1.length; _i++) {
                var clon = clones_1[_i];
                var _cloneField = Object.assign({}, _field);
                _cloneField.identifier = clon.name;
                this_2.rules[clon.name] = _cloneField;
                _fields[clon.name] = _cloneField;
            }
        };
        var this_2 = this;
        for (var _i = 0, fields_1 = fields; _i < fields_1.length; _i++) {
            var field = fields_1[_i];
            _loop_2(field);
        }
        var formConfig = {
            inline: true,
            onFailure: function (formErrors, fields) {
                var firstError = form.find('.field.error:first');
                try {
                    $([document.documentElement, document.body]).animate({
                        scrollTop: firstError.offset().top
                    }, 500);
                }
                catch (e) {
                    console.info(e);
                }
            },
            fields: _fields
        };
        form.form(formConfig);
        var dependFields = this.dependFields;
        var rules = this.rules;
        form.change(function (e) {
            for (var fieldName in dependFields) {
                var _dependsField = dependFields[fieldName];
                if (_dependsField.length > 0) {
                    var _rules = void 0;
                    if (rules[fieldName] != undefined) {
                        /* for (let r of rules[fieldName].rules) {
                            _rules.push(r.type)
                        } */
                        _rules = {
                            rules: rules[fieldName].rules
                        };
                    }
                    else {
                        continue;
                    }
                    var validate = true;
                    var _field = form.find("[name='" + fieldName + "']");
                    for (var _i = 0, _dependsField_1 = _dependsField; _i < _dependsField_1.length; _i++) {
                        var depend = _dependsField_1[_i];
                        var dependFieldName = depend.field;
                        var dependFieldSelector = "[name='" + dependFieldName + "']";
                        var _dField = form.find(dependFieldSelector);
                        var isRadio = _dField.attr('type') == 'radio';
                        var isCheckbox = _dField.attr('type') == 'checkbox';
                        if (isRadio) {
                            _dField = form.find(dependFieldSelector + ":checked");
                        }
                        var controlValue = depend.value;
                        var value = _dField.val();
                        if (isCheckbox) {
                            var name_3 = dependFieldName.replace('[]', '');
                            value = form.form('get values')[name_3];
                            value = Array.isArray(value) ? value.filter(function (v) { return v !== false; }) : value;
                        }
                        var isFunction = typeof controlValue == 'function';
                        if (value == undefined) {
                            validate = false;
                            break;
                        }
                        if (!isFunction) {
                            if (Array.isArray(value)) {
                                if (value.indexOf(controlValue) == -1) {
                                    validate = false;
                                    break;
                                }
                            }
                            else {
                                if (value != controlValue) {
                                    validate = false;
                                    break;
                                }
                            }
                        }
                        else {
                            validate = controlValue(value);
                            if (typeof validate != 'boolean') {
                                console.error('La función de validación en dependFields no ha devuelto un valor booleano');
                                return;
                            }
                            if (!validate) {
                                break;
                            }
                        }
                    }
                    try {
                        form.form('remove fields', [fieldName]);
                    }
                    catch (e) {
                        console.warn(e);
                    }
                    if (validate) {
                        form.form('add rule', fieldName, _rules);
                        _field.removeAttr('schema-ignore-value');
                        var container = _field.parents('.field');
                        var groupedContainer = container.parents('.grouped.fields');
                        if (groupedContainer.length > 0) {
                            container = groupedContainer;
                        }
                        if (container.hasClass('disabled')) {
                            container.removeClass('disabled');
                        }
                    }
                    else {
                        _field.attr('schema-ignore-value', '');
                        var container = _field.parents('.field');
                        var groupedContainer = container.parents('.grouped.fields');
                        if (groupedContainer.length > 0) {
                            container = groupedContainer;
                        }
                        if (!container.hasClass('disabled')) {
                            container.addClass('disabled');
                        }
                        if (container.hasClass('error')) {
                            container.removeClass('error');
                        }
                        container.find('.ui.prompt').remove();
                        if (_field.attr('type') == 'radio' || _field.attr('type') == 'checkbox') {
                            _field.parent().checkbox('uncheck');
                        }
                        else if (_field.parent().hasClass('dropdown')) {
                            _field.dropdown('clear');
                            _field.dropdown('refresh');
                        }
                        else {
                            _field.val('');
                        }
                    }
                }
            }
        });
        form.change();
    };
    BaseFormSchema.prototype.getValues = function () {
        var values = this._form.form('get values');
        var toRemove = [];
        var _loop_4 = function (name_4) {
            var isDuplicated = name_4.indexOf('[duplicated') !== -1;
            var selector = "[name='" + name_4 + "']";
            var selectorAlt = "[name*='" + name_4 + "[']";
            var isArrayValue = false;
            var field = this_3._form.find(selector);
            if (isDuplicated) {
                toRemove.push(name_4);
            }
            if (field.length == 0) {
                field = this_3._form.find(selectorAlt);
                isArrayValue = true;
            }
            var isRadio = field.attr('type') == 'radio';
            var isCheckbox = field.attr('type') == 'checkbox';
            if (isRadio) {
                selector = selector + ":checked";
                field = this_3._form.find(selector);
            }
            var value = '';
            if (field.length > 0) {
                if (isArrayValue && !isCheckbox && !isRadio) {
                    value = [];
                    field.each(function () {
                        var val = $(this).val().toString().trim();
                        if (val.length > 0) {
                            value.push(val);
                        }
                    });
                }
                else {
                    value = field.val();
                }
            }
            if (typeof value == 'string') {
                var _value = value.trim();
                if (_value.length == 0) {
                    value = this_3.fieldsValueOnEmpty[name_4];
                }
            }
            if (isCheckbox) {
                value = values[name_4];
                if (Array.isArray(value)) {
                    value = value.filter(function (val) {
                        return val !== false;
                    });
                }
            }
            var ignore = field.attr('schema-ignore-value') != undefined;
            if (ignore) {
                value = this_3.fieldsValueOnEmpty[name_4];
            }
            values[name_4] = value;
        };
        var this_3 = this;
        for (var name_4 in values) {
            _loop_4(name_4);
        }
        for (var _i = 0, toRemove_1 = toRemove; _i < toRemove_1.length; _i++) {
            var remove = toRemove_1[_i];
            delete values[remove];
        }
        return values;
    };
    /**
     * @method setValidation
     * @description Establece una regla de validacion para un campo
     * @param {BaseFieldSchema} field
     * @param {{ validation: string | FunctionStringCallback, prompt?: string }} options
     * @returns {void}
     */
    BaseFormSchema.prototype.setValidation = function (field, options) {
        if (typeof options.validation != 'function' && typeof options.validation != 'string') {
            throw new TypeError('El parámetro validation debe ser tipo function|string');
        }
        if (options.prompt === undefined) {
            options.prompt = null;
        }
        if (options.prompt !== null && typeof options.prompt != 'string') {
            throw new TypeError('El parámetro prompt debe ser tipo string');
        }
        if (this.validations[field.name] === undefined) {
            this.validations[field.name] = {
                rules: [],
                prompts: []
            };
        }
        this.validations[field.name].rules.push(options.validation);
        this.validations[field.name].prompts.push(options.prompt);
    };
    /**
     * @method isValid
     * @description Devuelve la validez del formulario
     * @returns {boolean}
     */
    BaseFormSchema.prototype.isValid = function () {
        return this._form.form('is valid');
    };
    /**
     * @method element
     * @description Devuelve el formulario con todos sus campos
     * @returns {HTMLFormElement}
     */
    BaseFormSchema.prototype.element = function () {
        var instance = this;
        var form = document.createElement('form');
        var attrs = this.attributes;
        var fields = Array.isArray(this.fields) ? this.fields : [this.fields];
        for (var _i = 0, attrs_2 = attrs; _i < attrs_2.length; _i++) {
            var attr = attrs_2[_i];
            form.setAttribute(attr.name, attr.value);
        }
        for (var index in fields) {
            var field = fields[index];
            this.fieldsDuplicablesClones[field.name] = [];
            var duplicable = this.fieldsDuplicables[field.name];
            if (duplicable) {
                var values = this.fieldsDuplicablesValues[field.name];
                var valuesNum = values.length;
                for (var i = 0; i < valuesNum; i++) {
                    var value = values[i];
                    if (i == 0) {
                        fields[index].value = value;
                    }
                    else {
                        var cloneField = Object.assign({}, field);
                        cloneField.value = value;
                        var options = {};
                        for (var property in cloneField) {
                            if (field.hasOwnProperty(property)) {
                                options[property] = cloneField[property];
                            }
                        }
                        var uniqid = 'duplicated_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                        options.name = options.name.replace('[]', '') + ("[" + uniqid + "]");
                        cloneField = new BaseFieldSchema(options);
                        this.fieldsDuplicablesClones[field.name].push(cloneField);
                    }
                }
            }
        }
        var _loop_5 = function (field) {
            var clones = this_4.fieldsDuplicablesClones[field.name];
            var hasClones = clones.length > 0;
            var excludeLabels = ['']; //Elementos que ignorarán el label
            var excludeWrapper = ['']; //Elementos que ignorarán el contenedor
            var selfWrapper = ['choice-radio', 'choice-checkbox']; //Elementos que generan su propio contenedor
            var fieldElement = field.element; //Elemento
            var wrapper = fieldElement; //Contenedor, por defecto el mismo elemento 
            var groupedWrapper = document.createElement('div'); //Contenedor para contenedores
            var wrapperClasses = ['field']; //Clases del contenedor
            var groupedWrapperClasses = ['grouped', 'fields']; //Clases del contenedor de grupos
            var groupedTypes = ['choice-radio', 'choice-checkbox']; //Elementos que usan contenedor de grupos
            var shouldHasWrapper = excludeWrapper.indexOf(field.type) == -1;
            if (shouldHasWrapper) { //En caso de que necesiten contenedor
                wrapper = document.createElement('div');
                var isGroupedType = groupedTypes.indexOf(field.type) != -1; //Si es de tipo grupo
                var hasSelfWrapper = selfWrapper.indexOf(field.type) != -1; //Si tiene contenedor propio
                //Verifica si usa label
                var hasLabel = (field.label.length > 0) &&
                    (field.type != 'submit') &&
                    (excludeLabels.indexOf(field.type) == -1);
                if (hasLabel) { //En caso de que use label
                    var label = document.createElement('label');
                    label.innerHTML = field.label;
                    wrapper.appendChild(label);
                    if (isGroupedType) { //Si es de tipo grupos
                        groupedWrapper.appendChild(label);
                    }
                    else { //Si no
                        wrapper.appendChild(label);
                    }
                }
                if (hasSelfWrapper) { //En caso de tener su propio contenedor
                    var fieldInnerHTML = fieldElement.innerHTML;
                    fieldElement.innerHTML = wrapper.innerHTML + fieldInnerHTML;
                    wrapper = fieldElement;
                }
                else { //Si no
                    wrapper.appendChild(fieldElement);
                    if (fieldElement.hasAttribute('required')) { //Se verifica si tiene el atributo required
                        wrapperClasses.push('required');
                    }
                    wrapper.setAttribute('class', wrapperClasses.join(' '));
                }
                if (fieldElement.hasAttribute('required')) { //Se verifica si tiene el atributo required
                    groupedWrapperClasses.push('required');
                }
                if (isGroupedType) { //Si es de tipo grupo
                    groupedWrapper.setAttribute('class', groupedWrapperClasses.join(' '));
                    groupedWrapper.appendChild(wrapper);
                    wrapper = groupedWrapper;
                }
                //Se verifica si es removible 
                var removible_1 = this_4.fieldsRemovibles[field.name];
                if (removible_1) {
                    var removeButton = document.createElement('button');
                    removeButton.setAttribute('remove-button', '');
                    removeButton.setAttribute('class', 'ui mini button red');
                    removeButton.innerHTML = "<i class=\"trash icon\"></i>Eliminar";
                    if (hasLabel) {
                        var label = wrapper.querySelector('label');
                        label.innerHTML += ' ' + removeButton.outerHTML;
                        removeButton = label.querySelector('[remove-button]');
                        $(label).click(function (e) {
                            e.preventDefault();
                        });
                    }
                    else {
                        var firstElement = wrapper.childNodes[0];
                        wrapper.insertBefore(removeButton, firstElement);
                    }
                    $(wrapper).click(function (e) {
                        e.preventDefault();
                    });
                    $(removeButton).click(function (e) {
                        e.preventDefault();
                        if (e.clientX != 0 && e.clientY != 0) {
                            wrapper.remove();
                        }
                    });
                }
                //Se verifica si es duplicable 
                var duplicable = this_4.fieldsDuplicables[field.name];
                if (duplicable) {
                    var fieldWrapper = wrapper.outerHTML;
                    var containerDuplicateButton = document.createElement('div');
                    var duplicateButton = document.createElement('button');
                    duplicateButton.setAttribute('duplicate-button', '');
                    duplicateButton.setAttribute('class', 'ui mini button blue');
                    duplicateButton.innerHTML = "<i class=\"plus icon\"></i>Agregar otro campo \"" + field.label + "\"";
                    containerDuplicateButton.appendChild(duplicateButton);
                    containerDuplicateButton.setAttribute('style', ([
                        'margin: 1rem 0;'
                    ]).join(' '));
                    wrapper = document.createElement('div');
                    wrapper.setAttribute('class', 'field');
                    var containerFields_1 = document.createElement('div');
                    containerFields_1.setAttribute('duplicate-container', '');
                    containerFields_1.innerHTML = fieldWrapper;
                    containerFields_1.querySelectorAll('[id]').forEach(function (element, i, parent) {
                        (parent.item(i)).removeAttribute('id');
                    });
                    wrapper.appendChild(containerDuplicateButton);
                    wrapper.appendChild(containerFields_1);
                    if (removible_1) {
                        var removeButton = containerFields_1.querySelector('[remove-button]');
                        $(removeButton).parent().click(function (e) {
                            e.preventDefault();
                        });
                        $(removeButton).click(function (e) {
                            e.preventDefault();
                            var parent = $(this).parent();
                            if (parent.prop('tagName') == 'LABEL') {
                                parent = parent.parent();
                            }
                            parent.remove();
                        });
                    }
                    var elementBase_1 = containerFields_1.childNodes[0].cloneNode(true);
                    $(duplicateButton).click(function (e) {
                        e.preventDefault();
                        instance.duplicateField(field, elementBase_1, containerFields_1, removible_1);
                    });
                    if (hasClones) {
                        for (var _i = 0, clones_2 = clones; _i < clones_2.length; _i++) {
                            var value = clones_2[_i];
                            instance.duplicateField(field, elementBase_1, containerFields_1, removible_1, value.value, value.name);
                        }
                    }
                }
            }
            form.appendChild(wrapper); //Se agrega al formulario
        };
        var this_4 = this;
        for (var _a = 0, fields_2 = fields; _a < fields_2.length; _a++) {
            var field = fields_2[_a];
            _loop_5(field);
        }
        var hasClass = form.getAttribute('class') !== null;
        var classes = [];
        if (hasClass) {
            classes = form.getAttribute('class').trim().split(' ');
        }
        var hasUi = false;
        var hasForm = false;
        for (var _b = 0, classes_1 = classes; _b < classes_1.length; _b++) {
            var _class = classes_1[_b];
            if (_class == 'ui') {
                hasUi = true;
            }
            if (_class == 'form') {
                hasUi = true;
            }
        }
        if (!hasUi) {
            classes.push('ui');
        }
        if (!hasForm) {
            classes.push('form');
        }
        form.setAttribute('class', classes.join(' '));
        return form;
    };
    /**
     * @method validateOptions
     * @description Valida las opciones
     * @param options Opciones a validar
     * @returns {object} Las opciones con valores por defecto en caso de necesitarlos
     */
    BaseFormSchema.prototype.validateOptions = function (options) {
        if (options === undefined || typeof options != 'object') {
            throw new TypeError("Se esperaba que options fuera tipo object");
        }
        this.validateAttributes(options.attributes);
        this.validateFields(options.fields);
    };
    /**
     * @method validateAttributes
     * @description Valida los atributos y defina la propiedad attributes
     * @param {BaseAttributeSchema|BaseAttributeSchema[]} attributes atributos a validar
     * @throws {TypeError}
     * @returns {void}
     */
    BaseFormSchema.prototype.validateAttributes = function (attributes) {
        var _attributes = [];
        attributes = attributes == undefined ? null : attributes;
        if (attributes !== null && !BaseAttributeSchema.attrsValidate(attributes)) {
            throw new TypeError("Se esperaba que attributes fuera tipo BaseAttributeSchema o BaseAttributeSchema[]");
        }
        if (attributes !== null) {
            attributes = Array.isArray(attributes) ? attributes : [attributes];
            for (var _i = 0, attributes_3 = attributes; _i < attributes_3.length; _i++) {
                var attr = attributes_3[_i];
                _attributes.push(new BaseAttributeSchema(attr));
            }
        }
        this.attributes = _attributes;
    };
    /**
     * @method validateFields
     * @description Valida los campos de entrada y sus opciones
     * @param {BaseFieldSchema|BaseFieldSchema[]} fields
     * @throws {TypeError}
     * @returns {void}
     */
    BaseFormSchema.prototype.validateFields = function (fields) {
        if (!BaseFieldSchema.fieldsValidate(fields)) {
            throw new TypeError("Se esperaba que fields fuera un objeto BaseFieldSchema o BaseFieldSchema[]");
        }
        //Convierte la entrada en un array en caso de no serlo
        var fieldsParam = (Array.isArray(fields) ? fields : [fields]);
        //Campos a agregar
        var _fields = [];
        for (var _i = 0, fieldsParam_1 = fieldsParam; _i < fieldsParam_1.length; _i++) {
            var field = fieldsParam_1[_i];
            if (field.attributes === undefined) { //Prueba si tiene atributos
                field.attributes = [];
            }
            //Conjunto de elementos a los que se les toma en cuenta la opción multiple
            var canBeMultiple = ['select',];
            if (canBeMultiple.indexOf(field.type) != -1) { //Prueba si puede ser múltiple
                field.multiple = field.multiple === true ? true : false;
                if (field.multiple) {
                    field.attributes.push(new BaseAttributeSchema({ name: 'multiple' }));
                }
            }
            var _field = new BaseFieldSchema(field); //Campo
            //Conjunto de elementos que son considerados de selecciones
            var choicesFields = ['choice-radio', 'choice-checkbox', 'select',];
            if (choicesFields.indexOf(field.type) != -1) { //Valida si es un elemento con selecciones
                if (field.choices != undefined) {
                    _field.setChoices(field.choices); //Agrega la selecciones
                }
                else {
                    throw new TypeError("Los campos tipo " + choicesFields.join('|') + " deben tener la opci\u00F3n choices");
                }
            }
            if (field.rules !== undefined) { //Verifica si tiene reglas
                field.rules = Array.isArray(field.rules) ? field.rules : [field.rules]; //Convierte en un array, de no serlo
                for (var _a = 0, _b = field.rules; _a < _b.length; _a++) {
                    var rule = _b[_a];
                    var _rule = {
                        validation: '',
                        prompt: undefined
                    };
                    if (typeof rule == 'string' || typeof rule == 'function') { //En caso de que la regla sea string
                        _rule.validation = rule;
                    }
                    else if (typeof rule == 'object') { //En caso de que la regla sea un objeto
                        _rule.validation = rule.type;
                        if (rule.prompt !== undefined) { //En caso de que tenga un mensaje
                            _rule.prompt = rule.prompt;
                        }
                    }
                    if (_rule.validation === undefined) { //Si no cumplió las validaciones se salta a la otra
                        continue;
                    }
                    this.setValidation(_field, _rule); //Se agrega la regla
                }
            }
            //Se define un valor en caso de estar vacío el campo
            if (field.valueOnEmpty !== undefined) {
                //Si es una función se obtiene el valor
                if (typeof field.valueOnEmpty == 'function') {
                    field.valueOnEmpty = field.valueOnEmpty();
                }
                //Si es un objeto se transforma en string
                if (typeof field.valueOnEmpty == 'symbol' ||
                    typeof field.valueOnEmpty == 'object') {
                    field.valueOnEmpty = JSON.stringify(field.valueOnEmpty);
                }
            }
            else {
                field.valueOnEmpty = ''; //Valor por defecto
            }
            this.fieldsValueOnEmpty[field.name] = field.valueOnEmpty;
            //Se definen los campos de los que dependen el estado del campo
            if (field.dependFields !== undefined) {
                if (!Array.isArray(field.dependFields)) {
                    field.dependFields = [field.dependFields];
                }
                for (var i = 0; i < field.dependFields.length; i++) {
                    var _dField = field.dependFields[i];
                    if (typeof _dField.field != 'string') { //Se valida el campo
                        throw new TypeError("La opcion dependFields.field debe ser tipo string");
                    }
                    if (!BaseFormSchema.isScalar(_dField.value) && typeof _dField.value != 'function') { //Se valida el valor
                        throw new TypeError("La opcion dependFields.value debe ser tipo scalar o una funci\u00F3n que retorne un bool.");
                    }
                }
            }
            else {
                field.dependFields = []; //Valor por defecto
            }
            this.dependFields[field.name] = field.dependFields;
            //Se define si el campo es duplicable
            var duplicable = field.duplicable;
            if (duplicable !== undefined) {
                if (typeof duplicable == 'boolean') {
                    this.fieldsDuplicables[field.name] = duplicable;
                    //Verificar si ya hay valores múltiples para los campos
                    _field.value = '';
                    var values = field.values;
                    if (values === undefined) {
                        values = [];
                    }
                    else {
                        values = Array.isArray(values) ? values : [values];
                    }
                    values = values;
                    values = values.map(function (v) { return (typeof v == 'string' ? v.trim() : v); });
                    values = values.filter(function (v) { return (typeof v == 'string' ? (v.length > 0) : v !== undefined && v !== null); });
                    this.fieldsDuplicablesValues[field.name] = values;
                }
                else {
                    throw new TypeError("La opci\u00F3n duplicable debe ser tipo booleana");
                }
            }
            else {
                this.fieldsDuplicables[field.name] = false; //Valor por defecto
            }
            //Se define si el campo es removible
            var removible = field.removible;
            if (removible !== undefined) {
                if (typeof removible == 'boolean') {
                    this.fieldsRemovibles[field.name] = removible;
                }
                else {
                    throw new TypeError("La opci\u00F3n removible debe ser tipo booleana");
                }
            }
            else {
                this.fieldsRemovibles[field.name] = false; //Valor por defecto
            }
            //Propiedades que no son validadas, sino agregadas como opciones de configuración de los
            //campos que lo requieran
            var ignoreProperties = [
                'name',
                'required',
                'label',
                'type',
                'rules',
                'attributes',
                'choices',
                'valueOnEmpty',
                'multiple',
                'dependFields',
                'duplicable',
                'removible',
                'values',
            ];
            var properties = Object.getOwnPropertyNames(field);
            for (var index in properties) {
                var property = properties[index];
                if (ignoreProperties.indexOf(property) == -1) {
                    if (this.fieldsConfigurations[field.name] == undefined) {
                        this.fieldsConfigurations[field.name] = {};
                    }
                    this.fieldsConfigurations[field.name][property] = field[property];
                }
            }
            _fields.push(_field); //Se añade el campo
        }
        this.fields = _fields; //Se establece la propiedad fields
    };
    /**
     * @method isForm
     * @description Valida que el parámetro concuerde con la estrutura de un objeto BaseFormSchema
     * @param {Object} form
     * @returns {boolean}
     */
    BaseFormSchema.isForm = function (form) {
        if ((form.fields !== undefined) &&
            (form.attributes == undefined ||
                BaseAttributeSchema.isAttribute(form.attributes) ||
                BaseAttributeSchema.isAttributeArray(form.attributes))) {
            return true;
        }
        return false;
    };
    /**
     * @method isScalar
     * @description Valida que el valor sea escalar
     * @param {any} value
     * @returns {boolean}
     */
    BaseFormSchema.isScalar = function (value) {
        return /boolean|number|string/.test(typeof value);
    };
    BaseFormSchema.prototype.duplicateField = function (field, elementBase, containerFields, removible, value, name) {
        if (value === void 0) { value = null; }
        if (name === void 0) { name = null; }
        var newElement = elementBase.cloneNode(true);
        containerFields.appendChild(newElement);
        if (removible) {
            var removeButton = containerFields.querySelectorAll('[remove-button]');
            $(removeButton).parent().click(function (e) {
                e.preventDefault();
            });
            $(removeButton).click(function (e) {
                e.preventDefault();
                var parent = $(this).parent();
                if (parent.prop('tagName') == 'LABEL') {
                    parent = parent.parent();
                }
                parent.remove();
            });
        }
        else {
            var removeButton = document.createElement('button');
            removeButton.setAttribute('remove-button', '');
            removeButton.setAttribute('class', 'ui mini button red');
            removeButton.innerHTML = "<i class=\"trash icon\"></i>Eliminar";
            var hasLabel = newElement.querySelector('label') != null;
            if (hasLabel) {
                var label = newElement.querySelector('label');
                label.innerHTML += ' ' + removeButton.outerHTML;
                removeButton = label.querySelector('[remove-button]');
                $(label).click(function (e) {
                    e.preventDefault();
                });
            }
            else {
                var firstElement = newElement.childNodes[0];
                newElement.insertBefore(removeButton, firstElement);
            }
            $(removeButton).click(function (e) {
                e.preventDefault();
                newElement.remove();
            });
        }
        var _field = $(newElement).find("[name='" + field.name + "']");
        if (name !== null) {
            _field.attr('name', name);
        }
        _field.val('');
        if (value !== null) {
            _field.val(value);
        }
        if (this._form !== undefined)
            this.initValidations();
    };
    BaseFormSchema.debug = false;
    return BaseFormSchema;
}());
var BaseSelectOptionsSchema = /** @class */ (function () {
    function BaseSelectOptionsSchema(name, options, label, required) {
        if (label === void 0) { label = ''; }
        if (required === void 0) { required = false; }
        if (name === undefined)
            throw new Error('Falta el argumento name');
        if (typeof name != 'string')
            throw new TypeError('El argumento name debe ser tipo string');
        this.name = name;
        options = this.clearOptions(options);
        this.options = [];
        var count = 1;
        for (var _i = 0, options_5 = options; _i < options_5.length; _i++) {
            var option = options_5[_i];
            var name_5 = "option_" + count + "_" + this.name;
            var _option = new BaseFieldSchema({
                name: name_5,
                label: option.display,
                value: option.value,
                type: 'option'
            });
            this.options.push(_option);
            count++;
        }
        if (label != undefined && typeof label != 'string')
            throw new TypeError('El argumento label debe ser tipo string');
        this.label = label;
        if (required != undefined && typeof required != 'boolean')
            throw new TypeError('El argumento required debe ser tipo boolean');
        this.required = required;
    }
    Object.defineProperty(BaseSelectOptionsSchema.prototype, "elements", {
        get: function () {
            this.configElement();
            return this._elements;
        },
        enumerable: true,
        configurable: true
    });
    BaseSelectOptionsSchema.prototype.clearOptions = function (options) {
        options = Array.isArray(options) ? options : [options];
        var _options = [];
        _options.push({
            value: '',
            display: 'Seleccione una opción'
        });
        for (var i = 0; i < options.length; i++) {
            var option = options[i];
            if (option.value === undefined)
                throw new TypeError('Se esperaba que option tuviera la propiedad value');
            if (typeof option.value != 'string')
                throw new TypeError('La propiedad value de option debe ser tipo string');
            if (option.display != undefined && typeof option.display != 'string')
                throw new TypeError('La propiedad display de option debe ser tipo string');
            if (option.display == undefined || option.display.length < 1) {
                options[i].display = option.value;
            }
            _options.push(options[i]);
        }
        return _options;
    };
    BaseSelectOptionsSchema.prototype.configElement = function () {
        var options = this.options;
        var _options = [];
        for (var _i = 0, options_6 = options; _i < options_6.length; _i++) {
            var option = options_6[_i];
            _options.push(option.element);
        }
        this._elements = _options;
    };
    return BaseSelectOptionsSchema;
}());
var FormJsonSchema = /** @class */ (function () {
    function FormJsonSchema(schema) {
        if (BaseFormSchema.isForm(schema)) {
            this._schema = new BaseFormSchema({
                fields: schema.fields,
                attributes: schema.attributes
            });
            if (FormJsonSchema.debug) {
                BaseFormSchema.debug = true;
            }
        }
        else {
            throw new Error("Se esperaba que schema fuera un objeto BaseFormSchema");
        }
    }
    FormJsonSchema.prototype.setDebug = function (debug) {
        debug = debug === true ? true : false;
        FormJsonSchema.debug = debug;
        if (FormJsonSchema.debug) {
            BaseFormSchema.debug = true;
        }
        else {
            BaseFormSchema.debug = false;
        }
    };
    Object.defineProperty(FormJsonSchema.prototype, "schema", {
        get: function () {
            return this._schema;
        },
        set: function (param) {
            throw new Error('La propiedad schema es de solo lectura');
        },
        enumerable: true,
        configurable: true
    });
    FormJsonSchema.prototype.form = function () {
        return this._schema.form;
    };
    FormJsonSchema.debug = false;
    return FormJsonSchema;
}());

//# sourceMappingURL=FormJsonSchema.js.map
