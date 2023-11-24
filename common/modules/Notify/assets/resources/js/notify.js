var popUpNotify = {
    notifyRequest: function (page) {
        $.get({
            url: '/notify/message/get/',
            data: {page: page},
            success: function (resp) {
                if (resp.length) {
                    resp.forEach(function (item) {
                        if (popUpNotify.isHidden(item.data.id)) return;

                        item.settings.template = popUpNotify.template(item.data.id);
                        $.notify(item.options, item.settings);
                    });
                }
            }
        });
    },

    template: function (id) {
        return `<div data-notify="container" style="padding-top: 10px;" class="col-xs-11 col-sm-3 alert alert-{0}" role="alert">
        <button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>
        <a href="#" class="pull-right underlined" style="z-index:1050;position:absolute;color:gray;top: 8px;left:16px; font-size:12px" onclick="popUpNotify.doNotShow(` + id + `, this)" >Больше не показывать</a>
        <div class="clearfix"></div>
        <span data-notify="icon"></span>
        <span class="h4" data-notify="title">{1}</span><br/>
        <span data-notify="message">{2}</span>
        <div class="progress" data-notify="progressbar">
            <div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
        </div>
        <a href="{3}" target="{4}" data-notify="url"></a>
        </div>`
    },

    storageNotifyKey: 'hideNotify-',

    doNotShow: function (id, el) {
        $(el).parent().find('[data-notify="dismiss"]').trigger('click');

        localStorage.setItem(popUpNotify.storageNotifyKey + id, 1);
    },

    isHidden: function (id) {
        return localStorage.getItem(popUpNotify.storageNotifyKey + id);
    }
};