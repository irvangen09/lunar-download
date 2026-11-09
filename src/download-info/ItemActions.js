import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Buttons that move or remove one item of a list. The names tell which item
 * they act on.
 *
 * @param {Object}                                  props
 * @param {number}                                  props.index    Position of the item.
 * @param {number}                                  props.count    Number of items.
 * @param {string}                                  props.name     Name of the item, such as "row 2 (Size)".
 * @param {string}                                  props.focusKey Prefix of the focus keys of the buttons.
 * @param {(index: number, offset: number) => void} props.onMove   Moves the item up (-1) or down (1).
 * @param {(index: number) => void}                 props.onRemove Removes the item.
 */
export default function ItemActions( {
	index,
	count,
	name,
	focusKey,
	onMove,
	onRemove,
} ) {
	return (
		<div className="lunar-dl-editor__actions">
			<Button
				variant="tertiary"
				size="small"
				data-focus={ `${ focusKey }:up` }
				disabled={ 0 === index }
				aria-label={ sprintf(
					/* translators: %s: name of the item, such as "row 2 (Size)". */
					__( 'Move up: %s', 'lunar-download' ),
					name
				) }
				onClick={ () => onMove( index, -1 ) }
			>
				{ __( 'Move up', 'lunar-download' ) }
			</Button>
			<Button
				variant="tertiary"
				size="small"
				data-focus={ `${ focusKey }:down` }
				disabled={ index >= count - 1 }
				aria-label={ sprintf(
					/* translators: %s: name of the item, such as "row 2 (Size)". */
					__( 'Move down: %s', 'lunar-download' ),
					name
				) }
				onClick={ () => onMove( index, 1 ) }
			>
				{ __( 'Move down', 'lunar-download' ) }
			</Button>
			<Button
				variant="tertiary"
				size="small"
				isDestructive
				data-focus={ `${ focusKey }:remove` }
				aria-label={ sprintf(
					/* translators: %s: name of the item, such as "row 2 (Size)". */
					__( 'Remove: %s', 'lunar-download' ),
					name
				) }
				onClick={ () => onRemove( index ) }
			>
				{ __( 'Remove', 'lunar-download' ) }
			</Button>
		</div>
	);
}
