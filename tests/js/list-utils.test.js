/* global describe, it, expect */
import {
	CUSTOM,
	cleanLink,
	fieldChoices,
	linkProblem,
	moveItem,
	nextUnusedField,
	removeItem,
	replaceItem,
	rowForSelection,
	rowSelection,
	selectChoices,
	valueKind,
} from '../../src/download-info/list-utils';

const fields = [
	{ slug: 'kredit', label: 'Kredit', type: 'text', archived: false },
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

describe( 'moveItem, removeItem, replaceItem', () => {
	const list = [ 'a', 'b', 'c' ];

	it( 'moves an item and leaves the original alone', () => {
		expect( moveItem( list, 1, -1 ) ).toEqual( [ 'b', 'a', 'c' ] );
		expect( moveItem( list, 1, 1 ) ).toEqual( [ 'a', 'c', 'b' ] );
		expect( list ).toEqual( [ 'a', 'b', 'c' ] );
	} );

	it( 'returns the same list at the edges', () => {
		expect( moveItem( list, 0, -1 ) ).toBe( list );
		expect( moveItem( list, 2, 1 ) ).toBe( list );
	} );

	it( 'removes and replaces by position', () => {
		expect( removeItem( list, 1 ) ).toEqual( [ 'a', 'c' ] );
		expect( replaceItem( list, 1, 'x' ) ).toEqual( [ 'a', 'x', 'c' ] );
	} );
} );

describe( 'nextUnusedField', () => {
	it( 'picks the first active field that no row uses', () => {
		expect( nextUnusedField( fields, [] ).slug ).toBe( 'kredit' );
		expect(
			nextUnusedField( fields, [ { field: 'kredit', value: 'x' } ] ).slug
		).toBe( 'tipe-bus' );
	} );

	it( 'skips archived fields and returns null when all are used', () => {
		const rows = [ 'kredit', 'tipe-bus', 'tanggal' ].map( ( field ) => ( {
			field,
			value: 'x',
		} ) );

		expect( nextUnusedField( fields, rows ) ).toBeNull();
		expect( nextUnusedField( [], [] ) ).toBeNull();
	} );
} );

describe( 'rowSelection and fieldChoices', () => {
	it( 'treats a row without a field as a custom label', () => {
		expect( rowSelection( { label: 'x', value: 'y' } ) ).toBe( CUSTOM );
		expect( rowSelection( { field: '', value: 'y' } ) ).toBe( CUSTOM );
		expect( rowSelection( { field: 'kredit' } ) ).toBe( 'kredit' );
	} );

	it( 'offers the active fields and a custom label', () => {
		expect( fieldChoices( fields, { field: 'kredit' } ) ).toEqual( [
			{ value: 'kredit', label: 'Kredit' },
			{ value: 'tipe-bus', label: 'Tipe Bus' },
			{ value: 'tanggal', label: 'Tanggal' },
			{ value: CUSTOM, label: 'Custom label' },
		] );
	} );

	it( 'keeps the archived or unknown field that a row already uses', () => {
		expect( fieldChoices( fields, { field: 'lama' } ) ).toContainEqual( {
			value: 'lama',
			label: 'Lama (archived)',
		} );
		expect( fieldChoices( fields, { field: 'hilang' } ) ).toContainEqual( {
			value: 'hilang',
			label: 'hilang (unknown field)',
		} );
	} );

	it( 'does not list an archived field for a row that does not use it', () => {
		const values = fieldChoices( fields, { label: 'x' } ).map(
			( c ) => c.value
		);

		expect( values ).not.toContain( 'lama' );
	} );
} );

describe( 'valueKind', () => {
	it( 'follows the field type', () => {
		expect( valueKind( fields, { field: 'tanggal' } ).type ).toBe( 'date' );
		expect( valueKind( fields, { field: 'tipe-bus' } ) ).toEqual( {
			type: 'select',
			options: [ 'Yudistira HD', 'Sadewa SHD' ],
			label: 'Tipe Bus',
		} );
	} );

	it( 'is plain text for custom and unknown rows', () => {
		expect( valueKind( fields, { label: 'x' } ).type ).toBe( 'text' );
		expect( valueKind( fields, { field: 'hilang' } ) ).toEqual( {
			type: 'text',
			options: [],
			label: null,
		} );
	} );
} );

describe( 'rowForSelection', () => {
	it( 'keeps the value when it still fits', () => {
		expect(
			rowForSelection(
				{ field: 'kredit', value: 'BlahBloh' },
				'ukuran',
				fields
			)
		).toEqual( { field: 'ukuran', value: 'BlahBloh' } );
		expect(
			rowForSelection(
				{ field: 'kredit', value: 'Yudistira HD' },
				'tipe-bus',
				fields
			)
		).toEqual( { field: 'tipe-bus', value: 'Yudistira HD' } );
		expect(
			rowForSelection(
				{ field: 'x', value: '2026-10-30' },
				'tanggal',
				fields
			)
		).toEqual( { field: 'tanggal', value: '2026-10-30' } );
	} );

	it( 'clears a value that does not fit the new type', () => {
		expect(
			rowForSelection(
				{ field: 'kredit', value: 'Lain' },
				'tipe-bus',
				fields
			).value
		).toBe( '' );
		expect(
			rowForSelection(
				{ field: 'kredit', value: 'bukan tanggal' },
				'tanggal',
				fields
			).value
		).toBe( '' );
	} );

	it( 'switches to a custom label without a field key', () => {
		expect(
			rowForSelection( { field: 'kredit', value: 'x' }, CUSTOM, fields )
		).toEqual( { label: '', value: 'x' } );
		expect(
			rowForSelection( { label: 'Catatan', value: 'x' }, CUSTOM, fields )
		).toEqual( { label: 'Catatan', value: 'x' } );
	} );
} );

describe( 'selectChoices', () => {
	it( 'starts with an empty choice and lists the options', () => {
		expect(
			selectChoices( [ 'A', 'B' ], 'A' ).map( ( c ) => c.value )
		).toEqual( [ '', 'A', 'B' ] );
	} );

	it( 'keeps a stored value that is no longer in the list', () => {
		expect( selectChoices( [ 'A' ], 'Z' ).pop() ).toEqual( {
			value: 'Z',
			label: 'Z (no longer in the list)',
		} );
	} );
} );

describe( 'linkProblem', () => {
	it.each( [
		[ 'https://drive.google.com/file/d/1', '' ],
		[ 'HTTP://Example.com:8080/a?x=1#y', '' ],
		[ '/download/file.zip', '' ],
		[ '/', '' ],
		[ '', 'empty' ],
		[ '   ', 'empty' ],
		[ 'javascript:alert(1)', 'invalid' ],
		[ 'ftp://example.com/a', 'invalid' ],
		[ '//evil.net/a', 'invalid' ],
		[ '/\\evil.net/a', 'invalid' ],
		[ 'example.com/file.zip', 'invalid' ],
		[ '#top', 'invalid' ],
		[ 'https://', 'invalid' ],
		[ 'https://user@example.com/a', 'invalid' ],
		[ 'https://exa mple.com/a', 'invalid' ],
		[ 'https://example.com/a\nb', 'invalid' ],
	] )( 'classifies %p as %p', ( url, expected ) => {
		expect( linkProblem( url ) ).toBe( expected );
	} );
} );

describe( 'cleanLink', () => {
	it( 'drops the default values', () => {
		expect(
			cleanLink( {
				url: '/a',
				label: '',
				relMode: 'auto',
				newTab: false,
			} )
		).toEqual( { url: '/a' } );
	} );

	it( 'keeps the values that differ from the default', () => {
		expect(
			cleanLink( {
				url: '/a',
				label: 'Bersih',
				relMode: 'follow',
				newTab: true,
			} )
		).toEqual( {
			url: '/a',
			label: 'Bersih',
			relMode: 'follow',
			newTab: true,
		} );
		expect( cleanLink( { url: '/a', relMode: 'nofollow' } ).relMode ).toBe(
			'nofollow'
		);
	} );

	it( 'ignores unknown keys and values of the wrong type', () => {
		expect(
			cleanLink( {
				url: 5,
				label: 7,
				relMode: 'bogus',
				newTab: 'true',
				extra: 1,
			} )
		).toEqual( { url: '' } );
	} );
} );
