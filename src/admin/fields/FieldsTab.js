import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';

import FieldForm from './FieldForm';
import {
	getRoleLabels,
	getTypeLabels,
	moveField,
	setArchived,
} from './validate';

/**
 * Field Registry tab: lists the fields and changes them. Every change is
 * saved at once, and the list always shows what the server stored.
 *
 * @param {Object}                                props
 * @param {Object[]}                              props.fields   Field definitions.
 * @param {boolean}                               props.isSaving Whether a save is running.
 * @param {(fields: Object[]) => Promise<Object>} props.onSave   Saves a new list of fields; rejects on failure.
 */
export default function FieldsTab( { fields, isSaving, onSave } ) {
	// null shows the list, 'new' the empty form, a slug the form for that field.
	const [ editing, setEditing ] = useState( null );
	const [ notice, setNotice ] = useState( null );
	const [ focusKey, setFocusKey ] = useState( null );
	const root = useRef( null );

	// Returns focus to a control after the view or the order changed.
	useEffect( () => {
		if ( null === editing && focusKey ) {
			root.current
				?.querySelector( `[data-focus-key="${ focusKey }"]` )
				?.focus();
			setFocusKey( null );
		}
	}, [ editing, focusKey ] );

	const typeLabels = getTypeLabels();
	const roleLabels = getRoleLabels();

	async function persist( nextFields, message ) {
		setNotice( null );

		try {
			await onSave( nextFields );
			setNotice( { status: 'success', message } );

			return true;
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error.message ||
					__( 'The fields could not be saved.', 'lunar-download' ),
			} );

			return false;
		}
	}

	async function handleSubmit( field ) {
		const isNew = 'new' === editing;
		const next = isNew
			? [ ...fields, field ]
			: fields.map( ( item ) =>
					item.slug === field.slug ? field : item
				);

		const saved = await persist(
			next,
			isNew
				? __( 'Field added.', 'lunar-download' )
				: __( 'Field updated.', 'lunar-download' )
		);

		if ( saved ) {
			setEditing( null );
			setFocusKey( isNew ? 'add' : `edit-${ field.slug }` );
		}
	}

	async function handleArchive( field, archived ) {
		const result = setArchived( fields, field.slug, archived );
		let message = __( 'Field archived.', 'lunar-download' );

		if ( ! archived ) {
			message = result.roleCleared
				? __(
						'Field restored. Its structured data role was removed because another active field uses it.',
						'lunar-download'
					)
				: __( 'Field restored.', 'lunar-download' );
		}

		await persist( result.fields, message );
	}

	async function handleMove( field, index, offset ) {
		const saved = await persist(
			moveField( fields, index, offset ),
			__( 'Order updated.', 'lunar-download' )
		);

		if ( saved ) {
			const target = index + offset;
			const atEdge = target === 0 || target === fields.length - 1;

			// A disabled button cannot take focus, so use the other direction.
			const direction = offset < 0 ? 'up' : 'down';
			const opposite = offset < 0 ? 'down' : 'up';

			setFocusKey( `${ atEdge ? opposite : direction }-${ field.slug }` );
		}
	}

	function closeForm() {
		setNotice( null );
		setFocusKey( 'new' === editing ? 'add' : `edit-${ editing }` );
		setEditing( null );
	}

	function archiveLabel( field ) {
		return field.archived
			? sprintf(
					/* translators: %s: field label. */
					__( 'Restore: %s', 'lunar-download' ),
					field.label
				)
			: sprintf(
					/* translators: %s: field label. */
					__( 'Archive: %s', 'lunar-download' ),
					field.label
				);
	}

	const statusNotice = notice && (
		<Notice
			status={ notice.status }
			onRemove={ () => setNotice( null ) }
			className="lunar-dl-fields__notice"
		>
			{ notice.message }
		</Notice>
	);

	if ( null !== editing ) {
		return (
			<div ref={ root }>
				{ statusNotice }
				<FieldForm
					field={
						'new' === editing
							? null
							: fields.find( ( item ) => item.slug === editing )
					}
					fields={ fields }
					isSaving={ isSaving }
					onSubmit={ handleSubmit }
					onCancel={ closeForm }
				/>
			</div>
		);
	}

	return (
		<div ref={ root }>
			{ statusNotice }

			<p className="description">
				{ __(
					'Create a field once, then choose it in each Download Info block. Archive a field to remove it from the picker; entries that already use it keep their values.',
					'lunar-download'
				) }
			</p>

			<Button
				variant="primary"
				data-focus-key="add"
				onClick={ () => {
					setNotice( null );
					setEditing( 'new' );
				} }
				disabled={ isSaving }
			>
				{ __( 'Add field', 'lunar-download' ) }
			</Button>

			{ 0 === fields.length ? (
				<p className="lunar-dl-fields__empty">
					{ __(
						'No fields yet. Add your first field, for example Credit or File size.',
						'lunar-download'
					) }
				</p>
			) : (
				<ul className="lunar-dl-fields__list">
					{ fields.map( ( field, index ) => (
						<li
							key={ field.slug }
							className={
								field.archived
									? 'lunar-dl-fields__item is-archived'
									: 'lunar-dl-fields__item'
							}
						>
							<div className="lunar-dl-fields__summary">
								<strong>{ field.label }</strong>
								{ field.archived && (
									<span className="lunar-dl-fields__badge">
										{ __( 'Archived', 'lunar-download' ) }
									</span>
								) }
								<span>
									{ typeLabels[ field.type ] || field.type }
								</span>
								{ field.schema_role && (
									<span>
										{ sprintf(
											/* translators: %s: structured data role, such as Creator. */
											__(
												'Structured data: %s',
												'lunar-download'
											),
											roleLabels[ field.schema_role ] ||
												field.schema_role
										) }
									</span>
								) }
								<code>{ field.slug }</code>
							</div>

							<div className="lunar-dl-fields__actions">
								<Button
									variant="tertiary"
									size="small"
									data-focus-key={ `up-${ field.slug }` }
									disabled={ isSaving || 0 === index }
									aria-label={ sprintf(
										/* translators: %s: field label. */
										__( 'Move up: %s', 'lunar-download' ),
										field.label
									) }
									onClick={ () =>
										handleMove( field, index, -1 )
									}
								>
									{ __( 'Move up', 'lunar-download' ) }
								</Button>
								<Button
									variant="tertiary"
									size="small"
									data-focus-key={ `down-${ field.slug }` }
									disabled={
										isSaving || index === fields.length - 1
									}
									aria-label={ sprintf(
										/* translators: %s: field label. */
										__( 'Move down: %s', 'lunar-download' ),
										field.label
									) }
									onClick={ () =>
										handleMove( field, index, 1 )
									}
								>
									{ __( 'Move down', 'lunar-download' ) }
								</Button>
								<Button
									variant="secondary"
									size="small"
									data-focus-key={ `edit-${ field.slug }` }
									disabled={ isSaving }
									aria-label={ sprintf(
										/* translators: %s: field label. */
										__( 'Edit: %s', 'lunar-download' ),
										field.label
									) }
									onClick={ () => {
										setNotice( null );
										setEditing( field.slug );
									} }
								>
									{ __( 'Edit', 'lunar-download' ) }
								</Button>
								<Button
									variant="secondary"
									size="small"
									disabled={ isSaving }
									aria-label={ archiveLabel( field ) }
									onClick={ () =>
										handleArchive( field, ! field.archived )
									}
								>
									{ field.archived
										? __( 'Restore', 'lunar-download' )
										: __( 'Archive', 'lunar-download' ) }
								</Button>
							</div>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
