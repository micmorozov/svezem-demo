$.fn.dotTpl = function(tmplId, data) {
    var tmpl = doT.template($(tmplId).html());
    if (!$.isArray(data)) data = [data];

    var html = '';
    for (var itemIdx = 0; itemIdx < data.length; itemIdx++) {
        html += tmpl(data[itemIdx]);
    }

    return this.each(function() {
        $(this).html(html);
    });
};