$(document).ready(function () {
    $('.header__form-trigger').click(function () {
        $(this).toggleClass('active');
        $('.header__form-wrap').slideToggle('slide');
    });

    $('.offers__toggle').click(function () {
        $(this).toggleClass('active');
        $(this).next().slideToggle('slide');
    });
});

/*  ====================================
 *  Прокрутка страницы
 *  ==================================== */

/**
 * Прокручивает страницу к элементу по ID
 * @param id Id html элемента к которому необходимо прокрутить страницу. По умолчанию "#scrollTo"
 */
function ScrollToElement(el = {id: '#scrollTo', top: 0, padding: true}) {
    let objByName = $(el.id);
    let scrollTop = parseInt(objByName.length ? objByName.offset().top : el.top);

    let offset = parseInt($("body").css("padding-top"));
    if (el.padding) {
        offset += parseInt($(".content").css("padding-top"));
    }

    $('html, body').animate({
        scrollTop: scrollTop - offset
    }, 'fast');
}

// прокрутка по якорю
$(document).on('click', 'a[href^="#"]', function (event) {
    event.preventDefault();
    let destination = $.attr(this, 'href');
    let id = destination.substr(1);

    if (id.length) {
        ScrollToElement({id: '#' + id});
    }
});

// прокрутка в поиске
$(document).on('click', '.btn-search', function (event) {
    ScrollToElement({id:'#scrollTo'});
    $('#search_items').css('opacity', 0.3);
});

// Показываем(скрываем) кнопку Наверх
$(window).scroll(function() {
    if($(this).scrollTop() != 0) {
        $('.footer__gotop').fadeIn();
    } else {
        $('.footer__gotop').fadeOut();
    }
});

/**
 * На страницах поиска на главном домене при клике на кнопку Показать еще
 * дописываем гет параметр page=2
 * @param form
 */
function searchOther(form)
{
    var data = form.serialize();
    if (data) data += '&page=2';
    var url = form.attr('action') + '?' + data;

    var a = $('<a>')
        .attr('href', url);

    form.append(a);
    a.click();
}
