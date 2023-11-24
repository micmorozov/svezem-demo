$('.cargo-list__search-input').autocomplete({
    serviceUrl: '/city/locationdd-list/',
    deferRequestBy: 200,
    transformResult: function(response) {
        return {
            suggestions: JSON.parse(response)
        }
    },
    onSelect: function (suggestion){
        window.location.href = '/' + suggestion.code + '/?set_region=' + suggestion.code;
    }
});