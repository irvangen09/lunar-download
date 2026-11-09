// React is provided by @wordpress/element; act() is only exported by react itself.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import { createRoot } from '@wordpress/element';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

/**
 * Renders into a detached container and offers helpers that act like a user.
 * Call unmount() after each test.
 *
 * @return {Object} Container, render, unmount and interaction helpers.
 */
export function createHarness() {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	const buttons = () => Array.from( container.querySelectorAll( 'button' ) );

	return {
		container,

		render( element ) {
			act( () => {
				root.render( element );
			} );
		},

		unmount() {
			act( () => root.unmount() );
			container.remove();
		},

		// Finds a button by its accessible name, then by its text.
		buttonByLabel( label ) {
			return (
				buttons().find(
					( button ) => button.getAttribute( 'aria-label' ) === label
				) ||
				buttons().find( ( button ) => button.textContent === label )
			);
		},

		async click( button ) {
			await act( async () => {
				button.click();
			} );
		},

		// Sets the value of a controlled input or textarea like typing would.
		type( control, value ) {
			const prototype =
				'TEXTAREA' === control.tagName
					? window.HTMLTextAreaElement.prototype
					: window.HTMLInputElement.prototype;
			const setter = Object.getOwnPropertyDescriptor(
				prototype,
				'value'
			).set;

			act( () => {
				setter.call( control, value );
				control.dispatchEvent(
					new Event( 'input', { bubbles: true } )
				);
			} );
		},

		// Chooses an option of a <select> like a user would.
		choose( select, value ) {
			const setter = Object.getOwnPropertyDescriptor(
				window.HTMLSelectElement.prototype,
				'value'
			).set;

			act( () => {
				setter.call( select, value );
				select.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
			} );
		},

		async submit() {
			await act( async () => {
				container.querySelector( 'form' ).dispatchEvent(
					new Event( 'submit', {
						bubbles: true,
						cancelable: true,
					} )
				);
			} );
		},
	};
}
