jQuery( document ).ready( function( $ ) {
	var $body = $( 'body' );
	var $mobileMenuTrigger = $( '.js-mobile-menu-trigger' );
	var $mobileMenuClose = $( '.js-mobile-menu-close' );
	var $siteHeader = $( '.js-site-header' );

	// Open/close sub menus
	$( '.has-children', $siteHeader )
		.hover(
			function() {
				$( this ).addClass( 'sub-menu--open' );
			},
			function() {
				$( this ).removeClass( 'sub-menu--open' );
			}
		)
		.on( 'focusin', '> a', function() {
			$( this ).parents( '.has-children' ).addClass( 'sub-menu--open' );
		} );

	$siteHeader.on( 'focusin', 'a', function() {
		var tree = $( this ).parents( '.sub-menu--open' );
		$siteHeader.find( '.sub-menu--open' ).not( tree ).removeClass( 'sub-menu--open' );
	} );

	$mobileMenuTrigger.on( 'click', function( e ) {
		$siteHeader.toggleClass( 'mobile-menu--open' );
		if ( $siteHeader.is( '.mobile-menu--open' ) ) {
			$mobileMenuClose.focus();
		} else {
			$mobileMenuClose.focus();
		}
		e.preventDefault();
	});

	$mobileMenuClose.on( 'click', function() {
		$mobileMenuTrigger.click().focus();

	});

	// Focus management for the mobile menu
	$body.on( 'keyup', function( e ) {
		if ( ! $siteHeader.is( '.mobile-menu--open' ) ) {
			return;
		}

		// Pressing the escape key should close the menu
		if ( 27 === e.keyCode ) {
			$mobileMenuTrigger.click();
		}

		// Check to see if the target is a child of $siteHeader
		// If not then switch the focus
		if ( 1 > $siteHeader.find( e.target ).length ) {
			$mobileMenuClose.focus();
		}
	});

});
