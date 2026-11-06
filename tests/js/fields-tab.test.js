/* global describe, it, expect, jest, beforeEach, afterEach */
// React is provided by @wordpress/element; act() is only exported by react itself.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import { createRoot } from '@wordpress/element';

import FieldsTab from '../../src/admin/fields/FieldsTab';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const field = ( slug, extra = {} ) => ( {
	slug,
	label: slug.toUpperCase(),
	type: 'text',
	schema_role: null,
	archived: false,
	...extra,
} );

let container;
let root;

function render( props ) {
	act( () => {
		root.render( <FieldsTab isSaving={ false } { ...props } /> );
	} );
}

const buttonByLabel = ( label ) =>
	Array.from( container.querySelectorAll( 'button' ) ).find(
		( button ) => button.getAttribute( 'aria-label' ) === label
	) ||
	Array.from( container.querySelectorAll( 'button' ) ).find(
		( button ) => button.textContent === label
	);

async function click( button ) {
	await act( async () => {
		button.click();
	} );
}

function type( input, value ) {
	const setter = Object.getOwnPropertyDescriptor(
		window.HTMLInputElement.prototype,
		'value'
	).set;

	act( () => {
		setter.call( input, value );
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	} );
}

async function submit() {
	await act( async () => {
		container
			.querySelector( 'form' )
			.dispatchEvent(
				new Event( 'submit', { bubbles: true, cancelable: true } )
			);
	} );
}

beforeEach( () => {
	container = document.createElement( 'div' );
	document.body.appendChild( container );
	root = createRoot( container );
} );

afterEach( () => {
	act( () => root.unmount() );
	container.remove();
} );

describe( 'FieldsTab', () => {
	it( 'shows an empty state and an add button', () => {
		render( { fields: [], onSave: jest.fn() } );

		expect( container.textContent ).toContain( 'No fields yet' );
		expect( buttonByLabel( 'Add field' ) ).toBeTruthy();
	} );

	it( 'lists fields with type, role and archived state', () => {
		render( {
			fields: [
				field( 'kredit', { schema_role: 'creator' } ),
				field( 'lama', { archived: true } ),
			],
			onSave: jest.fn(),
		} );

		const items = container.querySelectorAll( 'li' );

		expect( items ).toHaveLength( 2 );
		expect( items[ 0 ].textContent ).toContain(
			'Structured data: Creator'
		);
		expect( items[ 1 ].textContent ).toContain( 'Archived' );
		expect( buttonByLabel( 'Restore: LAMA' ) ).toBeTruthy();
		expect( buttonByLabel( 'Archive: KREDIT' ) ).toBeTruthy();
	} );

	it( 'disables the move buttons at the ends of the list', () => {
		render( { fields: [ field( 'a' ), field( 'b' ) ], onSave: jest.fn() } );

		expect( buttonByLabel( 'Move up: A' ).disabled ).toBe( true );
		expect( buttonByLabel( 'Move down: A' ).disabled ).toBe( false );
		expect( buttonByLabel( 'Move down: B' ).disabled ).toBe( true );
	} );

	it( 'saves the new order', async () => {
		const onSave = jest.fn().mockResolvedValue( {} );
		render( { fields: [ field( 'a' ), field( 'b' ) ], onSave } );

		await click( buttonByLabel( 'Move down: A' ) );

		expect( onSave.mock.calls[ 0 ][ 0 ].map( ( f ) => f.slug ) ).toEqual( [
			'b',
			'a',
		] );
		expect( container.textContent ).toContain( 'Order updated.' );
	} );

	it( 'archives a field', async () => {
		const onSave = jest.fn().mockResolvedValue( {} );
		render( { fields: [ field( 'a' ) ], onSave } );

		await click( buttonByLabel( 'Archive: A' ) );

		expect( onSave.mock.calls[ 0 ][ 0 ][ 0 ].archived ).toBe( true );
		expect( container.textContent ).toContain( 'Field archived.' );
	} );

	it( 'shows a message and no list change when saving fails', async () => {
		const onSave = jest.fn().mockRejectedValue( new Error( 'Nope' ) );
		render( { fields: [ field( 'a' ) ], onSave } );

		await click( buttonByLabel( 'Archive: A' ) );

		expect( container.textContent ).toContain( 'Nope' );
	} );

	it( 'blocks an empty form and shows why', async () => {
		const onSave = jest.fn();
		render( { fields: [], onSave } );

		await click( buttonByLabel( 'Add field' ) );
		await submit();

		expect( container.textContent ).toContain( 'Enter a label.' );
		expect( container.textContent ).toContain(
			'Fix the highlighted fields'
		);
		expect( onSave ).not.toHaveBeenCalled();
	} );

	it( 'adds a field without a slug and returns to the list', async () => {
		const onSave = jest.fn().mockResolvedValue( {} );
		render( { fields: [ field( 'a' ) ], onSave } );

		await click( buttonByLabel( 'Add field' ) );
		type( container.querySelector( 'input[type="text"]' ), 'Credit' );
		await submit();

		const saved = onSave.mock.calls[ 0 ][ 0 ];

		expect( saved ).toHaveLength( 2 );
		expect( saved[ 1 ] ).toEqual( {
			label: 'Credit',
			type: 'text',
			schema_role: null,
			archived: false,
		} );
		expect( container.querySelector( 'form' ) ).toBeNull();
		expect( container.textContent ).toContain( 'Field added.' );
	} );

	it( 'locks the type and shows the slug when editing', async () => {
		render( {
			fields: [ field( 'kredit', { type: 'date' } ) ],
			onSave: jest.fn(),
		} );

		await click( buttonByLabel( 'Edit: KREDIT' ) );

		expect( container.querySelector( 'form' ) ).not.toBeNull();
		expect( container.querySelector( 'code' ).textContent ).toBe(
			'kredit'
		);
		expect( container.querySelector( 'select' ).disabled ).toBe( true );
	} );

	it( 'returns to the list on cancel', async () => {
		render( { fields: [ field( 'a' ) ], onSave: jest.fn() } );

		await click( buttonByLabel( 'Edit: A' ) );
		await click( buttonByLabel( 'Cancel' ) );

		expect( container.querySelector( 'form' ) ).toBeNull();
		expect( container.querySelectorAll( 'li' ) ).toHaveLength( 1 );
	} );
} );
