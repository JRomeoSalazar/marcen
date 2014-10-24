/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
(function ( $ ) {
    'use strict';

    $(document).ready(function() {

        $('.sylius-different-billing-address-trigger').click(function() {
            $('#sylius-billing-address-container').toggleClass('hidden');
        });

        $("select[data-select='selectize']").selectize();

        // Aceptar cookies
		$.cookieCuttr({
			cookieAnalytics: false,
			cookieMessage: 'Nosotros utilizamos cookies para brindarle la mejor experiencia posible en nuestro sitio. Al seguir utilizando nuestro sitio usted acepta nuestra <a href="{{cookiePolicyLink}}" title="Política de cookies">Política de cookies</a> y el uso de las mismas.',
			cookiePolicyLink: $cookies,
			cookieAcceptButtonText: 'Aceptar cookies'
		});

        // Mostar y ocultar taxonomías
        $('h5#menu-categories').click(function() {
            $('ul.taxonomies').slideToggle();
            $('h4.newest-header').slideToggle();
        });

        // Mostrar y ocultar taxones
        $('span.nav-header').click(function() {
            var $id = $(this).attr('id');
            $('ul[data-id="' + $id + '"]').slideToggle();
        });

        // Mostrar y ocultar novedades
        $('h4.newest-header').click(function() {
            $('ul.newest').slideToggle();
        });

    });

})( jQuery );
