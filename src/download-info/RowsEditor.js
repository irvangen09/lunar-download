import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, SelectControl, TextControl } from '@wordpress/components';

import ItemActions from './ItemActions';
import useKeyedList from './use-keyed-list';
import {
	CUSTOM,
	fieldChoices,
	moveItem,
	nextUnusedField,
	removeItem,
	replaceItem,
	rowForSelection,
	rowSelection,
	selectChoices,
	valueKind,
} from './list-utils';

export const LABEL_MAX = 60;
export const VALUE_MAX = 200;

/**
 * The rows of an entry: a field (or a one-time label) and its value.
 *
 * @param {Object}                   props
 * @param {Object[]}                 props.rows     Rows of the entry.
 * @param {Object[]}                 props.fields   Field definitions.
 * @param {(rows: Object[]) => void} props.onChange Called with the new list.
 */
export default function RowsEditor( { rows, fields, onChange } ) {
	const keyed = useKeyedList( rows );
	const root = useRef( null );
	const focus = useRef( null );
	const [ status, setStatus ] = useState( '' );

	// Puts the focus where the last action asked for it.
	useEffect( () => {
		if ( focus.current ) {
			root.current
				?.querySelector( `[data-focus="${ focus.current }"]` )
				?.focus();
			focus.current = null;
		}
	} );

	const change = ( index, row ) =>
		onChange( replaceItem( rows, index, row ) );

	function add() {
		const field = nextUnusedField( fields, rows );
		const key = keyed.add();

		onChange( [
			...rows,
			field ? { field: field.slug, value: '' } : { label: '', value: '' },
		] );
		focus.current = `${ key }:${ field ? 'value' : 'label' }`;
		setStatus( __( 'Row added.', 'lunar-download' ) );
	}

	function move( index, offset ) {
		const target = index + offset;

		keyed.move( index, offset );
		onChange( moveItem( rows, index, offset ) );

		// A disabled button cannot take focus, so use the other direction at an edge.
		const atEdge = 0 === target || rows.length - 1 === target;
		const goesUp = offset < 0;

		focus.current = `${ keyed.keys[ target ] }:${
			goesUp !== atEdge ? 'up' : 'down'
		}`;
		setStatus(
			sprintf(
				/* translators: %d: new position of the row. */
				__( 'Row moved to position %d.', 'lunar-download' ),
				target + 1
			)
		);
	}

	function remove( index ) {
		keyed.remove( index );
		onChange( removeItem( rows, index ) );

		const next = keyed.keys[ index ] || keyed.keys[ index - 1 ];

		focus.current = next ? `${ next }:remove` : 'add-row';
		setStatus( __( 'Row removed.', 'lunar-download' ) );
	}

	return (
		<div className="lunar-dl-editor__list-wrap" ref={ root }>
			{ 0 === rows.length ? (
				<p className="lunar-dl-editor__empty">
					{ __( 'No rows yet.', 'lunar-download' ) }
				</p>
			) : (
				<ul className="lunar-dl-editor__list">
					{ rows.map( ( row, index ) => {
						const key = keyed.keys[ index ];
						const selection = rowSelection( row );
						const kind = valueKind( fields, row );
						const value =
							'string' === typeof row.value ? row.value : '';
						const name = sprintf(
							/* translators: 1: position of the row, 2: label of the row. */
							__( 'row %1$d (%2$s)', 'lunar-download' ),
							index + 1,
							kind.label ||
								row.label ||
								__( 'no label', 'lunar-download' )
						);
						const valueLabel =
							kind.label || __( 'Value', 'lunar-download' );

						return (
							<li
								key={ key }
								className="lunar-dl-editor__item"
								role="group"
								aria-label={ name }
							>
								<div className="lunar-dl-editor__controls">
									<SelectControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										label={ __(
											'Field',
											'lunar-download'
										) }
										value={ selection }
										options={ fieldChoices( fields, row ) }
										data-focus={ `${ key }:field` }
										onChange={ ( chosen ) =>
											change(
												index,
												rowForSelection(
													row,
													chosen,
													fields
												)
											)
										}
									/>

									{ CUSTOM === selection && (
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											label={ __(
												'Label',
												'lunar-download'
											) }
											value={
												'string' === typeof row.label
													? row.label
													: ''
											}
											maxLength={ LABEL_MAX }
											data-focus={ `${ key }:label` }
											onChange={ ( label ) =>
												change( index, {
													...row,
													label,
												} )
											}
										/>
									) }

									{ 'select' === kind.type ? (
										<SelectControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											label={ valueLabel }
											value={ value }
											options={ selectChoices(
												kind.options,
												value
											) }
											data-focus={ `${ key }:value` }
											onChange={ ( chosen ) =>
												change( index, {
													...row,
													value: chosen,
												} )
											}
										/>
									) : (
										<TextControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											type={
												'date' === kind.type
													? 'date'
													: 'text'
											}
											label={ valueLabel }
											value={ value }
											maxLength={ VALUE_MAX }
											data-focus={ `${ key }:value` }
											onChange={ ( typed ) =>
												change( index, {
													...row,
													value: typed,
												} )
											}
										/>
									) }
								</div>

								<ItemActions
									index={ index }
									count={ rows.length }
									name={ name }
									focusKey={ key }
									onMove={ move }
									onRemove={ remove }
								/>
							</li>
						);
					} ) }
				</ul>
			) }

			<Button variant="secondary" data-focus="add-row" onClick={ add }>
				{ __( 'Add row', 'lunar-download' ) }
			</Button>

			<p className="lunar-dl-visually-hidden" role="status">
				{ status }
			</p>
		</div>
	);
}
