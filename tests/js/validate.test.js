/* global describe, it, expect */
import {
	draftFromField,
	emptyDraft,
	fieldFromDraft,
	findRoleUser,
	moveField,
	parseOptions,
	rolesForType,
	setArchived,
	validateField,
} from '../../src/admin/fields/validate';

const field = ( slug, extra = {} ) => ( {
	slug,
	label: slug,
	type: 'text',
	schema_role: null,
	archived: false,
	...extra,
} );

describe( 'parseOptions', () => {
	it( 'trims lines and drops empty and repeated ones', () => {
		expect( parseOptions( ' One \r\n\r\nTwo\nOne\n   \nThree' ) ).toEqual( [
			'One',
			'Two',
			'Three',
		] );
	} );
} );

describe( 'rolesForType', () => {
	it( 'lists the roles that fit a type', () => {
		expect( rolesForType( 'text' ) ).toEqual( [
			'creator',
			'file_size',
			'version',
		] );
		expect( rolesForType( 'select' ) ).toEqual( [ 'creator', 'version' ] );
		expect( rolesForType( 'date' ) ).toEqual( [
			'date_modified',
			'date_published',
		] );
	} );
} );

describe( 'validateField', () => {
	const valid = { ...emptyDraft(), label: 'Credit' };

	it( 'accepts a plain text field', () => {
		expect( validateField( valid, [] ) ).toEqual( {} );
	} );

	it( 'requires a label within 60 characters', () => {
		expect( validateField( { ...valid, label: '  ' }, [] ) ).toHaveProperty(
			'label'
		);
		expect(
			validateField( { ...valid, label: 'é'.repeat( 61 ) }, [] )
		).toHaveProperty( 'label' );
		expect(
			validateField( { ...valid, label: 'é'.repeat( 60 ) }, [] )
		).toEqual( {} );
	} );

	it( 'rejects an unknown type on a new field', () => {
		expect(
			validateField( { ...valid, type: 'number' }, [] )
		).toHaveProperty( 'type' );
	} );

	it( 'limits the number and length of choices', () => {
		const select = { ...valid, type: 'select' };
		const many = Array.from( { length: 101 }, ( _, i ) => `Choice ${ i }` );

		expect(
			validateField( { ...select, optionsText: many.join( '\n' ) }, [] )
		).toHaveProperty( 'options' );
		expect(
			validateField( { ...select, optionsText: 'x'.repeat( 201 ) }, [] )
		).toHaveProperty( 'options' );
		expect(
			validateField(
				{ ...select, optionsText: many.slice( 0, 100 ).join( '\n' ) },
				[]
			)
		).toEqual( {} );
	} );

	it( 'rejects a role that does not fit the type', () => {
		expect(
			validateField( { ...valid, role: 'date_modified' }, [] )
		).toHaveProperty( 'role' );
	} );

	it( 'rejects a role that another active field uses', () => {
		const fields = [ field( 'kredit', { schema_role: 'creator' } ) ];

		expect(
			validateField( { ...valid, role: 'creator' }, fields )
		).toHaveProperty( 'role' );
	} );

	it( 'lets a field keep its own role and ignores archived users', () => {
		const own = field( 'kredit', { schema_role: 'creator' } );
		const archived = field( 'old', {
			schema_role: 'creator',
			archived: true,
		} );

		expect(
			validateField( { ...valid, role: 'creator' }, [ own ], own )
		).toEqual( {} );
		expect(
			validateField( { ...valid, role: 'creator' }, [ archived ] )
		).toEqual( {} );
	} );

	it( 'does not check roles for an archived field being edited', () => {
		const active = field( 'a', { schema_role: 'creator' } );
		const archived = field( 'b', {
			schema_role: 'creator',
			archived: true,
		} );

		expect(
			validateField(
				{ ...draftFromField( archived ) },
				[ active, archived ],
				archived
			)
		).toEqual( {} );
	} );

	it( 'uses the stored type of an existing field', () => {
		const existing = field( 'tanggal', { type: 'date' } );

		expect(
			validateField(
				{ ...draftFromField( existing ), role: 'date_modified' },
				[ existing ],
				existing
			)
		).toEqual( {} );
	} );
} );

describe( 'fieldFromDraft', () => {
	it( 'builds a new field without a slug', () => {
		expect(
			fieldFromDraft( {
				...emptyDraft(),
				label: ' Credit ',
				role: 'creator',
			} )
		).toEqual( {
			label: 'Credit',
			type: 'text',
			schema_role: 'creator',
			archived: false,
		} );
	} );

	it( 'adds options only for fixed choices', () => {
		expect(
			fieldFromDraft( {
				label: 'Bus',
				type: 'select',
				optionsText: 'A\nB',
				role: '',
			} )
		).toEqual( {
			label: 'Bus',
			type: 'select',
			options: [ 'A', 'B' ],
			schema_role: null,
			archived: false,
		} );
	} );

	it( 'keeps slug, type and archived state of an existing field', () => {
		const existing = field( 'kredit', { archived: true } );

		expect(
			fieldFromDraft(
				{ label: 'Pembuat', type: 'date', optionsText: '', role: '' },
				existing
			)
		).toEqual( {
			slug: 'kredit',
			label: 'Pembuat',
			type: 'text',
			schema_role: null,
			archived: true,
		} );
	} );
} );

describe( 'moveField', () => {
	const list = [ field( 'a' ), field( 'b' ), field( 'c' ) ];

	it( 'swaps a field with its neighbour', () => {
		expect( moveField( list, 1, -1 ).map( ( f ) => f.slug ) ).toEqual( [
			'b',
			'a',
			'c',
		] );
		expect( moveField( list, 1, 1 ).map( ( f ) => f.slug ) ).toEqual( [
			'a',
			'c',
			'b',
		] );
	} );

	it( 'returns the same list at the edges', () => {
		expect( moveField( list, 0, -1 ) ).toBe( list );
		expect( moveField( list, 2, 1 ) ).toBe( list );
	} );

	it( 'does not change the original list', () => {
		moveField( list, 1, -1 );

		expect( list.map( ( f ) => f.slug ) ).toEqual( [ 'a', 'b', 'c' ] );
	} );
} );

describe( 'setArchived', () => {
	it( 'archives a field and keeps its role', () => {
		const list = [ field( 'a', { schema_role: 'creator' } ) ];
		const result = setArchived( list, 'a', true );

		expect( result.fields[ 0 ] ).toMatchObject( {
			archived: true,
			schema_role: 'creator',
		} );
		expect( result.roleCleared ).toBe( false );
	} );

	it( 'restores a field without a role conflict', () => {
		const list = [
			field( 'a', { archived: true, schema_role: 'creator' } ),
		];
		const result = setArchived( list, 'a', false );

		expect( result.fields[ 0 ].schema_role ).toBe( 'creator' );
		expect( result.roleCleared ).toBe( false );
	} );

	it( 'removes the role of a restored field when an active field has it', () => {
		const list = [
			field( 'a', { archived: true, schema_role: 'creator' } ),
			field( 'b', { schema_role: 'creator' } ),
		];
		const result = setArchived( list, 'a', false );

		expect( result.fields[ 0 ] ).toMatchObject( {
			archived: false,
			schema_role: null,
		} );
		expect( result.fields[ 1 ].schema_role ).toBe( 'creator' );
		expect( result.roleCleared ).toBe( true );
	} );
} );

describe( 'findRoleUser', () => {
	it( 'ignores archived fields and the given slug', () => {
		const list = [
			field( 'a', { schema_role: 'version', archived: true } ),
			field( 'b', { schema_role: 'version' } ),
		];

		expect( findRoleUser( list, 'version' ).slug ).toBe( 'b' );
		expect( findRoleUser( list, 'version', 'b' ) ).toBeUndefined();
	} );
} );
