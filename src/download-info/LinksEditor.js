import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

import ItemActions from './ItemActions';
import useKeyedList from './use-keyed-list';
import {
	cleanLink,
	linkProblem,
	moveItem,
	removeItem,
	replaceItem,
} from './list-utils';

export const LABEL_MAX = 60;

function getRelModes() {
	return [
		{ value: 'auto', label: __( 'Automatic', 'lunar-download' ) },
		{ value: 'follow', label: __( 'Follow', 'lunar-download' ) },
		{ value: 'nofollow', label: __( 'Nofollow', 'lunar-download' ) },
	];
}

/**
 * The download buttons of an entry.
 *
 * @param {Object}                    props
 * @param {Object[]}                  props.links    Links of the entry.
 * @param {(links: Object[]) => void} props.onChange Called with the new list.
 */
export default function LinksEditor( { links, onChange } ) {
	const keyed = useKeyedList( links );
	const root = useRef( null );
	const focus = useRef( null );
	const [ status, setStatus ] = useState( '' );
	const [ open, setOpen ] = useState( {} );

	// Puts the focus where the last action asked for it.
	useEffect( () => {
		if ( focus.current ) {
			root.current
				?.querySelector( `[data-focus="${ focus.current }"]` )
				?.focus();
			focus.current = null;
		}
	} );

	const change = ( index, changes ) =>
		onChange(
			replaceItem(
				links,
				index,
				cleanLink( { ...links[ index ], ...changes } )
			)
		);

	function add() {
		const key = keyed.add();

		onChange( [ ...links, { url: '' } ] );
		focus.current = `${ key }:label`;
		setStatus( __( 'Link added.', 'lunar-download' ) );
	}

	function move( index, offset ) {
		const target = index + offset;

		keyed.move( index, offset );
		onChange( moveItem( links, index, offset ) );

		// A disabled button cannot take focus, so use the other direction at an edge.
		const atEdge = 0 === target || links.length - 1 === target;
		const goesUp = offset < 0;

		focus.current = `${ keyed.keys[ target ] }:${
			goesUp !== atEdge ? 'up' : 'down'
		}`;
		setStatus(
			sprintf(
				/* translators: %d: new position of the link. */
				__( 'Link moved to position %d.', 'lunar-download' ),
				target + 1
			)
		);
	}

	function remove( index ) {
		keyed.remove( index );
		onChange( removeItem( links, index ) );

		const next = keyed.keys[ index ] || keyed.keys[ index - 1 ];

		focus.current = next ? `${ next }:remove` : 'add-link';
		setStatus( __( 'Link removed.', 'lunar-download' ) );
	}

	function urlHelp( problem ) {
		if ( 'invalid' === problem ) {
			return __(
				'This link will not be shown. Use an address that starts with http:// or https://, or a path on this site that starts with /.',
				'lunar-download'
			);
		}

		return __(
			'Where the button leads. Without an address the link is not shown.',
			'lunar-download'
		);
	}

	return (
		<div className="lunar-dl-editor__list-wrap" ref={ root }>
			{ 0 === links.length ? (
				<p className="lunar-dl-editor__empty">
					{ __(
						'No links yet. Add a download link so that readers have something to click.',
						'lunar-download'
					) }
				</p>
			) : (
				<ul className="lunar-dl-editor__list">
					{ links.map( ( link, index ) => {
						const key = keyed.keys[ index ];
						const problem = linkProblem( link.url );
						const isOpen = true === open[ key ];
						const name = sprintf(
							/* translators: 1: position of the link, 2: label of the link. */
							__( 'link %1$d (%2$s)', 'lunar-download' ),
							index + 1,
							link.label || __( 'Download', 'lunar-download' )
						);

						return (
							<li
								key={ key }
								className="lunar-dl-editor__item"
								role="group"
								aria-label={ name }
							>
								<div className="lunar-dl-editor__controls">
									<TextControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										label={ __(
											'Button text',
											'lunar-download'
										) }
										placeholder={ __(
											'Download',
											'lunar-download'
										) }
										value={ link.label || '' }
										maxLength={ LABEL_MAX }
										data-focus={ `${ key }:label` }
										onChange={ ( label ) =>
											change( index, { label } )
										}
									/>
									<TextControl
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										type="url"
										className={
											'invalid' === problem
												? 'is-invalid'
												: ''
										}
										label={ __(
											'Address',
											'lunar-download'
										) }
										help={ urlHelp( problem ) }
										value={ link.url || '' }
										aria-invalid={ 'invalid' === problem }
										data-focus={ `${ key }:url` }
										onChange={ ( url ) =>
											change( index, { url } )
										}
									/>
								</div>

								<Button
									variant="link"
									aria-expanded={ isOpen }
									onClick={ () =>
										setOpen( {
											...open,
											[ key ]: ! isOpen,
										} )
									}
								>
									{ isOpen
										? __( 'Hide options', 'lunar-download' )
										: __( 'Options', 'lunar-download' ) }
								</Button>

								{ isOpen && (
									<div className="lunar-dl-editor__options">
										<SelectControl
											__nextHasNoMarginBottom
											__next40pxDefaultSize
											label={ __(
												'Follow rule',
												'lunar-download'
											) }
											help={ __(
												'Automatic marks links to other websites as nofollow and leaves links to your own websites alone.',
												'lunar-download'
											) }
											value={ link.relMode || 'auto' }
											options={ getRelModes() }
											onChange={ ( relMode ) =>
												change( index, { relMode } )
											}
										/>
										<ToggleControl
											__nextHasNoMarginBottom
											label={ __(
												'Open in a new tab',
												'lunar-download'
											) }
											checked={ true === link.newTab }
											onChange={ ( newTab ) =>
												change( index, { newTab } )
											}
										/>
									</div>
								) }

								<ItemActions
									index={ index }
									count={ links.length }
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

			<Button variant="secondary" data-focus="add-link" onClick={ add }>
				{ __( 'Add link', 'lunar-download' ) }
			</Button>

			<p className="lunar-dl-visually-hidden" role="status">
				{ status }
			</p>
		</div>
	);
}
