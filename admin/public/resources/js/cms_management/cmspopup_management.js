
var imgloader = "/resources/images/ajax-loader.gif";
var default_promo_cms_banner = $('#default_promo_cms_1').attr('src');
var popupSettings = {};
var popupManager = {
    initialize: function () {

        this.initBannerImageSetting();
        this.initRedirectionSetting();
        $("#contentInput").summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ol', 'ul', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['undo', 'redo', 'fullscreen', 'codeview', 'help']]
            ]
        });

        if (window.location.href.indexOf('addPopup') == -1) {
            var settings = this.getPopupdetails();
            console.log(settings);
            this.assignSettingValue(settings);
        }
    },
    initBannerImageSetting: function () {
        $('#edit_set_default_banner').prop('checked', true).trigger("change");
    },
    initRedirectionSetting: function (type) {
        var target = (type == 'enable' ? '#redirectTo-enable' : '#redirectTo-disable');
        $(target).trigger("click");
    },
    getPopupdetails: function () {
        var settings = {
            "categoryId": popupSettings.categoryId,
            "displayIn": JSON.parse(popupSettings.display_in) || false,
            "displayFreq": JSON.parse(popupSettings.display_freq) || false,
            "redirectTo": popupSettings.redirect_to || 'disable',
            "redirectType": popupSettings.redirect_type,
            "redirectBtnTxt": popupSettings.redirect_btn_name,
            "setEffectTime": popupSettings.is_daterange == '1' ? true : false,
            "startDate": popupSettings.start_date,
            "endDate": popupSettings.end_date,
            "title": popupSettings.title,
            "useDefaultBanner": popupSettings.is_default_banner == '1' || false,
            "bannerUrl": popupSettings.banner_url,
            "content": popupSettings.content || false
        };

        return settings;
    },
    assignSettingValue: function (settings) {
        $('#categoryId').val(settings.categoryId);
        assignDisplayInValue(settings.displayIn);
        assignDisplayFreqValue(settings.displayFreq);
        this.initRedirectionSetting(settings.redirectTo);
        $('#button_link').val(settings.redirectType);
        $('#redirectBtnName').val(settings.redirectBtnTxt);

        //date
        $('input[name="is_daterange"]').prop('checked', settings.setEffectTime);

        $('#datetime_range').data('daterangepicker').setStartDate(settings.startDate);
        $('#datetime_range').data('daterangepicker').setEndDate(settings.endDate);
        $('#start_date').val(settings.startDate);
        $('#end_date').val(settings.endDate);

        $('#title').val(settings.title);
        var popupDetailsDecoded = _pubutils.decodeHtmlEntities(((settings.content)));
        // console.log(popupDetailsDecoded);
        $("#contentInput").code(popupDetailsDecoded);
        $('#edit_set_default_banner').prop('checked', settings.useDefaultBanner).trigger("change");

        if (settings.useDefaultBanner == 1) {
            banner = settings.bannerUrl;
            $('#editBannerUrl').val(banner);
            $('.bannerImgPreview-content').css('background-color', settings.bannerUrl).width(600).height(300);
        } else {
            src = settings.bannerUrl;
            $('#edit_promo_cms_banner_600x300').attr('src', src).width(600).height(300);
            $('#uploadBannerUrl').val(src);
            $('.upload_req_txt').hide();
        }

    }
}

$('#edit_set_default_banner').on('change', function () {
    if ($(this).is(':checked')) {
        $(this).attr('value', 'true');
        $('.upload_req_txt').hide();
        $('.fileUpload').hide();
        $('#edit_upload_req_txt').hide();
        //show preset banner list
        $('.presetBannerType').show();
        $('#isEditDefaultBannerFlag').val(true);
        // $('#edit_promo_cms_banner_600x300').attr('src', default_promo_cms_banner).width(600).height(300);
        setBannerImg($('#single_color_04')[0], 'edit_promo_cms_banner_600x300');
        // setBannerImg($('#default_promo_cms_1')[0], 'edit_promo_cms_banner_600x300');
    } else {
        $(this).attr('value', 'false');
        $('.upload_req_txt').show();
        $('.fileUpload').show();
        var uploadUrl = '';
        var uploadBannerUrl = $('#uploadBannerUrl').val();
        if (uploadBannerUrl) {
            uploadUrl = uploadBannerUrl ? (uploadBannerUrl) : '';
            $('.upload_req_txt').hide();
            console.log(uploadUrl);
        }
        $('#edit_promo_cms_banner_600x300').attr('src', uploadUrl).width(600).height(300).show();
        $('.bannerImgPreview-content').css('background-color', '');
        //hide preset banner list
        $('.presetBannerType').hide();

        $('#isEditDefaultBannerFlag').val(false);
    }
});

function showEditBannerPreview() {
    var bannerPreviewUrl = $('#edit_promo_cms_banner_600x300').attr('src');
    if ($('#edit_set_default_banner').is(':checked')){
        $('#preview_promo_cms_banner_background').css('background-color', $('#editBannerUrl').val()).width(600).height(300);
        $('#preview_promo_cms_banner_600x300').hide();
    } else {

        $('#preview_promo_cms_banner_600x300').attr('src', bannerPreviewUrl).width(600).height(300).show();
    }
}

function showEditPromoCmsPreview() {
    var bannerPreviewUrl = $('#edit_promo_cms_banner_600x300').attr('src');
    $('.priviewTitle').text($('#title').val().toUpperCase());
    $('.previewDetailsTxt').html($("#contentInput").code());
    // $('#preview_promo_cms_banner_600x300_big').attr('src', bannerPreviewUrl).width(600).height(300);

    if ($('#edit_set_default_banner').is(':checked')) {
        $('#preview_promo_cms_banner_background_big').css('background-color', $('#editBannerUrl').val()).width(600).height(300);
        $('#preview_promo_cms_banner_600x300_big').hide();
    } else {

        $('#preview_promo_cms_banner_600x300_big').attr('src', bannerPreviewUrl).width(600).height(300).show();

    }
    if ($('#redirectTo-enable').is(':checked')){
        $('.redriectBtn').removeClass('hide');
        if ($('#redirectBtnName').val() != ''){
            $('.redriectBtn').removeClass('hide');
            $('.redriectBtn>button').text($('#redirectBtnName').val());
        } else {
            var button_link = $('#button_link').val();
            $('.redriectBtn>button').text(document.querySelector('#button_link option[value="'+button_link+'"]').text);
        }
    } else {
        $('.redriectBtn').addClass('hide');
    }
}

function setBannerImg(item, bannerId) {
    bannerType = item.id;
    $('#isEditDefaultBannerFlag').val(true);
    $('#editBannerUrl').val(item.style.backgroundColor);
    $('#edit_upload_req_txt').hide();
    $('#' + bannerId).hide();
    $('.bannerImgPreview-content').css('background-color', item.style.backgroundColor).width(600).height(300);
}

function uploadImage(input, id) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('.upload_req_txt').hide();
            $('#' + id).attr('src', e.target.result).width(600).height(300).show();
            // $('#banner_url').val(input.files[0].name);
            $('#editBannerUrl').val(input.files[0].name);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function assignDisplayInValue(items) {
    if (items[0]) {
        items.forEach((item) => {
            $('#displayIn-op' + item).prop('checked', 1);
        });
    }
}

function assignDisplayFreqValue(items) {
    if (items[0]) {
        items.forEach((item) => {
            $('#displayFreq-op' + item).prop('checked', 1);
        });
    }
}

$("#addNewPopupBtn").on('click', function () {
    var popupDetails = $("#contentInput").code();
    var encodePopupDetails = encode64(encodeURIComponent(popupDetails));
    var popupDetailsLength = encodePopupDetails.length;
    $("#contentInput").code(encodePopupDetails);
    $("#summernoteDetailsLength").val(popupDetailsLength);
    $("#summernoteDetails").val(encodePopupDetails);

    $("#editPopUpForm").submit();
    // return false;
});

$("#cancelEditPopup").on('click', function () {
    $("a#closeEditForm")[0].click();
});


var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
//turn string to base64 encode string
function encode64(input) {
    var output = "";
    var chr1, chr2, chr3 = "";
    var enc1, enc2, enc3, enc4 = "";
    var i = 0;

    do {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);

        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;

        if (isNaN(chr2)) {
            enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
            enc4 = 64;
        }

        output = output +
            keyStr.charAt(enc1) +
            keyStr.charAt(enc2) +
            keyStr.charAt(enc3) +
            keyStr.charAt(enc4);
        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";
    } while (i < input.length);

    return output;
}

/**
 * Decodes base64 string to UTF-8 string, compatibility wrapper
 * Will use _decode64_modern() if atob() is available; otherwise _decode64_legacy() is used, OGP-22312
 * @param   string  data    base64 string
 * @return  string  UTF-8 compatible string
 */
function decode64(data) {
    if (typeof (atob) == 'function') {
        return _decode64_modern(data);
    }

    return _decode64_legacy(data);
}

/**
 * Decodes base64 string to UTF-8 string, modern version
 * Do not use directly; use decode64() instead, OGP-22312
 * Uses atob() function built in most modern browsers
 * @param   string  data    base64 string
 * @return  string  UTF-8 compatible string
 */
function _decode64_modern(data) {
    var decoded = decodeURIComponent(atob(data));
    return decoded;
}

/**
 * Decodes base64 string to utf-8 string, legacy version
 * Do not use directly; use decode64() instead, OGP-22312
 * Adapted from https://simplycalc.com/base64-source.php
 * @param   string  data    base64 string
 * @return  string  UTF-8 compatible string
 */
function _decode64_legacy(data) {
    var b64pad = '=';
    var b64u = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_";

    function base64_charIndex(c) {
        if (c == "+") return 62;
        if (c == "/") return 63;
        return b64u.indexOf(c);
    }

    var dst = "";
    var i, a, b, c, d, z;

    for (i = 0; i < data.length - 3; i += 4) {
        a = base64_charIndex(data.charAt(i + 0));
        b = base64_charIndex(data.charAt(i + 1));
        c = base64_charIndex(data.charAt(i + 2));
        d = base64_charIndex(data.charAt(i + 3));

        dst += String.fromCharCode((a << 2) | (b >>> 4));
        if (data.charAt(i + 2) != b64pad) {
            dst += String.fromCharCode(((b << 4) & 0xF0) | ((c >>> 2) & 0x0F));
        }
        if (data.charAt(i + 3) != b64pad) {
            dst += String.fromCharCode(((c << 6) & 0xC0) | d);
        }
    }

    // dst = decodeURIComponent(escape(dst));
    dst = decodeURIComponent(dst);
    return dst;
}