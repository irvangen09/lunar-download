import { __, sprintf } from '@wordpress/i18n';

export const FIELD_TYPES = [ 'text', 'select', 'date' ];
export const LABEL_MAX = 60;
export const OPTION_MAX_LENGTH = 200;
export const OPTIONS_MAX = 100;

// Schema roles and the field types each one accepts.
export const ROLE_TYPES = {
	creator: [ 'text', 'select' ],
	file_size: [ 'text' ],
	date_modified: [ 'date' ],
	date_published: [ 'date' ],
	version: [ 'text', 'select' ],
};

export function getTypeLabels() {
	return {
		text: __( 'Text', 'lunar-download' ),
		select: __( 'Fixed choices', 'lunar-download' ),
		date: __( 'Date', 'lunar-download' ),
	};
}

export function getRoleLabels() {
	return {
		creator: __( 'Creator', 'lunar-download' ),
		file_size: __( 'File size', 'lunar-download' ),
		date_modified: __( 'Date modified', 'lunar-download' ),
		date_published: __( 'Date published', 'lunar-download' ),
		version: __( 'Version', 'lunar-download' ),
	};
}

export function rolesForType( type ) {
	return Object.keys( ROLE_TYPES ).filter( ( role ) =>
		ROLE_TYPES[ role ].includes( type )
	);
}

/**
 * Splits a textarea value into trimmed, non-empty, unique options.
 *
 * @param {string} text One option per line.
 * @return {string[]} Options in the order written.
 */
export function parseOptions( text ) {
	const options = [];

	String( text )
		.split( /\r?\n/ )
		.forEach( ( line ) => {
			const option = line.trim();

			if ( option && ! options.includes( option ) ) {
				options.push( option );
			}
		} );

	return options;
}

/**
 * Finds the active field that uses a schema role.
 *
 * @param {Object[]} fields     Field definitions.
 * @param {string}   role       Schema role.
 * @param {?string}  ignoreSlug Slug of a field that does not count.
 * @return {?Object} The field, or undefined.
 */
export function findRoleUser( fields, role, ignoreSlug = null ) {
	return fields.find(
		( field ) =>
			! field.archived &&
			field.slug !== ignoreSlug &&
			field.schema_role === role
	);
}

const countCharacters = ( text ) => Array.from( text ).length;

export function emptyDraft() {
	return { label: '', type: 'text', optionsText: '', role: '' };
}

export function draftFromField( field ) {
	return {
		label: field.label,
		type: field.type,
		optionsText: ( field.options || [] ).join( '\n' ),
		role: field.schema_role || '',
	};
}

/**
 * Builds the definition that is sent to the server. A new field has no slug:
 * the server creates it from the label.
 *
 * @param {Object}  draft    Form values.
 * @param {?Object} existing The field being edited, or null for a new one.
 * @return {Object} Field definition.
 */
export function fieldFromDraft( draft, existing = null ) {
	const type = existing ? existing.type : draft.type;
	const field = {
		label: draft.label.trim(),
		type,
		schema_role: draft.role || null,
		archived: existing ? existing.archived : false,
	};

	if ( existing ) {
		field.slug = existing.slug;
	}

	if ( 'select' === type ) {
		field.options = parseOptions( draft.optionsText );
	}

	return field;
}

/**
 * Checks the form values before they are sent. The server repairs bad data
 * silently, so this is where the author gets a clear message.
 *
 * @param {Object}   draft    Form values.
 * @param {Object[]} fields   Current field definitions.
 * @param {?Object}  existing The field being edited, or null for a new one.
 * @return {Object} Messages keyed by form value (label, type, options, role).
 */
export function validateField( draft, fields, existing = null ) {
	const errors = {};
	const label = draft.label.trim();
	const type = existing ? existing.type : draft.type;

	if ( ! label ) {
		errors.label = __( 'Enter a label.', 'lunar-download' );
	} else if ( countCharacters( label ) > LABEL_MAX ) {
		errors.label = sprintf(
			/* translators: %d: maximum number of characters. */
			__( 'Use %d characters or fewer.', 'lunar-download' ),
			LABEL_MAX
		);
	}

	if ( ! FIELD_TYPES.includes( type ) ) {
		errors.type = __( 'Choose a type.', 'lunar-download' );
	}

	if ( 'select' === type ) {
		const options = parseOptions( draft.optionsText );

		if ( options.length > OPTIONS_MAX ) {
			errors.options = sprintf(
				/* translators: %d: maximum number of options. */
				__( 'Use %d options or fewer.', 'lunar-download' ),
				OPTIONS_MAX
			);
		} else if (
			options.some(
				( option ) => countCharacters( option ) > OPTION_MAX_LENGTH
			)
		) {
			errors.options = sprintf(
				/* translators: %d: maximum number of characters per option. */
				__(
					'Keep each option within %d characters.',
					'lunar-download'
				),
				OPTION_MAX_LENGTH
			);
		}
	}

	if ( draft.role ) {
		if ( ! ( ROLE_TYPES[ draft.role ] || [] ).includes( type ) ) {
			errors.role = __(
				'This role does not fit the field type.',
				'lunar-download'
			);
		} else if (
			! ( existing && existing.archived ) &&
			findRoleUser( fields, draft.role, existing ? existing.slug : null )
		) {
			errors.role = __(
				'Another active field already uses this role.',
				'lunar-download'
			);
		}
	}

	return errors;
}

/**
 * Returns the list with one field moved, or the same list when it cannot move.
 *
 * @param {Object[]} fields Field definitions.
 * @param {number}   index  Current position.
 * @param {number}   offset -1 to move up, 1 to move down.
 * @return {Object[]} Reordered list.
 */
export function moveField( fields, index, offset ) {
	const target = index + offset;

	if ( target < 0 || target >= fields.length || ! fields[ index ] ) {
		return fields;
	}

	const next = [ ...fields ];
	next[ index ] = fields[ target ];
	next[ target ] = fields[ index ];

	return next;
}

/**
 * Archives or restores a field. A restored field loses its schema role when
 * an active field already uses it.
 *
 * @param {Object[]} fields   Field definitions.
 * @param {string}   slug     Field to change.
 * @param {boolean}  archived New archived state.
 * @return {{fields: Object[], roleCleared: boolean}} New list and whether a role was removed.
 */
export function setArchived( fields, slug, archived ) {
	let roleCleared = false;

	const next = fields.map( ( field ) => {
		if ( field.slug !== slug ) {
			return field;
		}

		const updated = { ...field, archived };

		if (
			! archived &&
			field.schema_role &&
			findRoleUser( fields, field.schema_role, slug )
		) {
			updated.schema_role = null;
			roleCleared = true;
		}

		return updated;
	} );

	return { fields: next, roleCleared };
}
