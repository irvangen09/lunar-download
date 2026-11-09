/* global describe, it, expect, beforeEach, afterEach */
import { useState } from '@wordpress/element';

import LinksEditor from '../../src/download-info/LinksEditor';
import { createHarness } from './helpers';

let ui;
let changes;

function Stateful( { initial } ) {
	const [ links, setLinks ] = useState( initial );

	return (
		<LinksEditor
			links={ links }
			onChange={ ( next ) => {
				changes.push( next );
				setLinks( next );
			} }
		/>
	);
}

const last = () => changes[ changes.length - 1 ];
const items = () => ui.container.querySelectorAll( 'li' );
const inputs = ( item ) =>
	item.querySelectorAll( 'input[type="text"], input[type="url"]' );

beforeEach( () => {
	ui = createHarness();
	changes = [];
} );

afterEach( () => ui.unmount() );

describe( 'LinksEditor', () => {
	it( 'shows an empty state and an add button', () => {
		ui.render( <Stateful initial={ [] } /> );

		expect( ui.container.textContent ).toContain( 'No links yet.' );
		expect( ui.buttonByLabel( 'Add link' ) ).toBeTruthy();
	} );

	it( 'adds a link and focuses its button text', async () => {
		ui.render( <Stateful initial={ [] } /> );

		await ui.click( ui.buttonByLabel( 'Add link' ) );

		expect( last() ).toEqual( [ { url: '' } ] );
		expect( document.activeElement.getAttribute( 'placeholder' ) ).toBe(
			'Download'
		);
	} );

	it( 'edits the text and the address, and saves only what differs from the default', () => {
		ui.render( <Stateful initial={ [ { url: '' } ] } /> );

		const [ text, address ] = inputs( items()[ 0 ] );
		ui.type( text, 'Bersih' );
		ui.type( address, 'https://drive.google.com/file/d/1' );

		expect( last() ).toEqual( [
			{ label: 'Bersih', url: 'https://drive.google.com/file/d/1' },
		] );

		ui.type( inputs( items()[ 0 ] )[ 0 ], '' );

		expect( last()[ 0 ] ).toEqual( {
			url: 'https://drive.google.com/file/d/1',
		} );
	} );

	it( 'warns about an address that will not be shown', () => {
		ui.render(
			<Stateful initial={ [ { url: 'javascript:alert(1)' } ] } />
		);

		const address = ui.container.querySelector( 'input[type="url"]' );

		expect( address.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( ui.container.textContent ).toContain(
			'This link will not be shown.'
		);
	} );

	it( 'does not warn about an address that is fine or still empty', () => {
		ui.render(
			<Stateful initial={ [ { url: '/download/a' }, { url: '' } ] } />
		);

		expect( ui.container.textContent ).not.toContain(
			'This link will not be shown.'
		);
	} );

	it( 'hides the options until asked and stores them only when they differ', async () => {
		ui.render( <Stateful initial={ [ { url: '/a' } ] } /> );

		expect( ui.container.querySelectorAll( 'select' ) ).toHaveLength( 0 );

		const toggle = ui.buttonByLabel( 'Options' );
		await ui.click( toggle );

		expect( toggle.getAttribute( 'aria-expanded' ) ).toBe( 'true' );

		ui.choose( ui.container.querySelector( 'select' ), 'nofollow' );

		expect( last() ).toEqual( [ { url: '/a', relMode: 'nofollow' } ] );

		await ui.click(
			ui.container.querySelector( 'input[type="checkbox"]' )
		);

		expect( last() ).toEqual( [
			{ url: '/a', relMode: 'nofollow', newTab: true },
		] );

		ui.choose( ui.container.querySelector( 'select' ), 'auto' );
		await ui.click(
			ui.container.querySelector( 'input[type="checkbox"]' )
		);

		expect( last() ).toEqual( [ { url: '/a' } ] );
	} );

	it( 'shows the stored options when they are opened', async () => {
		ui.render(
			<Stateful
				initial={ [ { url: '/a', relMode: 'follow', newTab: true } ] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Options' ) );

		expect( ui.container.querySelector( 'select' ).value ).toBe( 'follow' );
		expect(
			ui.container.querySelector( 'input[type="checkbox"]' ).checked
		).toBe( true );
	} );

	it( 'names every button after the link it acts on', () => {
		ui.render(
			<Stateful
				initial={ [ { url: '/a', label: 'Bersih' }, { url: '/b' } ] }
			/>
		);

		expect( ui.buttonByLabel( 'Move down: link 1 (Bersih)' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Move up: link 2 (Download)' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Remove: link 2 (Download)' ) ).toBeTruthy();
		expect( ui.buttonByLabel( 'Move up: link 1 (Bersih)' ).disabled ).toBe(
			true
		);
	} );

	it( 'moves a link and keeps the focus on it', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ url: '/a', label: 'A' },
					{ url: '/b', label: 'B' },
					{ url: '/c', label: 'C' },
				] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Move down: link 1 (A)' ) );

		expect( last().map( ( l ) => l.label ) ).toEqual( [ 'B', 'A', 'C' ] );
		expect( document.activeElement.getAttribute( 'aria-label' ) ).toBe(
			'Move down: link 2 (A)'
		);
	} );

	it( 'keeps the options of a link open when it is moved', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ url: '/a', label: 'A' },
					{ url: '/b', label: 'B' },
					{ url: '/c', label: 'C' },
				] }
			/>
		);

		await ui.click(
			ui.container
				.querySelectorAll( 'li' )[ 0 ]
				.querySelector( '.is-link' )
		);
		await ui.click( ui.buttonByLabel( 'Move down: link 1 (A)' ) );

		const second = items()[ 1 ];

		expect( second.querySelector( 'select' ) ).not.toBeNull();
		expect( items()[ 0 ].querySelector( 'select' ) ).toBeNull();
	} );

	it( 'removes a link and focuses the next one, or the add button when none is left', async () => {
		ui.render(
			<Stateful
				initial={ [
					{ url: '/a', label: 'A' },
					{ url: '/b', label: 'B' },
				] }
			/>
		);

		await ui.click( ui.buttonByLabel( 'Remove: link 1 (A)' ) );

		expect( last().map( ( l ) => l.label ) ).toEqual( [ 'B' ] );
		expect( document.activeElement.getAttribute( 'aria-label' ) ).toBe(
			'Remove: link 1 (B)'
		);

		await ui.click( ui.buttonByLabel( 'Remove: link 1 (B)' ) );

		expect( document.activeElement.textContent ).toBe( 'Add link' );
	} );
} );
