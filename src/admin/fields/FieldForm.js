import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';

import {
	draftFromField,
	emptyDraft,
	fieldFromDraft,
	findRoleUser,
	getRoleLabels,
	getTypeLabels,
	rolesForType,
	validateField,
} from './validate';

/**
 * Form for a new field, or for changing the label, options and role of an
 * existing one.
 *
 * @param {Object}                  props
 * @param {?Object}                 props.field    Field being edited, or null for a new one.
 * @param {Object[]}                props.fields   All fields.
 * @param {boolean}                 props.isSaving Whether a save is running.
 * @param {(field: Object) => void} props.onSubmit Called with the field definition.
 * @param {() => void}              props.onCancel Called when the form is dismissed.
 */
export default function FieldForm( {
	field,
	fields,
	isSaving,
	onSubmit,
	onCancel,
} ) {
	const [ draft, setDraft ] = useState( () =>
		field ? draftFromField( field ) : emptyDraft()
	);
	const [ errors, setErrors ] = useState( {} );
	const labelInput = useRef( null );

	useEffect( () => {
		labelInput.current?.focus();
	}, [] );

	const type = field ? field.type : draft.type;
	const typeLabels = getTypeLabels();
	const roleLabels = getRoleLabels();
	const hasErrors = Object.keys( errors ).length > 0;

	const roleOptions = [
		{ label: __( 'None', 'lunar-download' ), value: '' },
		...rolesForType( type ).map( ( role ) => {
			const user =
				field && field.archived
					? undefined
					: findRoleUser( fields, role, field ? field.slug : null );

			return {
				value: role,
				disabled: !! user,
				label: user
					? sprintf(
							/* translators: 1: schema role, 2: label of the field that uses it. */
							__( '%1$s (used by %2$s)', 'lunar-download' ),
							roleLabels[ role ],
							user.label
						)
					: roleLabels[ role ],
			};
		} ),
	];

	const update = ( key ) => ( value ) =>
		setDraft( ( current ) => ( { ...current, [ key ]: value } ) );

	const changeType = ( value ) =>
		setDraft( ( current ) => ( {
			...current,
			type: value,
			role: rolesForType( value ).includes( current.role )
				? current.role
				: '',
		} ) );

	function handleSubmit( event ) {
		event.preventDefault();

		const found = validateField( draft, fields, field );

		setErrors( found );

		if ( Object.keys( found ).length === 0 ) {
			onSubmit( fieldFromDraft( draft, field ) );
		}
	}

	return (
		<form className="lunar-dl-fields__form" onSubmit={ handleSubmit }>
			<h2>
				{ field
					? __( 'Edit field', 'lunar-download' )
					: __( 'Add field', 'lunar-download' ) }
			</h2>

			{ hasErrors && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Fix the highlighted fields and try again.',
						'lunar-download'
					) }
				</Notice>
			) }

			<TextControl
				ref={ labelInput }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Label', 'lunar-download' ) }
				help={
					errors.label ||
					__(
						'Shown next to the value in every download entry.',
						'lunar-download'
					)
				}
				value={ draft.label }
				onChange={ update( 'label' ) }
				aria-invalid={ !! errors.label }
			/>

			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Type', 'lunar-download' ) }
				help={
					errors.type ||
					( field
						? __(
								'The type cannot be changed after the field is created.',
								'lunar-download'
							)
						: __(
								'Text is typed in each entry. Fixed choices are picked from a list. Date uses the site date format.',
								'lunar-download'
							) )
				}
				value={ type }
				options={ Object.keys( typeLabels ).map( ( value ) => ( {
					value,
					label: typeLabels[ value ],
				} ) ) }
				onChange={ changeType }
				disabled={ !! field }
			/>

			{ 'select' === type && (
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Choices', 'lunar-download' ) }
					help={
						errors.options ||
						__(
							'One choice per line. Entries that already use a removed choice keep showing it.',
							'lunar-download'
						)
					}
					value={ draft.optionsText }
					onChange={ update( 'optionsText' ) }
					rows={ 6 }
					aria-invalid={ !! errors.options }
				/>
			) }

			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Structured data role', 'lunar-download' ) }
				help={
					errors.role ||
					__(
						'Optional. Adds this value to the structured data of the entry. Each role can be used by one active field.',
						'lunar-download'
					)
				}
				value={ draft.role }
				options={ roleOptions }
				onChange={ update( 'role' ) }
			/>

			{ field && (
				<p className="lunar-dl-fields__slug">
					{ __( 'Slug:', 'lunar-download' ) }{ ' ' }
					<code>{ field.slug }</code>
					<br />
					<span className="description">
						{ __(
							'The slug identifies the field in saved entries and cannot be changed.',
							'lunar-download'
						) }
					</span>
				</p>
			) }

			<div className="lunar-dl-fields__form-actions">
				<Button
					variant="primary"
					type="submit"
					isBusy={ isSaving }
					disabled={ isSaving }
				>
					{ field
						? __( 'Save changes', 'lunar-download' )
						: __( 'Add field', 'lunar-download' ) }
				</Button>
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'lunar-download' ) }
				</Button>
			</div>
		</form>
	);
}
