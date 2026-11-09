import { useRef } from '@wordpress/element';

let counter = 0;

const createKey = () => `item-${ ++counter }`;

/**
 * Gives each item of a list a key that follows it when it is moved or removed.
 * The keys exist only in the editor and are never saved. They keep the focus
 * on the same item when the list is reordered.
 *
 * Call add(), move() or remove() in the same handler that changes the list.
 *
 * @param {Object[]} items The list that is edited.
 * @return {{keys: string[], add: () => string, move: (index: number, offset: number) => void, remove: (index: number) => void}} The keys and the operations that keep them in step with the list.
 */
export default function useKeyedList( items ) {
	const keys = useRef( [] );

	// The list can also change from outside, for example by undo.
	while ( keys.current.length < items.length ) {
		keys.current.push( createKey() );
	}

	keys.current.length = items.length;

	return {
		keys: keys.current,

		add() {
			const key = createKey();

			keys.current.push( key );

			return key;
		},

		move( index, offset ) {
			const target = index + offset;

			if ( target < 0 || target >= keys.current.length ) {
				return;
			}

			[ keys.current[ index ], keys.current[ target ] ] = [
				keys.current[ target ],
				keys.current[ index ],
			];
		},

		remove( index ) {
			keys.current.splice( index, 1 );
		},
	};
}
