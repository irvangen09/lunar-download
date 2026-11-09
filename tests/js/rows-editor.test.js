/* global describe, it, expect, beforeEach, afterEach */
import { useState } from '@wordpress/element';

import RowsEditor from '../../src/download-info/RowsEditor';
import { createHarness } from './helpers';

const fields = [
	{ slug: 'kredit', label: 'Kredit', type: 'text', archived: false },
	{ slug: 'ukuran', label: 'Ukuran', type: 'text', archived: false },
	{
		slug: 'tipe-bus',
		label: 'Tipe Bus',
		type: 'select',
		options: [ 'Yudistira HD', 'Sadewa SHD' ],
		archived: false,
	},
	{ slug: 'tanggal', label: 'Tanggal', type: 'date', archived: false },
	{ slug: 'lama', label: 'Lama', type: 'text', archived: true },
];

let ui;
let changes;

function Stateful( { initial } ) {
	const [ rows, setRows ] = useState( initial );

	return (
		<RowsEditor
			rows={ rows }
			fields={ fields }
			onChange={ ( next ) => {
				changes.push( next );
				setRows( next );
			} }
		/>
	);
}

const last = () => changes[ changes.length - 1 ];
const items = () => ui.container.querySelectorAll( 'li' );

beforeEach( () => {
	ui = createHarness();
	changes = [];
} );

afterEach( () => ui.unmount() );

describe( 'RowsEditor', () => {
	it( 'shows an empty state and an add button', () => {
		ui.render( <Stateful initial={ [] } /> );

		expect( ui.container.textContent ).toContain( 'No rows yet.' );
		expect( ui.buttonByLabel( 'Add row' ) ).toBeTruthy();
	} );

	it( 'adds a row with the first unused field and focuses its value', async () => {
		ui.render(
			<Stateful initial={ [ { field: 'kredit', value: 'BlahBloh' } ] } />
		);

		await ui.click( ui.buttonByLabel( 'Add row' ) );

		expect( last() ).toEqual( [
			{ field: 'kredit', value: 'BlahBloh' },
			{ field: 'ukuran', value: '' },
		] );
		expect( items() ).toHaveLength( 2 );
		expect( document.activeElement.value ).toBe( '' );
		expect( document.activeElement.closest( 'li' ) ).toBe( items()[ 1 ] );
		expect( document.activeElement.tagName ).toBe( 'INPUT' );
	} );

	it( 'adds a custom label row when every field is used', async () => {
		const all = [ 'kredit', 'ukuran', 'tipe-bus', 'tanggal' ].map(
			( field ) => ( {
				field,
				value: 'x',
			} )
		);
		ui.render( <Stateful initial={ all } /> );

		await ui.click( ui.buttonByLabel( 'Add row' ) );

		expect( last()[ 4 ] ).toEqual( { label: '', value: '' } );
		expect( document.activeElement.closest( 'li' ) ).toBe( items()[ 4 ] );
	} );

	it( 'edits the value of a text row', () => {
		ui.render(
			<Stateful initial={ [ { field: 'kredit', value: '' } ] } />
		);

		ui.type(
			ui.container.querySelector( 'input[type="text"]' ),
			'BlahBloh'
		);

		expect( last() ).toEqual( [ { field: 'kredit', value: 'BlahBloh' } ] );
	} );

	it( 'uses a dropdown for a fixed choice and keeps a removed choice', () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'tipe-bus', value: 'Yudistira HD' },
					{ field: 'tipe-bus', value: 'Lama' },
				] }
			/>
		);

		const selects = ui.container.querySelectorAll( 'select' );
		const valueSelects = [ selects[ 1 ], selects[ 3 ] ];
		const labels = ( select ) =>
			Array.from( select.options ).map( ( o ) => o.text );

		expect( labels( valueSelects[ 0 ] ) ).toEqual( [
			'Choose…',
			'Yudistira HD',
			'Sadewa SHD',
		] );
		expect( labels( valueSelects[ 1 ] ) ).toContain(
			'Lama (no longer in the list)'
		);
		expect( valueSelects[ 1 ].value ).toBe( 'Lama' );

		ui.choose( valueSelects[ 0 ], 'Sadewa SHD' );

		expect( last()[ 0 ] ).toEqual( {
			field: 'tipe-bus',
			value: 'Sadewa SHD',
		} );
	} );

	it( 'uses a date input for a date field', () => {
		ui.render(
			<Stateful
				initial={ [ { field: 'tanggal', value: '2026-10-30' } ] }
			/>
		);

		const date = ui.container.querySelector( 'input[type="date"]' );

		expect( date.value ).toBe( '2026-10-30' );
	} );

	it( 'switches a row to another field and keeps a value that fits', () => {
		ui.render(
			<Stateful initial={ [ { field: 'kredit', value: 'BlahBloh' } ] } />
		);

		ui.choose( ui.container.querySelector( 'select' ), 'ukuran' );

		expect( last() ).toEqual( [ { field: 'ukuran', value: 'BlahBloh' } ] );
	} );

	it( 'switches a row to a custom label and edits the label', () => {
		ui.render(
			<Stateful initial={ [ { field: 'kredit', value: 'x' } ] } />
		);

		ui.choose( ui.container.querySelector( 'select' ), '__custom__' );

		expect( last() ).toEqual( [ { label: '', value: 'x' } ] );

		const label =
			ui.container.querySelectorAll( 'input[type="text"]' )[ 0 ];
		ui.type( label, 'Catatan' );

		expect( last() ).toEqual( [ { label: 'Catatan', value: 'x' } ] );
	} );

	it( 'shows an archived field that a row uses, and not for others', () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'lama', value: 'x' },
					{ field: 'kredit', value: 'y' },
				] }
			/>
		);

		const selects = ui.container.querySelectorAll( 'select' );
		const texts = ( s ) => Array.from( s.options ).map( ( o ) => o.text );

		expect( texts( selects[ 0 ] ) ).toContain( 'Lama (archived)' );
		expect( texts( selects[ 1 ] ) ).not.toContain( 'Lama (archived)' );
	} );

	it( 'gives every button a name that tells which row it acts on', () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'kredit', value: 'a' },
					{ field: 'ukuran', value: 'b' },
				] }
			/>
		);

		expect( ui.buttonByLabel( 'Move up: row 2 (Ukuran)' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Move down: row 1 (Kredit)' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Remove: row 2 (Ukuran)' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Move up: row 1 (Kredit)' ).disabled ).toBe(
			true
		);
		expect( ui.buttonByLabel( 'Move down: row 2 (Ukuran)' ).disabled ).toBe(
			true
		);
	} );

	it( 'moves a row and keeps the focus on the moved row', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'kredit', value: 'a' },
					{ field: 'ukuran', value: 'b' },
					{ field: 'tanggal', value: '' },
				] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Move down: row 1 (Kredit)' ) );

		expect( last().map( ( r ) => r.field ) ).toEqual( [
			'ukuran',
			'kredit',
			'tanggal',
		] );
		expect( document.activeElement.getAttribute( 'aria-label' ) ).toBe(
			'Move down: row 2 (Kredit)'
		);
		expect(
			ui.container.querySelector( '[role="status"]' ).textContent
		).toBe( 'Row moved to position 2.' );
	} );

	it( 'moves the focus to the other direction when a row reaches an edge', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'kredit', value: 'a' },
					{ field: 'ukuran', value: 'b' },
				] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Move up: row 2 (Ukuran)' ) );

		expect( last().map( ( r ) => r.field ) ).toEqual( [
			'ukuran',
			'kredit',
		] );
		expect( document.activeElement.getAttribute( 'aria-label' ) ).toBe(
			'Move down: row 1 (Ukuran)'
		);
	} );

	it( 'removes a row and focuses the next one', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'kredit', value: 'a' },
					{ field: 'ukuran', value: 'b' },
					{ field: 'tanggal', value: '' },
				] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Remove: row 1 (Kredit)' ) );

		expect( last().map( ( r ) => r.field ) ).toEqual( [
			'ukuran',
			'tanggal',
		] );
		expect( document.activeElement.getAttribute( 'aria-label' ) ).toBe(
			'Remove: row 1 (Ukuran)'
		);
	} );

	it( 'focuses the previous row when the last one is removed, and the add button when none is left', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ field: 'kredit', value: 'a' },
					{ field: 'ukuran', value: 'b' },
				] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Remove: row 2 (Ukuran)' ) );

		expect( document.activeElement.getAttribute( 'aria-label' ) ).toBe(
			'Remove: row 1 (Kredit)'
		);

		await ui.click( ui.buttonByLabel( 'Remove: row 1 (Kredit)' ) );

		expect( document.activeElement.textContent ).toBe( 'Add row' );
		expect( ui.container.textContent ).toContain( 'No rows yet.' );
	} );
} );
