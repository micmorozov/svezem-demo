const $categoryElement = $('.location-form__category')
const $locationElement = $('.location-form__location')

$categoryElement.select2({
    width: "300px",
    allowClear: true,
    //minimumInputLength: 3,
    theme: 'bootstrap',
    "placeholder": "Выберите категорию услуг"
});

$locationElement.select2({
    width: "300px",
    allowClear: true,
    minimumInputLength: 3,
    theme: 'bootstrap',
    ajax: {
        url: '/city/list/',
        dataType: "json",
        data: function (params) {
            return {query: params.term};
        },
        processResults: function (data) {
            return {results: data};
        },
        delay: 250,
        cache: true
    },
    "placeholder": "Выберите город"
});

$locationElement.on('change', (e) => {
    const cityId = e.currentTarget.value;

    $('option', $categoryElement).remove()

    if(! cityId) {
        return
    }

    $categoryElement.attr('disabled', true)

    fetch(`/location/${cityId}/categories/`)
        .then(response => response.json())
        .finally(() => {
            $categoryElement.attr('disabled', false)
        })
        .then(data => {
            $categoryElement.append(new Option())
            data.forEach(el => {
                $categoryElement.append(new Option(el.label, el.url))
            })
            $categoryElement.trigger('change')
        })
});

$categoryElement.on('change', e => {
    if (! e.currentTarget.value) {
        return
    }
    location.href = e.currentTarget.value;
})

$('#locationForm').removeClass('hidden')
