/* global describe, it, expect, beforeEach, afterEach */
import EntryPreview from '../../src/download-info/EntryPreview';
import { buildEntry } from '../../src/download-info/entry';
import { createHarness } from './helpers';

const fields = [
	{ slug: 'kredit', label: 'Kredit', type: 'text', archived: false },
	{ slug: 'tanggal', label: 'Tanggal', type: 'date', archived: false },
];

let ui;

const entry = ( attributes ) => buildEntry( attributes, fields, 'Download' );
const formatDate = ( { year, month, day } ) => `${ day }/${ month }/${ year }`;
const media = {
	alt_text: 'Alt dari media',
	source_url: 'https://example.test/full.jpg',
	media_details: {
		sizes: {
			large: {
				source_url: 'https://example.test/large.jpg',
				width: 1024,
				height: 576,
			},
			medium: {
				source_url: 'https://example.test/medium.jpg',
				width: 300,
				height: 169,
			},
		},
	},
};

function render( attributes, props = {} ) {
	ui.render(
		<EntryPreview
			entry={ entry( attributes ) }
			formatDate={ formatDate }
			{ ...props }
		/>
	);
}

beforeEach( () => {
	ui = createHarness();
} );

afterEach( () => ui.unmount() );

describe( 'EntryPreview', () => {
	it( 'renders nothing for an entry with nothing to show', () => {
		render( { name: 'Only a name' } );

		expect( ui.container.innerHTML ).toBe( '' );
	} );

	it( 'uses the markup and classes of the page', () => {
		render( {
			preset: 'card',
			rows: [ { field: 'kredit', value: 'BlahBloh' } ],
			links: [ { url: '/a', label: 'Bersih' } ],
		} );

		const article = ui.container.querySelector( 'article' );

		expect( article.className ).toBe(
			'lunar-dl-entry lunar-dl-entry--card'
		);
		expect(
			article.querySelector(
				'dl.lunar-dl-entry__rows > div.lunar-dl-entry__row > dt'
			).textContent
		).toBe( 'Kredit' );
		expect( article.querySelector( 'dd' ).textContent ).toBe( 'BlahBloh' );
		expect(
			article.querySelector(
				'ul.lunar-dl-entry__links a.lunar-dl-button'
			).textContent
		).toBe( 'Bersih' );
		expect( article.querySelector( 'dl a' ) ).toBeNull();
	} );

	it( 'is not interactive and is hidden from screen readers', () => {
		render( { links: [ { url: '/a' } ] } );

		const article = ui.container.querySelector( 'article' );

		expect( article.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( article.hasAttribute( 'inert' ) ).toBe( true );
	} );

	it( 'shows a date row inside a time element with the site format', () => {
		render( { rows: [ { field: 'tanggal', value: '2026-10-30' } ] } );

		const time = ui.container.querySelector( 'time' );

		expect( time.getAttribute( 'datetime' ) ).toBe( '2026-10-30' );
		expect( time.textContent ).toBe( '30/10/2026' );
	} );

	it( 'shows a value that is not a real date as written', () => {
		render( { rows: [ { field: 'tanggal', value: '2026-02-30' } ] } );

		expect( ui.container.querySelector( 'time' ) ).toBeNull();
		expect( ui.container.querySelector( 'dd' ).textContent ).toBe(
			'2026-02-30'
		);
	} );

	it( 'adds a hidden notice to a link that opens in a new tab', () => {
		render( { links: [ { url: '/a', newTab: true } ] } );

		expect(
			ui.container.querySelector( '.lunar-dl-visually-hidden' )
				.textContent
		).toBe( ' (opens in a new tab)' );
	} );

	it( 'leaves out links that the page would not show', () => {
		render( {
			links: [ { url: 'javascript:alert(1)' }, { url: '/kept' } ],
		} );

		expect( ui.container.querySelectorAll( 'a' ) ).toHaveLength( 1 );
	} );

	it( 'shows the compact style with two rows and one button', () => {
		render( {
			preset: 'compact',
			rows: [
				{ field: 'kredit', value: '1' },
				{ field: 'kredit', value: '2' },
				{ field: 'kredit', value: '3' },
			],
			links: [ { url: '/a' }, { url: '/b' } ],
		} );

		expect( ui.container.querySelectorAll( 'dt' ) ).toHaveLength( 2 );
		expect( ui.container.querySelectorAll( 'a' ) ).toHaveLength( 1 );
	} );

	it( 'shows the image in the size of the style, with the entry alt first', () => {
		render( { imageId: 7, imageAlt: 'Alt entri' }, { media } );

		const image = ui.container.querySelector(
			'figure.lunar-dl-entry__media img.lunar-dl-entry__image'
		);

		expect( image.getAttribute( 'src' ) ).toBe(
			'https://example.test/large.jpg'
		);
		expect( image.getAttribute( 'alt' ) ).toBe( 'Alt entri' );
	} );

	it( 'uses the medium image and the media alt for the compact style', () => {
		render( { preset: 'compact', imageId: 7 }, { media } );

		const image = ui.container.querySelector( 'img' );

		expect( image.getAttribute( 'src' ) ).toBe(
			'https://example.test/medium.jpg'
		);
		expect( image.getAttribute( 'alt' ) ).toBe( 'Alt dari media' );
	} );

	it( 'falls back to the full image when the size is missing', () => {
		render(
			{ imageId: 7 },
			{ media: { source_url: 'https://example.test/full.jpg' } }
		);

		expect(
			ui.container.querySelector( 'img' ).getAttribute( 'src' )
		).toBe( 'https://example.test/full.jpg' );
	} );

	it( 'shows nothing for an image that has not loaded', () => {
		render( { imageId: 7 }, { media: null } );

		expect( ui.container.innerHTML ).toBe( '' );
	} );
} );
