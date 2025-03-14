var ip_tag_list = ip_tag_list || {};
ip_tag_list.uri = {};
ip_tag_list.langs = {};


ip_tag_list.initial = function(options){
    var _this = this;
    _this.dataTable = null;
    _this.dbg = false; // work in querystring has "dbg=1".

    _this.langs.IP_Tag_Edit = 'IP Tag Edit.';
    _this.langs.IP_Tag_Add = 'IP Tag Add.';

    _this = $.extend(true, {}, _this, options );

    // detect dbg for console.log
    var query = window.location.search.substring(1);
    var qs = _this.parse_query_string(query);
    if ('dbg' in qs
        && typeof (qs.dbg) !== 'undefined'
        && qs.dbg
    ) {
        _this.dbg = true;
    }

    return _this;
}


/**
 * Cloned from promotionDetails.parse_query_string()
 *
 * @param {*} query
 */
 ip_tag_list.parse_query_string = function (query) {
    var vars = query.split("&");
    var query_string = {};
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        var key = decodeURIComponent(pair[0]);
        var value = decodeURIComponent(pair[1]);
        // If first entry with this name
        if (typeof query_string[key] === "undefined") {
            query_string[key] = decodeURIComponent(value);
            // If second entry with this name
        } else if (typeof query_string[key] === "string") {
            var arr = [query_string[key], decodeURIComponent(value)];
            query_string[key] = arr;
            // If third or later entry with this name
        } else {
            query_string[key].push(decodeURIComponent(value));
        }
    }
    return query_string;
}; // EOF parse_query_string

/**
 * Get the Html from tpl.
 *
 * @param {string} selectorStr The selector String of tpl
 * @param {array} regexList The Regex List for replace the real values in tpl.
 * @returns
 */
 ip_tag_list.getTplHtmlWithOuterHtmlAndReplaceAll = function (selectorStr, regexList) {
    var self = this;

    var _outerHtml = '';
    if (typeof (selectorStr) !== 'undefined') {
        _outerHtml = $(selectorStr).html();
    }
    // REF. BY data-results_counter="${results_counter}" data-playerpromoid="${playerpromo_id}"

    if (typeof (regexList) === 'undefined') {
        regexList = [];
    }

    if (regexList.length > 0) {
        regexList.forEach(function (currRegex, indexNumber) {
            // assign playerpromo_id into the tpl
            var regex = currRegex['regex']; // var regex = /\$\{playerpromo_id\}/gi;
            _outerHtml = _outerHtml.replaceAll(regex, currRegex['replaceTo']);// currVal.playerpromo_id);
        });
    }
    return _outerHtml;
} // getTplHtmlWithOuterHtmlAndReplaceAll

/**
 * reference to https://stackoverflow.com/a/11339012
 * @param {array} unindexed_array
 */
 ip_tag_list.getFormData = function (unindexed_array) {
    var _this = this;
    var indexed_array = {};

    $.map(unindexed_array, function (n, i) {
        if (n['name'].lastIndexOf('[]') > -1) {
            var iKey = _this.getExistsCountByNameInArray(indexed_array, n['name']);
            indexed_array[n['name'].replace('[]', '[' + iKey + ']')] = n['value'];
        } else {
            indexed_array[n['name']] = n['value'];
        }

    });

    return indexed_array;
};

ip_tag_list.getExistsCountByNameInArray = function (indexed_array, pName) {
    var iKey = 0;
    Object.keys(indexed_array).forEach(function (key) {
        var _pName = pName.replace('[]', ''); // xxx[] => xxx
        if (key.lastIndexOf(_pName) > -1) {
            iKey++;
        }
    })
    return iKey;
}


ip_tag_list.getTarget$El = function (e) {
    var _this = this;
    return $(e.target);
};

ip_tag_list.invertColor = function (hex, bw) {
    var _this = this;
    if (hex.indexOf('#') === 0) {
        hex = hex.slice(1);
    }
    // convert 3-digit hex to 6-digits.
    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    if (hex.length !== 6) {
        throw new Error('Invalid HEX color.');
    }
    var r = parseInt(hex.slice(0, 2), 16),
        g = parseInt(hex.slice(2, 4), 16),
        b = parseInt(hex.slice(4, 6), 16);
    if (bw) {
        // https://stackoverflow.com/a/3943023/112731
        return (r * 0.299 + g * 0.587 + b * 0.114) > 186
            ? '#000000'
            : '#FFFFFF';
    }
    // invert color components
    r = (255 - r).toString(16);
    g = (255 - g).toString(16);
    b = (255 - b).toString(16);
    // pad each with zeros and return
    return "#" + _this.padZero(r) + _this.padZero(g) + _this.padZero(b);
}


ip_tag_list.padZero = function(str, len) {
    len = len || 2;
    var zeros = new Array(len).join('0');
    return (zeros + str).slice(-len);
}

ip_tag_list.onReady = function(){
    var _this = this;

    $('body').on('hidden.bs.modal','#edit_ip_tag_modal', function(e){
        _this.hidden_edit_ip_tag_modal(e);
    });

    $('body').on('shown.bs.modal','#edit_ip_tag_modal', function(e){
        _this.shown_edit_ip_tag_modal(e);
    });


    $('body').on('hidden.bs.modal','#delete_ip_tag_modal', function(e){
        _this.hidden_delete_ip_tag_modal(e);
    });

    $('body').on('shown.bs.modal','#delete_ip_tag_modal', function(e){
        _this.shown_delete_ip_tag_modal(e);
    });

    $('body').on('click','#myTable .selecte_all', function(e){
        _this.clicked_selecte_all(e);
    });

    $('body').on('click','.addiptag-btn', function(e){
        _this.clicked_addiptag_btn(e);
    });

    $('body').on('click','.editiptag-btn', function(e){
        _this.clicked_editiptag_btn(e);
    });

    $('body').on('click','#edit_ip_tag_modal .submit-btn', function(e){
        _this.clicked_edit_ip_tag_modal_submit_btn(e);
    });

    $('body').on('click','.deliptag-btn', function(e){
        _this.clicked_deliptag_btn(e);
    });

    $('body').on('click','.delete_selected', function(e){
        _this.clicked_delete_selected(e);
    });

    $('body').on('click','#delete_ip_tag_modal .submit-btn', function(e){
        _this.clicked_delete_ip_tag_modal_submit_btn(e);
    });


    $('body').on('click','.search_btn', function(e){
        _this.clicked_search_btn(e);
    });

}; // EOF ip_tag_list.onReady = function(){...


// the delegate motheds


ip_tag_list.clicked_selecte_all = function(e){
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var _dataTable$El = target$El.closest('table');

    var listCheckbox$Els = _dataTable$El.find('[data-ip_tag_list_id]:checkbox');

    var _to_select_all = false;
    if( listCheckbox$Els.length == listCheckbox$Els.filter(':checked').length ){
        _to_select_all = false;
    }else{
        _to_select_all = true;
    }

    /// sync the select_all checkbox and the checkbox of list
    if(_to_select_all){
        target$El.attr('checked', 'checked');
        target$El.prop('checked', true);
    }else{
        target$El.prop('checked', false);
        target$El.removeAttr('checked');
    }

    /// control to the checkbox of list by var, _to_select_all.
    if(_to_select_all){
        // checke
        listCheckbox$Els.attr('checked', 'checked');
        listCheckbox$Els.prop('checked', true);
    }else{
        // dis-checke
        listCheckbox$Els.prop('checked', false);
        listCheckbox$Els.removeAttr('checked');
    }
};

ip_tag_list.clicked_search_btn = function(e){
    var _this = this;
    _this.dataTable.ajax.reload();

};

ip_tag_list.clicked_addiptag_btn = function(e){
    var _this = this;
    // var target$El = _this.getTarget$El(e);
    // var modal$El = $('#edit_ip_tag_modal');
    $('#edit_ip_tag_modal').modal('show');
};

ip_tag_list.clicked_editiptag_btn = function(e){
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetWrapper$El = target$El.closest('.ip_tag_action_wrapper');
    var ip_tag_id = targetWrapper$El.data('id');

    var _rowData = {};
    var _list = _this.getDataListByIdList([ip_tag_id], e);
    if( _list.length > 0){
        _rowData = _list[0];
    }

    var modal$El = $('#edit_ip_tag_modal');

    modal$El.find('[name="ip_tag_list_id"]').val(ip_tag_id);
    if( ! $.isEmptyObject(_rowData) ){
        modal$El.find('[name="name"]').val(_rowData.name);
        modal$El.find('[name="ip"]').val(_rowData.ip);
        modal$El.find('[name="description"]').val(_rowData.description);
        modal$El.find('[name="color"]').val(_rowData.color);
        modal$El.find('[name="color"]').trigger('change');
    }

    $('#edit_ip_tag_modal').modal('show');
};  // EOF ip_tag_list.clicked_editiptag_btn = function(e){...



ip_tag_list.clicked_delete_selected = function(e){
    var _this = this;

    var _will_delete_id_list = [];
    var target$El = _this.getTarget$El(e);
    var targetWrapper$El = target$El.closest('.ip_tag_action_wrapper');
    if( ! $.isEmptyObject( target$El.closest('div').find('.delete_selected') ) ){
        // delete by checkboxs
        // .delete_selected
        $(':checkbox[name="tag[]"]:checked').each(function(){
            _will_delete_id_list.push($(this).val());
        });
    }
    var _list = _this.getDataListByIdList(_will_delete_id_list, e);

    var modal$El = $('#delete_ip_tag_modal');
    if( ! $.isEmptyObject(_list) ){
        _this.script_assign_to_will_delete_list_wrapper_by_list( _list );

        modal$El.modal('show');
    }

};

ip_tag_list.clicked_deliptag_btn = function(e){
    var _this = this;

    var _will_delete_id_list = [];
    var target$El = _this.getTarget$El(e);
    var targetWrapper$El = target$El.closest('.ip_tag_action_wrapper');
    if( ! $.isEmptyObject(targetWrapper$El) ){
        _will_delete_id_list.push(targetWrapper$El.data('id'));
    }

    var _list = _this.getDataListByIdList(_will_delete_id_list, e);

    var modal$El = $('#delete_ip_tag_modal');

    if( ! $.isEmptyObject(_list) ){
        _this.script_assign_to_will_delete_list_wrapper_by_list( _list );

        modal$El.modal('show');
    }

};  // EOF ip_tag_list.clicked_deliptag_btn = function(e){...


ip_tag_list.script_assign_to_will_delete_list_wrapper_by_list = function(_list){
    var _this = this;
    var modal$El = $('#delete_ip_tag_modal');
    if( ! $.isEmptyObject(_list) ){

        $.each(_list, function(indexNumber, currVal){
            var nIndex = -1;
            var regexList = [];
            // param, ip_tag_id, ip_tag_ip, ip_tag_name
            nIndex++; // #1 ip_tag_id
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{ip_tag_id\}/gi; // ${ip_tag_id}
            regexList[nIndex]['replaceTo'] = currVal.id;
            nIndex++; // #1 ip_tag_id
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{ip_tag_ip\}/gi; // ${ip_tag_ip}
            regexList[nIndex]['replaceTo'] = currVal.ip;
            nIndex++; // #1 ip_tag_id
            regexList[nIndex] = {};
            regexList[nIndex]['regex'] = /\$\{ip_tag_name\}/gi; // ${ip_tag_name}
            regexList[nIndex]['replaceTo'] = currVal.name;

            var _will_delete_wrapper_html = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-will_delete_wrapper', regexList);

            modal$El.find('.will_delete_list_wrapper').append(_will_delete_wrapper_html);
        });
    }
};

ip_tag_list.getDataListByIdList = function(_will_delete_id_list, e){
    var _this = this;
    var _list = [];
    if( _will_delete_id_list.length > 0){
        $.each(_will_delete_id_list, function(indexNumber, currVal){
            var _curr_tr = $(':checkbox[data-ip_tag_list_id][value="'+ currVal+ '"]').closest('tr').eq(0);
            var idx = _this.dataTable.row( _curr_tr ).index();
            var _rowData = _this.dataTable.row( idx ).data();
            var _curr = {};
            _curr.id = _rowData[0]; // id
            _curr.name = _rowData[1]; // name
            _curr.ip = _rowData[2]; // ip
            _curr.description = _rowData[3]; // description
            _curr.color = _rowData[4];// color
            _list.push(_curr);
        });
    }
    return _list;
}

ip_tag_list.shown_delete_ip_tag_modal = function(e){
    var _this = this;
    // var modal$El = $('#delete_ip_tag_modal');
};


ip_tag_list.hidden_delete_ip_tag_modal = function(e){
    var _this = this;
    // var modal$El = $('#delete_ip_tag_modal');

    _this.resetInDeleteForm();
};

ip_tag_list.resetInDeleteForm = function(){
    var _this = this;
    var modal$El = $('#delete_ip_tag_modal');

    modal$El.find('.will_delete_list_wrapper').empty();
};

ip_tag_list.shown_edit_ip_tag_modal = function(e){
    var _this = this;

    var modal$El = $('#edit_ip_tag_modal');

    if( modal$El.find('[name="ip_tag_list_id"]').val() == ''){
        modal$El.find('.modal-title').html(_this.langs.IP_Tag_Add);
    }else{
        modal$El.find('.modal-title').html(_this.langs.IP_Tag_Edit);
    }

}; // EOF ip_tag_list.shown_edit_ip_tag_modal = function(e){...


ip_tag_list.hidden_edit_ip_tag_modal = function(e){
    var _this = this;
    _this.resetInEditForm();
    _this.validationResetInEditForm();
}; // EOF ip_tag_list.hidden_edit_ip_tag_modal = function(e){...

ip_tag_list.resetInEditForm = function(){
    var _this = this;
    var modal$El = $('#edit_ip_tag_modal');
    /// clear all field
    modal$El.find('[name="ip_tag_list_id"]').val('');
    modal$El.find('[name="name"]').val('');
    modal$El.find('[name="ip"]').val('');
    modal$El.find('[name="color"]').val('');
    modal$El.find('[name="description"]').val('');

};

ip_tag_list.validationResetInEditForm = function(){
    var _this = this;
    var modal$El = $('#edit_ip_tag_modal');
    var _invalid_prompt$Els = modal$El.find('.invalid-prompt');
    _invalid_prompt$Els.html('');
    _invalid_prompt$Els.addClass('hide');
};


ip_tag_list.clicked_delete_ip_tag_modal_submit_btn = function(e){
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetBtn$El = target$El.closest('.modal-footer').find('.submit-btn');
    var theUri = $('#delete_ip_tag_modal .delete-form').prop('action');
    var modal$El = $('#delete_ip_tag_modal');
    var form$El = modal$El.find('.delete-form');

    var serializeData = form$El.serializeArray();
    var theData = _this.getFormData(serializeData);

    var jqXHR = $.ajax({
        type: 'POST', // for delete a detail
        url: theUri,
        data: theData,
        beforeSend: function () {
            targetBtn$El.button('loading');
        },
        complete: function () {
            targetBtn$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.dataTable.ajax.reload(null, false); // user paging is not reset on reload
        modal$El.modal('hide');
        // _this.validationResetInEditForm();
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            // modal$El.modal('show'); // TODO
        }
    });
}; // EOF clicked_delete_ip_tag_modal_submit_btn

ip_tag_list.clicked_edit_ip_tag_modal_submit_btn = function(e){
    var _this = this;
    var target$El = _this.getTarget$El(e);
    var targetBtn$El = target$El.closest('.modal-footer').find('.submit-btn');
    var theUri = $('#edit_ip_tag_modal .edit-form').prop('action');

    var modal$El = $('#edit_ip_tag_modal');

    var form$El = modal$El.find('.edit-form');
    var serializeData = form$El.serializeArray();
    var theData = _this.getFormData(serializeData);

    var jqXHR = $.ajax({
        type: 'POST', // for delete a detail
        url: theUri,
        data: theData,
        beforeSend: function () {
            _this.validationResetInEditForm();
            targetBtn$El.button('loading');
        },
        complete: function () {
            targetBtn$El.button('reset');
        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        if(data.status == 'failed'){
            var _invalids = data.message;

            $.each(_invalids, function(keyStr, currVal){
                var _invalid_prompt$El = modal$El.find('input[name="'+ keyStr+ '"]').closest('div.field_input_wrapper').find('.invalid-prompt');
                _invalid_prompt$El.html(currVal);
                _invalid_prompt$El.removeClass('hide');
            });

        }else{
            _this.dataTable.ajax.reload(null, false); // user paging is not reset on reload
            modal$El.modal('hide');

        }
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        if (errorThrown == 'Forbidden') {
            // modal$El.modal('show'); // TODO
        }
    });
}; // EOF clicked_edit_ip_tag_modal_submit_btn
