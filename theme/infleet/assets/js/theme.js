/**
 * Comportamentos do tema INFLEET.
 */
( function () {
	'use strict';

	var header = document.querySelector( '.site-header' );

	if ( ! header ) {
		return;
	}

	var aoRolar = function () {
		header.classList.toggle( 'site-header--compact', window.scrollY > 32 );
	};

	window.addEventListener( 'scroll', aoRolar, { passive: true } );
	aoRolar();
}() );
