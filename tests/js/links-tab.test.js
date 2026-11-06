/* global describe, it, expect, jest, beforeEach, afterEach */
import LinksTab from '../../src/admin/links/LinksTab';
import { createHarness } from './helpers';

let ui;

function render( props ) {
	ui.render( <LinksTab ownDomains={ [] } isSaving={ false } { ...props } /> );
}

const textarea = () => ui.container.querySelector( 'textarea' );

beforeEach( () => {
	ui = createHarness();
} );

afterEach( () => ui.unmount() );

describe( 'LinksTab', () => {
	it( 'shows the stored domains one per line', () => {
		render( {
			ownDomains: [ 'example.com', 'other.net' ],
			onSave: jest.fn(),
		} );

		expect( textarea().value ).toBe( 'example.com\nother.net' );
	} );

	it( 'explains how links are marked', () => {
		render( { onSave: jest.fn() } );

		expect( ui.container.textContent ).toContain( 'marked nofollow' );
		expect( ui.container.textContent ).toContain( 'noopener' );
	} );

	it( 'sends trimmed non-empty lines and confirms the save', async () => {
		const onSave = jest.fn().mockResolvedValue( {
			own_domains: [ 'example.com', 'other.net' ],
		} );
		render( { onSave } );

		ui.type( textarea(), '  example.com \r\n\n other.net\n   ' );
		await ui.submit();

		expect( onSave ).toHaveBeenCalledWith( [ 'example.com', 'other.net' ] );
		expect( ui.container.textContent ).toContain( 'Domains saved.' );
		expect( ui.container.textContent ).not.toContain( 'removed' );
	} );

	it( 'shows the stored form of what was saved', async () => {
		const onSave = jest
			.fn()
			.mockResolvedValue( { own_domains: [ 'example.com' ] } );
		render( { onSave } );

		ui.type( textarea(), 'https://www.Example.com/page' );
		await ui.submit();

		expect( textarea().value ).toBe( 'example.com' );
	} );

	it( 'says how many lines the server removed', async () => {
		const onSave = jest
			.fn()
			.mockResolvedValueOnce( { own_domains: [ 'example.com' ] } )
			.mockResolvedValueOnce( { own_domains: [ 'example.com' ] } );
		render( { onSave } );

		ui.type( textarea(), 'example.com\nnope' );
		await ui.submit();

		expect( ui.container.textContent ).toContain( '1 line was removed' );

		ui.type( textarea(), 'example.com\nnope\nalso nope\nexample.com' );
		await ui.submit();

		expect( ui.container.textContent ).toContain( '3 lines were removed' );
	} );

	it( 'shows the message when saving fails and keeps the text', async () => {
		const onSave = jest.fn().mockRejectedValue( new Error( 'Nope' ) );
		render( { onSave } );

		ui.type( textarea(), 'example.com' );
		await ui.submit();

		expect( ui.container.textContent ).toContain( 'Nope' );
		expect( textarea().value ).toBe( 'example.com' );
	} );

	it( 'does not send more than 100 domains', async () => {
		const onSave = jest.fn();
		render( { onSave } );

		ui.type(
			textarea(),
			Array.from(
				{ length: 101 },
				( _, i ) => `s${ i }.example.com`
			).join( '\n' )
		);
		await ui.submit();

		expect( ui.container.textContent ).toContain(
			'Use 100 domains or fewer.'
		);
		expect( onSave ).not.toHaveBeenCalled();
	} );
} );
