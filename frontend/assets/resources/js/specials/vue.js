Vue.component('header-line', {
    props: ['isGuest', 'cabinetMenu', 'mainDomain'],
    template:
            '<li class="dropdown" v-if="!isGuest">' +
                '<a href="#" id="dLabel" data-toggle="dropdown" role="button" ' +
                    'aria-haspopup="true" aria-expanded="false" class="">' +
                    '<i class="fa fa-user"></i> Кабинет' +
                    '<span class="caret"></span>' +
                '</a>' +
                '<ul class="dropdown-menu" aria-labelledby="dLabel">' +
                    '<template v-for="item in cabinetMenu">'+
                        '<li>'+
                            '<a v-bind:href="item.url">{{item.title}}</a>'+
                        '</li>'+
                        '<li role="separator" class="divider" v-if="item.separator"></li>' +
                    '</template>'+
                    '<li><a v-bind:href="\'https://\'+mainDomain+\'/account/logout/\'">Выйти</a></li>' +
                '</ul>'+
            '</li>'

});

// Vue.component('header-mobile', {
//     props: ['isGuest', 'cabinetMenu', 'mainDomain', 'selectedDomain'],
//     template:
//         '<div class="header-mobile-nav-wrap">' +
//             '<div class="header-mobile-nav">' +
//                 '<div class="header-mobile-nav__body">' +
//                     '<a :href="\'https://\'+mainDomain+\'/account/login/\'" class = "header-mobile-nav__btn" v-if="isGuest">Войти</a>' +
//                     '<template v-else>' +
//                         '<div class="delimiter" style="margin-top: 10px">' +
//                             '<i class="fa fa-user" style="font-size: 20px;color: #ffffff55;margin-right: 10px;"></i>' +
//                             '<span>Кабинет</span>' +
//                         '</div>' +
//                         '<ul class="header-mobile-nav__nav">' +
//                             '<li>' +
//                                 '<a v-bind:href="\'https://\'+mainDomain+\'/account/logout/\'" class = "header-mobile-nav__btn">Выйти</a>'+
//                             '</li>' +
//                             '<li v-for="item in cabinetMenu">'+
//                                 '<a v-bind:href="item.url">{{item.title}}</a>'+
//                             '</li>'+
//                         '</ul>' +
//                     '</template>' +
//                     '<div class="delimiter"><i style="font-size: 20px;color: #ffffff55;margin-right: 10px;" class="fas fa-box-open"></i><span>Отправителю</span></div>' +
//                     '<ul class="header-mobile-nav__nav">' +
//                         '<li>' +
//                             '<a v-bind:href="selectedDomain+\'/transport/search/\'">Поиск перевозчика</a>'+
//                         '</li>' +
//                         '<li>' +
//                             '<a v-bind:href="selectedDomain+\'/tk/search/\'">Поиск транспортных компаний</a>' +
//                         '</li>' +
//                         '<li>' +
//                             '<a v-bind:href="\'https://\'+mainDomain+\'/tk/price-comparison/\'" rel="nofollow">Сравнение цен транспортных компаний</a>'+
//                         '</li>' +
//                     '</ul>' +
//                     '<div class="delimiter"><i style="font-size: 20px;color: #ffffff55;margin-right: 10px;" class="fas fa-bus"></i><span>Перевозчику</span></div>' +
//                     '<ul class="header-mobile-nav__nav">' +
//                         '<li>' +
//                             '<a v-bind:href="\'https://\'+mainDomain+\'/account/signup-transport/\'" rel="nofollow">Предложить услуги</a>'+
//                         '</li>' +
//                         '<li>' +
//                             '<a v-bind:href="\'https://\'+mainDomain+\'/cargo/passing/\'">Поиск попутного груза</a>'+
//                         '</li>' +
//                         '<li>' +
//                             '<a v-bind:href="selectedDomain+\'/cargo/search/\'">Поиск груза</a>'+
//                         '</li>' +
//                         '<li>' +
//                             '<a v-bind:href="\'https://\'+mainDomain+\'/sub/\'" rel="nofollow">Подписка на новые грузы</a>'+
//                         '</li>' +
//                     '</ul>' +
//                 '</div>' +
//             '</div>' +
//         '</div>'
// });

//Экземпляры шапок разделены на две версии потому что в header.php подключается
//форма добавления груза. Vue перерисовывает шаблон, убивая события формы.
//Поэтому создается два экземпляра с общими данными

//Экземпляр шапки ПК версии
var headMain = new Vue({
    el: '#header',
    data: commonVueData,
    mounted: function () {
        // $('.header__sing-in').hover(
        //     function () {
        //         $('.header__top').addClass('darkBefore');
        //     }, function () {
        //         $('.header__top').removeClass('darkBefore');
        //     }
        // );
        //
        $('.wrapper-dropdown-1').click(function () {
            $(this).toggleClass('active');
        });

        $('.wrapper-dropdown-2').click(function () {
            $(this).toggleClass('active');
        });

        $(".header__mobile-menu").on('click', function () {
            $('.header-mobile-nav-wrap').css("left", "0");
            $('body').addClass('open-modal');
        });

        $(".header-mobile-nav__hum").on('click', function () {
            $('.header-mobile-nav-wrap').css("left", "-100%");
            $('body').removeClass('open-modal');
        });

        $(document).mouseup(function (e) {
            var firstContainer = $(".menu.primary");
            var secondContainer = $(".menu.secondary");
            if (firstContainer.has(e.target).length === 0) {
                firstContainer.children().removeClass('active');
            }
            if (secondContainer.has(e.target).length === 0) {
                secondContainer.children().removeClass('active');
            }
        });
    }
});

//Экземпляр шапки мобильной версии
// new Vue({
//     el: '#mob-header-app',
//     data: commonVueData,
//     mounted: function () {
//         // var checkMobile = function () {
//         //     var isTouch = ('ontouchstart' in document.documentElement);
//         //     if (isTouch) {
//         //         $('body').swipe({
//         //             swipeRight: function () {
//         //                 $('.header-mobile-nav-wrap').css("left", "0");
//         //                 $('body').addClass('open-modal');
//         //             },
//         //             swipeLeft: function () {
//         //                 $('.header-mobile-nav-wrap').css("left", "-100%");
//         //                 $('body').removeClass('open-modal');
//         //             }
//         //         });
//         //     }
//         //
//         // };
//         //
//         // //Execute Check
//         // checkMobile();
//
//         // $('.menu_mask').click(function () {
//         //     $('.header-mobile-nav-wrap').css("left", "-100%");
//         //     $('body').removeClass('open-modal');
//         // })
//     }
// });
