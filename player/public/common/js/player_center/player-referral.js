const PlayerReferral = {

    msg_success_copy: "",
    sharingTitle: "",
    sharingText: "",
    sharingURL: "",
    sharingCode: "",
    pcCopyToclipboard: function (id) {
        this.copyToClipboard(id);
        MessageBox.success(this.msg_success_copy);
    },
    mobileCopyToClipboard: function (id) {
        this.copyToClipboard(id);
        $("#suc").show().delay(500).fadeOut();
    },
    mobileCopyShareStringToClipboard: function (id) {
        this.copyToClipboard(id);
        MessageBox.success(this.msg_success_copy);
    },
    copyToClipboard: function (id) {
        var copyElement = document.getElementById(id);
        var range = document.createRange();
        range.selectNode(copyElement);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
    },
    mobileDeviceShare: function (id) {
        let _sharingText = PlayerReferral.sharingText;
        if(navigator?.share){
            navigator.share({
                title: PlayerReferral.sharingTitle,
                text: _sharingText,
                // url: PlayerReferral.sharingURL,
               });
        } else {
            // console.log('navigator.share not found', PlayerReferral.sharingURL);
            PlayerReferral.mobileCopyShareStringToClipboard(id);
        }
    },
    generateShareString: function() {
        PlayerReferral.sharingText = PlayerReferral.sharingText?.replace('[code]', PlayerReferral.sharingCode).replace('[link]', PlayerReferral.sharingURL);
    },
    generateExtraInfo: function (detail) {

        for (const key in detail) {
            // <div class="referral-detail-item" id="referral-detail-username"></div>
            // <div class="referral-detail-item" id="referral-detail-countReferral"></div>
            // <div class="referral-detail-item" id="referral-detail-countAvlibleReferral"></div>
            // <div class="referral-detail-item" id="referral-detail-earnedBonus"></div>
            if (detail.hasOwnProperty(key)) {
                let item = detail[key]
                let title = item?.title;
                let value = item?.value;
                let _itemTemplate = `
                <div class="referral-detail-item" id="referral-detail-${key}">
                    <div class="referral-detail-item-title">${title}</div>
                    <div class="referral-detail-item-value">${value}</div>
                </div>`;

                $('#referral-extrainfo-content').append(_itemTemplate);
            }
        }
        // detail = {
        //     username: {
        //         title: '用戶名',
        //         value: 'test002'
        //     },
        //     countReferral: {
        //         title: '推荐玩家人數',
        //         value: 10
        //     },
        //     countAvlibleReferral: {
        //         title: '有效玩家人數',
        //         value: 9
        //     },
        //     earnedBonus: {
        //         title: '累計獎金',
        //         value: 100
        //     }
        // };
    },
    generateMobileShareBtn: function() {

    }
}