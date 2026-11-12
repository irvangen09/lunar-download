/* global describe, it, expect */
import {
	buildEntry,
	parseDate,
	singleLine,
} from '../../src/download-info/entry';
import cases from '../fixtures/entry-cases.json';

describe( 'buildEntry matches the server', () => {
	// The same file is checked by the PHP tests against the PHP class.
	it.each( cases.cases.map( ( item ) => [ item.name, item ] ) )(
		'%s',
		( name, item ) => {
			expect(
				buildEntry(
					item.attributes,
					cases.fields,
					cases.defaultLinkLabel
				)
			).toEqual( item.expected );
		}
	);
} );

describe( 'singleLine', () => {
	it( 'cleans, trims and cuts by characters', () => {
		expect( singleLine( '  Line\none  ', 60 ) ).toBe( 'Line one' );
		expect( singleLine( 'ab cd', 3 ) ).toBe( 'ab' );
		expect( singleLine( '😀😀😀', 2 ) ).toBe( '😀😀' );
	} );

	it( 'gives an empty string for anything but a string', () => {
		expect( singleLine( null, 5 ) ).toBe( '' );
		expect( singleLine( 12, 5 ) ).toBe( '' );
		expect( singleLine( [ 'a' ], 5 ) ).toBe( '' );
	} );
} );

describe( 'parseDate', () => {
	it( 'accepts a real date written as YYYY-MM-DD', () => {
		expect( parseDate( '2026-10-30' ) ).toEqual( {
			year: 2026,
			month: 10,
			day: 30,
		} );
		expect( parseDate( '2024-02-29' ) ).toEqual( {
			year: 2024,
			month: 2,
			day: 29,
		} );
	} );

	it.each( [
		'soon',
		'2026-02-30',
		'2025-02-29',
		'2026-13-01',
		'30/10/2026',
		'2026-1-5',
		'',
	] )( 'rejects %p', ( value ) => {
		expect( parseDate( value ) ).toBeNull();
	} );
} );
