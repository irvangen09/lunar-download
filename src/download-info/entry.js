import { linkProblem, trimAscii } from './list-utils';

export const PRESETS = [ 'table', 'card', 'compact', 'detail' ];

const NAME_MAX = 200;
const LABEL_MAX = 60;
const VALUE_MAX = 200;
const SLUG_MAX = 40;

// The compact style shows the first rows and one button.
const COMPACT_ROWS = 2;
const COMPACT_LINKS = 1;

/**
 * Single-line text cut to a number of characters. It follows the PHP class
 * that cleans the same attributes on the server.
 *
 * @param {unknown} value Raw value.
 * @param {number}  max   Maximum number of characters.
 * @return {string} The clean text, or '' when the value is not a string.
 */
export function singleLine( value, max ) {
	if ( 'string' !== typeof value ) {
		return '';
	}

	const text = trimAscii( value.replace( /[\x00-\x1f\x7f]+/g, ' ' ) );

	return trimAscii( Array.from( text ).slice( 0, max ).join( '' ) );
}

function toId( value ) {
	if ( Number.isInteger( value ) ) {
		return Math.max( 0, value );
	}

	return 'string' === typeof value && /^\d+$/.test( value )
		? parseInt( value, 10 )
		: 0;
}

const isObject = ( value ) =>
	null !== value && 'object' === typeof value && ! Array.isArray( value );

function cleanRows( rows, fields ) {
	const clean = [];

	( Array.isArray( rows ) ? rows : [] ).forEach( ( row ) => {
		if ( ! isObject( row ) ) {
			return;
		}

		const value = singleLine( row.value, VALUE_MAX );

		if ( '' === value ) {
			return;
		}

		const slug = singleLine( row.field, SLUG_MAX );

		if ( '' !== slug ) {
			// A field that is no longer in the registry still shows, under its slug.
			const field = fields.find( ( item ) => item.slug === slug );
			const label = field ? singleLine( field.label, LABEL_MAX ) : slug;

			clean.push( {
				slug,
				label: '' === label ? slug : label,
				value,
				type: field ? field.type : 'text',
			} );

			return;
		}

		const label = singleLine( row.label, LABEL_MAX );

		if ( '' !== label ) {
			clean.push( { slug: null, label, value, type: 'custom' } );
		}
	} );

	return clean;
}

function cleanLinks( links, defaultLabel ) {
	const clean = [];

	( Array.isArray( links ) ? links : [] ).forEach( ( link ) => {
		if ( ! isObject( link ) || 'string' !== typeof link.url ) {
			return;
		}

		if ( '' !== linkProblem( link.url ) ) {
			return;
		}

		const label = singleLine( link.label, LABEL_MAX );

		clean.push( {
			label: '' === label ? defaultLabel : label,
			url: trimAscii( link.url ),
			newTab: true === link.newTab,
		} );
	} );

	return clean;
}

/**
 * The clean form of one entry's attributes: what the page shows. It mirrors
 * the PHP class that renders the block, and a shared set of test cases keeps
 * the two in step.
 *
 * @param {Object}   attributes   Block attributes.
 * @param {Object[]} fields       Field definitions.
 * @param {string}   defaultLabel Button text for a link without a label.
 * @return {Object} The entry: name, imageId, imageAlt, preset, rows and links.
 */
export function buildEntry( attributes, fields, defaultLabel ) {
	const preset = attributes.preset;
	const entry = {
		name: singleLine( attributes.name, NAME_MAX ),
		imageId: toId( attributes.imageId ),
		imageAlt: singleLine( attributes.imageAlt, NAME_MAX ),
		preset: PRESETS.includes( preset ) ? preset : PRESETS[ 0 ],
		rows: cleanRows( attributes.rows, fields ),
		links: cleanLinks( attributes.links, defaultLabel ),
	};

	// What the style does not show is dropped, as on the server.
	if ( 'compact' === entry.preset ) {
		entry.rows = entry.rows.slice( 0, COMPACT_ROWS );
		entry.links = entry.links.slice( 0, COMPACT_LINKS );
	}

	return entry;
}

/**
 * Whether a stored date is a real calendar date written as YYYY-MM-DD.
 *
 * @param {string} value Stored value.
 * @return {?{year: number, month: number, day: number}} The parts, or null.
 */
export function parseDate( value ) {
	const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec( value );

	if ( ! match ) {
		return null;
	}

	const [ year, month, day ] = match.slice( 1 ).map( Number );
	const date = new Date( Date.UTC( year, month - 1, day ) );

	return date.getUTCFullYear() === year &&
		date.getUTCMonth() === month - 1 &&
		date.getUTCDate() === day
		? { year, month, day }
		: null;
}
