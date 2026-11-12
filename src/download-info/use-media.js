import { useSelect } from '@wordpress/data';

/**
 * The media item behind an attachment ID, from the editor's data store.
 *
 * @param {number} imageId Attachment ID, 0 for none.
 * @return {?Object} The media item, or null while it loads or when it is missing.
 */
export default function useMedia( imageId ) {
	return useSelect(
		( select ) =>
			( imageId ? select( 'core' ).getMedia( imageId ) : null ) || null,
		[ imageId ]
	);
}
