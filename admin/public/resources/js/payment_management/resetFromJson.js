
/**
 * ResetFromJson
 * Handle the reset button events
 *
 */
var ResetFromJson = ResetFromJson || {};

ResetFromJson.defaults = {};
ResetFromJson.defaults.formSelector = '#search-form-def';
ResetFromJson.defaults.form_data = {};
/**
 * The callback will be executed after reset the field in the each field of the form.
 *
 * @param jQuery(formSelector) theField$El The selected field with jQuery.
 * @param mix _value The reset value.
 */
ResetFromJson.eachFieldAlwaysCB = function(theField$El, _value){
    // do something after reset a field.
    // usually for trigger the field plugin.
};

/**
 * The callback will be executed after done of reset the form.
 *
 * @param jQuery(formSelector) theForm$El The selected form with jQuery.
 * @param json _form_data the json object for reset.
 */
ResetFromJson.alwaysCB = function(theForm$El, _form_data){
    // do something after reset the form.
    // usually for trigger the fields plugin.
};


ResetFromJson.initialize = function(options){
    var _this = this;
    _this.options = $.extend(true, {}, _this.defaults, options);
    return _this;
}

ResetFromJson.onReady = function(){
    var _this = this;

    _this.options.form_data = _this.formToJSON(_this.options.formSelector);

    $('body').on('submit', _this.options.formSelector, function(e){
        _this.options.form_data = _this.formToJSON(_this.options.formSelector);
    });

    $('body').on('reset', _this.options.formSelector, function(e){
        e.preventDefault();
        _this.resetFromJson();
    });

}

ResetFromJson.formToJSON = function(f) {
    var _this = this;
    var fd = $(f).serializeArray();
    var d = {};
    $(fd).each(function(indexNumber, currObj) {
        var _name = currObj.name;
        var _val = currObj.value;
        if ( typeof(d[_name]) !== 'undefined'){
            if (!Array.isArray(d[_name])) {
                d[_name] = [d[_name]];
            }
            d[_name].push(_val);
        }else{
            d[_name] = _val;
        }
    });
    return d;
}; // EOF ResetFromJson.formToJSON = function(f) {...

ResetFromJson.resetFromJson = function(eachFieldAlwaysCB){
    var _this = this;

    var fs$EL = $(_this.options.formSelector);
    if( typeof(eachFieldAlwaysCB) === 'undefined'){
        eachFieldAlwaysCB = _this.eachFieldAlwaysCB;
    }

    fs$EL.find('input,textarea,select').each(function() {
        var _this$El = $(this);
        var _name = _this$El.prop('name');

        if( typeof(_this.options.form_data[_name]) !== 'undefined'){
            var _value = _this.options.form_data[_name];
            if( _this$El.is('input[type="checkbox"]') ){
                _this.resetFromJsonWithCheckboxInput(_this$El, _value)
            }else if( _this$El.is('input:not([type="checkbox"])') ){
                _this.resetFromJsonWithTextInput(_this$El, _value)
            }else if( _this$El.is('select:has(multiple)') ){
                // multi select
                _this.resetFromJsonWithMultiSelect(_this$El, _value);
            }else if( _this$El.is('select:not([multiple])') ){
                // single select
                _this.resetFromJsonWithSelect(_this$El, _value);
            }
            eachFieldAlwaysCB(_this$El, _value);
        } // EOF if( typeof(form_data[_name]) !== 'undefined'){...
    });

    _this.alwaysCB.apply(null, [fs$EL, _this.options.form_data]);

} // EOF ResetFromJson.resetFromJson = function(eachFieldAlwaysCB){...

ResetFromJson.resetFromJsonWithTextInput = function(the$El, _value){
    the$El.val(_value);
}

ResetFromJson.resetFromJsonWithCheckboxInput = function(the$El, _value){
    if(!!_value){
        the$El.attr('checked', 'checked').prop('checked', 'checked');
    }else{
        the$El.attr('checked', false).prop('checked', false);
    }
}

ResetFromJson.resetFromJsonWithSelect = function(the$El, _value){
    the$El.find('option').attr('selected', false).prop('selected', false); // clear all
    the$El.find('option[value="' + _value + '"]').attr('selected', true).prop('selected', true);
}

ResetFromJson.resetFromJsonWithMultiSelect = function(the$El, _value_list){
    the$El.find('option').attr('selected', false).prop('selected', false); // clear all

    $.each(_value_list, function( intIndex, objValue ){
        the$El.find('option[value="' + objValue + '"]').attr('selected', true).prop('selected', true);
    });
}
