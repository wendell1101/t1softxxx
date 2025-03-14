function getParam(val) {
    var result = "",
        tmp = [];
    var items = location.search.substr(1).split("&");
    for (var index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
    }
    return result;
}
$(document).ready(function () {

    var url_clickid = getParam('clickid');
    var url_pub_id = getParam('pud_id');
    var url_rec = getParam('rec');

    if (url_clickid) {
        $('#clickid').val(url_clickid);
    }
    if (url_pub_id) {
        $('#pub_id').val(url_pub_id);
    }
    if (url_rec) {
        $('#url_rec').val(url_rec);
    }
});