import { __, sprintf } from '@wordpress/i18n';

// Value of the field select for a row that uses a one-time label.
export const CUSTOM = '__custom__';

const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

/**
 * Returns the list with one item moved, or the same list when it cannot move.
 *
 * @param {Object[]} list   Items.
 * @param {number}   index  Current position.
 * @param {number}   offset -1 to move up, 1 to move down.
 * @return {Object[]} Reordered list.
 */
export function moveItem( list, index, offset ) {
	const target = index + offset;

	if ( target < 0 || target >= list.length || ! list[ index ] ) {
		return list;
	}

	const next = [ ...list ];
	next[ index ] = list[ target ];
	next[ target ] = list[ index ];

	return next;
}

export function removeItem( list, index ) {
	return list.filter( ( item, position ) => position !== index );
}

export function replaceItem( list, index, item ) {
	return list.map( ( current, position ) =>
		position === index ? item : current
	);
}

/**
 * The first active field that no row of the entry uses yet.
 *
 * @param {Object[]} fields Field definitions.
 * @param {Object[]} rows   Rows of the entry.
 * @return {?Object} The field, or null when every active field is in use.
 */
export function nextUnusedField( fields, rows ) {
	const used = rows.map( ( row ) => row.field );

	return (
		fields.find(
			( field ) => ! field.archived && ! used.includes( field.slug )
		) || null
	);
}

/**
 * What the field select shows for a row.
 *
 * @param {Object} row A row of the entry.
 * @return {string} A field slug, or CUSTOM.
 */
export function rowSelection( row ) {
	return 'string' === typeof row.field && '' !== row.field
		? row.field
		: CUSTOM;
}

/**
 * Options of the field select: the active fields, the field the row already
 * uses even when it is archived or unknown, and a custom label.
 *
 * @param {Object[]} fields Field definitions.
 * @param {Object}   row    A row of the entry.
 * @return {{value: string, label: string}[]} Options.
 */
export function fieldChoices( fields, row ) {
	const choices = fields
		.filter( ( field ) => ! field.archived )
		.map( ( field ) => ( { value: field.slug, label: field.label } ) );
	const slug = rowSelection( row );

	if ( CUSTOM !== slug ) {
		const current = fields.find( ( field ) => field.slug === slug );

		if ( ! current ) {
			choices.push( {
				value: slug,
				label: sprintf(
					/* translators: %s: slug of a field that no longer exists. */
					__( '%s (unknown field)', 'lunar-download' ),
					slug
				),
			} );
		} else if ( current.archived ) {
			choices.push( {
				value: slug,
				label: sprintf(
					/* translators: %s: label of an archived field. */
					__( '%s (archived)', 'lunar-download' ),
					current.label
				),
			} );
		}
	}

	choices.push( {
		value: CUSTOM,
		label: __( 'Custom label', 'lunar-download' ),
	} );

	return choices;
}

/**
 * How the value of a row is entered.
 *
 * @param {Object[]} fields Field definitions.
 * @param {Object}   row    A row of the entry.
 * @return {{type: string, options: string[], label: ?string}} Control type, choices, and the field label.
 */
export function valueKind( fields, row ) {
	const field =
		'string' === typeof row.field
			? fields.find( ( item ) => item.slug === row.field )
			: null;

	return field
		? {
				type: field.type,
				options: Array.isArray( field.options ) ? field.options : [],
				label: field.label,
			}
		: { type: 'text', options: [], label: null };
}

function keepValue( value, type, options ) {
	const text = 'string' === typeof value ? value : '';

	if ( 'date' === type ) {
		return DATE_PATTERN.test( text ) ? text : '';
	}

	if ( 'select' === type ) {
		return options.includes( text ) ? text : '';
	}

	return text;
}

/**
 * The row after another field (or a custom label) is chosen. The value is kept
 * when it still fits the new type.
 *
 * @param {Object}   row       A row of the entry.
 * @param {string}   selection A field slug, or CUSTOM.
 * @param {Object[]} fields    Field definitions.
 * @return {Object} The new row.
 */
export function rowForSelection( row, selection, fields ) {
	if ( CUSTOM === selection ) {
		return {
			label: 'string' === typeof row.label ? row.label : '',
			value: keepValue( row.value, 'text', [] ),
		};
	}

	const { type, options } = valueKind( fields, { field: selection } );

	return {
		field: selection,
		value: keepValue( row.value, type, options ),
	};
}

/**
 * Options of the select for a fixed-choice value. A stored value that is no
 * longer in the list stays selectable, so that it is not lost.
 *
 * @param {string[]} options Choices of the field.
 * @param {string}   value   Stored value.
 * @return {{value: string, label: string}[]} Options.
 */
export function selectChoices( options, value ) {
	const choices = [
		{ value: '', label: __( 'Choose…', 'lunar-download' ) },
		...options.map( ( option ) => ( { value: option, label: option } ) ),
	];

	if ( value && ! options.includes( value ) ) {
		choices.push( {
			value,
			label: sprintf(
				/* translators: %s: a value that was removed from the list of choices. */
				__( '%s (no longer in the list)', 'lunar-download' ),
				value
			),
		} );
	}

	return choices;
}

// Same characters as PHP trim(), so that the editor and the server agree on edge cases.
const TRIM_PATTERN = /^[ \t\n\r\0\x0B]+|[ \t\n\r\0\x0B]+$/g;

export function trimAscii( text ) {
	return String( text ).replace( TRIM_PATTERN, '' );
}

/**
 * Checks a link address the way the server does. The editor uses it to warn
 * the author early and to leave out links in the preview.
 * Allowed: http, https, and a path that starts with one slash.
 *
 * @param {string} url Address as typed.
 * @return {string} '' when it is fine, 'empty' or 'invalid'.
 */
export function linkProblem( url ) {
	const value = trimAscii( 'string' === typeof url ? url : '' );

	if ( '' === value ) {
		return 'empty';
	}

	// Control characters and backslashes can hide the real destination.
	if ( /[\x00-\x1f\x7f\\]/.test( value ) ) {
		return 'invalid';
	}

	if ( value.startsWith( '/' ) ) {
		return value.startsWith( '//' ) ? 'invalid' : '';
	}

	const match = value.match( /^https?:\/\/([^/?#]*)/i );

	// A user name or a space makes the address unsafe or broken.
	if ( ! match || '' === match[ 1 ] || /[@ \t\n\r\f\v]/.test( match[ 1 ] ) ) {
		return 'invalid';
	}

	const authority = match[ 1 ];
	const colon = authority.lastIndexOf( ':' );

	if ( colon > -1 && ! authority.endsWith( ']' ) ) {
		const host = authority.slice( 0, colon );
		const port = authority.slice( colon + 1 );

		if ( '' === host || ! /^\d*$/.test( port ) || Number( port ) > 65535 ) {
			return 'invalid';
		}
	}

	return '';
}

/**
 * Drops the values that are the default, so that saved content stays small.
 *
 * @param {Object} link A link of the entry.
 * @return {Object} The link without default values.
 */
export function cleanLink( link ) {
	const clean = { url: 'string' === typeof link.url ? link.url : '' };

	if ( 'string' === typeof link.label && '' !== link.label ) {
		clean.label = link.label;
	}

	if ( 'follow' === link.relMode || 'nofollow' === link.relMode ) {
		clean.relMode = link.relMode;
	}

	if ( true === link.newTab ) {
		clean.newTab = true;
	}

	return clean;
}
