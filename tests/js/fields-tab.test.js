/* global describe, it, expect, jest, beforeEach, afterEach */
import FieldsTab from '../../src/admin/fields/FieldsTab';
import { createHarness } from './helpers';

const field = ( slug, extra = {} ) => ( {
	slug,
	label: slug.toUpperCase(),
	type: 'text',
	schema_role: null,
	archived: false,
	...extra,
} );

let ui;
let container;

function render( props ) {
	ui.render( <FieldsTab isSaving={ false } { ...props } /> );
}

beforeEach( () => {
	ui = createHarness();
	container = ui.container;
} );

afterEach( () => ui.unmount() );

describe( 'FieldsTab', () => {
	it( 'shows an empty state and an add button', () => {
		render( { fields: [], onSave: jest.fn() } );

		expect( container.textContent ).toContain( 'No fields yet' );
		expect( ui.buttonByLabel( 'Add field' ) ).toBeTruthy();
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
		expect( ui.buttonByLabel( 'Restore: LAMA' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Archive: KREDIT' ) ).toBeTruthy();
	} );

	it( 'disables the move buttons at the ends of the list', () => {
		render( { fields: [ field( 'a' ), field( 'b' ) ], onSave: jest.fn() } );

		expect( ui.buttonByLabel( 'Move up: A' ).disabled ).toBe( true );
		expect( ui.buttonByLabel( 'Move down: A' ).disabled ).toBe( false );
		expect( ui.buttonByLabel( 'Move down: B' ).disabled ).toBe( true );
	} );

	it( 'saves the new order', async () => {
		const onSave = jest.fn().mockResolvedValue( {} );
		render( { fields: [ field( 'a' ), field( 'b' ) ], onSave } );

		await ui.click( ui.buttonByLabel( 'Move down: A' ) );

		expect( onSave.mock.calls[ 0 ][ 0 ].map( ( f ) => f.slug ) ).toEqual( [
			'b',
			'a',
		] );
		expect( container.textContent ).toContain( 'Order updated.' );
	} );

	it( 'archives a field', async () => {
		const onSave = jest.fn().mockResolvedValue( {} );
		render( { fields: [ field( 'a' ) ], onSave } );

		await ui.click( ui.buttonByLabel( 'Archive: A' ) );

		expect( onSave.mock.calls[ 0 ][ 0 ][ 0 ].archived ).toBe( true );
		expect( container.textContent ).toContain( 'Field archived.' );
	} );

	it( 'shows a message and no list change when saving fails', async () => {
		const onSave = jest.fn().mockRejectedValue( new Error( 'Nope' ) );
		render( { fields: [ field( 'a' ) ], onSave } );

		await ui.click( ui.buttonByLabel( 'Archive: A' ) );

		expect( container.textContent ).toContain( 'Nope' );
	} );

	it( 'blocks an empty form and shows why', async () => {
		const onSave = jest.fn();
		render( { fields: [], onSave } );

		await ui.click( ui.buttonByLabel( 'Add field' ) );
		await ui.submit();

		expect( container.textContent ).toContain( 'Enter a label.' );
		expect( container.textContent ).toContain(
			'Fix the highlighted fields'
		);
		expect( onSave ).not.toHaveBeenCalled();
	} );

	it( 'adds a field without a slug and returns to the list', async () => {
		const onSave = jest.fn().mockResolvedValue( {} );
		render( { fields: [ field( 'a' ) ], onSave } );

		await ui.click( ui.buttonByLabel( 'Add field' ) );
		ui.type( container.querySelector( 'input[type="text"]' ), 'Credit' );
		await ui.submit();

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

		await ui.click( ui.buttonByLabel( 'Edit: KREDIT' ) );

		expect( container.querySelector( 'form' ) ).not.toBeNull();
		expect( container.querySelector( 'code' ).textContent ).toBe(
			'kredit'
		);
		expect( container.querySelector( 'select' ).disabled ).toBe( true );
	} );

	it( 'returns to the list on cancel', async () => {
		render( { fields: [ field( 'a' ) ], onSave: jest.fn() } );

		await ui.click( ui.buttonByLabel( 'Edit: A' ) );
		await ui.click( ui.buttonByLabel( 'Cancel' ) );

		expect( container.querySelector( 'form' ) ).toBeNull();
		expect( container.querySelectorAll( 'li' ) ).toHaveLength( 1 );
	} );
} );
