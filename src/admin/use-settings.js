import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

const PATH = '/wp/v2/settings';
const KEY = 'lunar_download_settings';

/**
 * Loads the plugin settings once and saves changes to them.
 *
 * The server resets every part that is missing from a save, so save() always
 * sends the whole object with the changes merged in.
 *
 * @return {{settings: ?Object, error: string, isSaving: boolean, save: (changes: Object) => Promise<Object>}} Settings state.
 */
export default function useSettings() {
	const [ settings, setSettings ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ isSaving, setIsSaving ] = useState( false );
	const latest = useRef( null );

	useEffect( () => {
		let active = true;

		apiFetch( { path: PATH } )
			.then( ( data ) => {
				if ( active ) {
					latest.current = data[ KEY ];
					setSettings( data[ KEY ] );
				}
			} )
			.catch( ( reason ) => {
				if ( active ) {
					setError( reason.message || '' );
				}
			} );

		return () => {
			active = false;
		};
	}, [] );

	/**
	 * @param {Object} changes Parts of the settings to replace.
	 * @return {Promise<Object>} The settings as the server stored them.
	 */
	const save = useCallback( async ( changes ) => {
		setIsSaving( true );

		try {
			const data = await apiFetch( {
				path: PATH,
				method: 'POST',
				data: { [ KEY ]: { ...latest.current, ...changes } },
			} );

			latest.current = data[ KEY ];
			setSettings( data[ KEY ] );

			return data[ KEY ];
		} finally {
			setIsSaving( false );
		}
	}, [] );

	return { settings, error, isSaving, save };
}
