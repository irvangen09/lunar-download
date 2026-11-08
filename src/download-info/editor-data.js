/**
 * Reads the data that PHP attaches to the editor script. A missing or broken
 * value gives empty defaults, so the editor still opens.
 *
 * @param {unknown} source Value of window.lunarDownloadEditor.
 * @return {{fields: Object[], settingsUrl: string}} Field definitions and the settings address.
 */
export function getEditorData( source = window.lunarDownloadEditor ) {
	const data = source && 'object' === typeof source ? source : {};

	return {
		fields: Array.isArray( data.fields ) ? data.fields : [],
		settingsUrl:
			'string' === typeof data.settingsUrl ? data.settingsUrl : '',
	};
}

/**
 * Fields that can still be picked for a new row.
 *
 * @param {Object[]} fields Field definitions.
 * @return {Object[]} The fields that are not archived.
 */
export function activeFields( fields ) {
	return fields.filter( ( field ) => ! field.archived );
}
