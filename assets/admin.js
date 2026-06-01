( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {

		// --- App password: strip spaces as the user types ---
		var passField = document.getElementById( 'egws_app_password' );
		if ( passField ) {
			passField.addEventListener( 'input', function () {
				var pos   = this.selectionStart;
				var raw   = this.value;
				var clean = raw.replace( / /g, '' );
				if ( clean !== raw ) {
					// Count spaces before the cursor so we can adjust the caret.
					var spacesBefore = ( raw.slice( 0, pos ).match( / /g ) || [] ).length;
					this.value          = clean;
					this.selectionStart = pos - spacesBefore;
					this.selectionEnd   = pos - spacesBefore;
				}
			} );
		}

		// --- Toggle password visibility ---
		document.querySelectorAll( '.egws-toggle-pass' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var targetId = this.getAttribute( 'data-target' );
				var field    = document.getElementById( targetId );
				var icon     = this.querySelector( '.dashicons' );
				if ( ! field ) return;

				if ( 'password' === field.type ) {
					field.type = 'text';
					icon.classList.remove( 'dashicons-visibility' );
					icon.classList.add( 'dashicons-hidden' );
					this.setAttribute( 'aria-label', 'Hide password' );
				} else {
					field.type = 'password';
					icon.classList.remove( 'dashicons-hidden' );
					icon.classList.add( 'dashicons-visibility' );
					this.setAttribute( 'aria-label', 'Show password' );
				}
			} );
		} );

		// --- Port ↔ Encryption auto-sync ---
		var portSel = document.getElementById( 'egws_port' );
		var encSel  = document.getElementById( 'egws_encryption' );
		if ( portSel && encSel ) {
			portSel.addEventListener( 'change', function () {
				if ( '465' === this.value ) {
					encSel.value = 'ssl';
				} else {
					encSel.value = 'tls';
				}
			} );
			encSel.addEventListener( 'change', function () {
				if ( 'ssl' === this.value ) {
					portSel.value = '465';
				} else {
					portSel.value = '587';
				}
			} );
		}

	} );
} )();
