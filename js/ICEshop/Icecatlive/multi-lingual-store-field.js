/**
 *
 * @param configObj
 * @returns {*}
 * @constructor
 */
function MultiLingualStoreField(configObj) {
    var self = this;
    if (typeof configObj != 'undefined') {
        jQuery.extend(self.config, configObj);
    }
    return this;
};

/**
 *
 * @type {{storeViewsEl: null, languagesEl: null, mainEl: null, rowIdentifier: string}}
 */
MultiLingualStoreField.prototype.config = {
    storeViewsEl: null,
    languagesEl: null,
    mainEl: null,
    fieldsSet: null,
    visibilityToggler: null,
    temporaryStorage: {},
    emptyRowHtml: '<tr><td class="label"><label for=""></label></td><td class="value"></td><td></td><td></td></tr>',
    rowIdentifier: 'row_'
};

MultiLingualStoreField.prototype.languageField = {
    label: null,
    html: null
};

MultiLingualStoreField.prototype.stores = [
    {
        id: 9999,
        label: 'Insurance language'
    }
];

/**
 *
 */
MultiLingualStoreField.prototype.buildForm = function () {
    var self = this;
    //return this;
    self.rebuldMainField();
    self.fetchLanguageField();
    self.fetchStores();

    self.generateFields();
    self.bindHandler();

    return this;
};

/**
 *
 * @returns {*}
 */
MultiLingualStoreField.prototype.rebuldMainField = function () {
    var self = this;
    var handler = self.config.mainEl;
    if (handler != null) {
        //run rebuilding main field
        handler.hide(0);
    }
    return this;
};

/**
 *
 * @returns {*}
 */
MultiLingualStoreField.prototype.fetchLanguageField = function () {
    var self = this;
    var handler = self.config.languagesEl;
    if (handler != null) {
        var field_handler = handler.find('#' + handler.attr('id').slice(self.config.rowIdentifier.length)).clone();
        field_handler.attr({class: null, name: null, id: null});
        self.languageField.html = field_handler[0].outerHTML;
        self.languageField.label = handler.find('td.label').text();
        handler.hide(0);
    }
    return this;
};

/**
 *
 * @returns {*}
 */
MultiLingualStoreField.prototype.fetchStores = function () {
    var self = this;
    var handler = self.config.storeViewsEl;
    if (handler != null) {
        var options_handler = handler.find('#' + handler.attr('id').slice(self.config.rowIdentifier.length) + ' option');

        var options = jQuery.map(options_handler, function (option) {
            var joption = jQuery(option);
            return {
                id: joption.attr('value'),
                label: joption.text()
            };
        });
        jQuery.merge(self.stores, options);
        handler.hide(0);
    }
    return this;
};

/**
 *
 * @returns {*}
 */
MultiLingualStoreField.prototype.generateFields = function () {
    var self = this;
    if (self.stores.length > 0 && self.config.fieldsSet != null) {
        var saved_values = self.config.mainEl.find('input:text').val();
        if (saved_values.length > 0) {
            saved_values = jQuery.parseJSON(saved_values);
        }

        var visibility = self.checkMultilingualVisibility();
        for (var i = 0; i < self.stores.length; i++) {
            //TODO build fields
            var store_item = self.stores[i];
            var new_element = jQuery(self.config.emptyRowHtml);

            new_element
                .find('td.value')
                .html(self.languageField.html)
                .find('select')
                .attr({id: 'store-' + store_item.id});

            if (visibility == false) {
                new_element.css('display', 'none');
            }

            if (typeof saved_values != 'undefined') {
                var result = jQuery.grep(saved_values, function(el){ return el.store_id == store_item.id });
                if (result.length > 0) {
                    new_element
                        .find('select')
                        .val(result[0]['value']);
                }
            }

            new_element
                .find('label')
                .text(store_item.label)
                .end()
                .attr({id: 'store-' + store_item.id});

            self.config.fieldsSet
                .find('tbody')
                .append(new_element);
        }
    }
    return this;
};

/**
 *
 * @returns {boolean}
 */
MultiLingualStoreField.prototype.checkMultilingualVisibility = function () {
    return (parseInt(this.config.visibilityToggler.find('select').val()) != 0);
};

/**
 *
 * @returns {*}
 */
MultiLingualStoreField.prototype.bindHandler = function () {
    var self = this;
    self
        .config
        .visibilityToggler
        .find('#' + self.config.visibilityToggler.attr('id').slice(self.config.rowIdentifier.length))
        .on('change', jQuery.proxy(self.visibilityChangeHandler, null, self.config));

    self.config.fieldsSet
        .find('tbody')
        .find('select[id^=store-]')
        .on('change', jQuery.proxy(self.changeHandler, null, self.config));
    return this;
};

MultiLingualStoreField.prototype.visibilityChangeHandler = function (config, e) {
    var visibility = jQuery(this).val();
    if (visibility == 0) {
        config.temporaryStorage.mainVal = config.mainEl.find('input:text').val();
        config.mainEl.find('input:text').val(null);

        //if value - 0, hide all custom fields
        config.fieldsSet
            .find('tbody')
            .find('tr[id^=store-]')
            .hide(0);
    } else {
        //if value - 1, hide all service fields
        config.fieldsSet
            .find('tbody')
            .find('select[id^=store-]')
            .attr('disabled', null)
            .end()
            .find('tr[id^=store-]')
            .show(0);

        config.mainEl.find('input:text').val(config.temporaryStorage.mainVal);

        config.mainEl.hide(0);
        config.languagesEl.hide(0);
        config.storeViewsEl.hide(0);

        //TODO prevent using hardcode
        config.fieldsSet.find('select[id^=store-]').each(function(i, el){
            var handler = jQuery(this);
            handler.trigger('change');
            return false;
        });
    }

    e.preventDefault();
};

/**
 *
 * @param config
 * @param e
 */
MultiLingualStoreField.prototype.changeHandler = function (config, e) {
    var values_field_container = config.mainEl;
    var values = jQuery.map(config.fieldsSet.find('select[id^=store-]'), function (element) {
        var el_handler = jQuery(element);
        return {
            store_id: parseInt(el_handler.attr('id').slice('store-'.length)),
            value: el_handler.val()
        };
    });
    values_field_container.find('input:text').val(JSON.stringify(values));
    e.preventDefault();
};