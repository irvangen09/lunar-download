/* global describe, it, expect */
import {
	activeFields,
	getEditorData,
} from '../../src/download-info/editor-data';

describe( 'getEditorData', () => {
	it( 'returns the fields and the settings address', () => {
		const fields = [ { slug: 'kredit', label: 'Kredit', type: 'text' } ];

		expect(
			getEditorData( { fields, settingsUrl: 'https://example.test/s' } )
		).toEqual( { fields, settingsUrl: 'https://example.test/s' } );
	} );

	it.each( [ undefined, null, 'text', 5, [] ] )(
		'gives empty defaults for %p',
		( value ) => {
			expect( getEditorData( value ) ).toEqual( {
				fields: [],
				settingsUrl: '',
			} );
		}
	);

	it( 'ignores values of the wrong type', () => {
		expect( getEditorData( { fields: 'nope', settingsUrl: 12 } ) ).toEqual(
			{ fields: [], settingsUrl: '' }
		);
	} );
} );

describe( 'activeFields', () => {
	it( 'leaves out archived fields and keeps the order', () => {
		const list = [
			{ slug: 'a', archived: false },
			{ slug: 'b', archived: true },
			{ slug: 'c', archived: false },
		];

		expect( activeFields( list ).map( ( f ) => f.slug ) ).toEqual( [
			'a',
			'c',
		] );
	} );

	it( 'returns an empty list when every field is archived', () => {
		expect( activeFields( [ { slug: 'a', archived: true } ] ) ).toEqual(
			[]
		);
	} );
} );
