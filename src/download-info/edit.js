import { useBlockProps } from '@wordpress/block-editor';
import { createInterpolateElement } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

import { activeFields, getEditorData } from './editor-data';
import Inspector, { getPresets } from './Inspector';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'lunar-dl-editor' } );
	const { fields, settingsUrl } = getEditorData();
	const { name, preset, imageId } = attributes;
	const rows = Array.isArray( attributes.rows ) ? attributes.rows : [];
	const links = Array.isArray( attributes.links ) ? attributes.links : [];

	const presets = getPresets();
	const style =
		presets.find( ( item ) => item.value === preset ) || presets[ 0 ];
	const hasFields = activeFields( fields ).length > 0;

	const summary = [
		[
			__( 'Name', 'lunar-download' ),
			name || __( 'None', 'lunar-download' ),
		],
		[ __( 'Style', 'lunar-download' ), style.label ],
		[
			__( 'Image', 'lunar-download' ),
			imageId > 0
				? __( 'Chosen', 'lunar-download' )
				: __( 'None', 'lunar-download' ),
		],
		[
			__( 'Rows', 'lunar-download' ),
			sprintf(
				/* translators: %d: number of rows. */
				_n( '%d row', '%d rows', rows.length, 'lunar-download' ),
				rows.length
			),
		],
		[
			__( 'Links', 'lunar-download' ),
			sprintf(
				/* translators: %d: number of links. */
				_n( '%d link', '%d links', links.length, 'lunar-download' ),
				links.length
			),
		],
	];

	return (
		<>
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<div { ...blockProps }>
				<p className="lunar-dl-editor__title">
					{ __( 'Download Info', 'lunar-download' ) }
				</p>

				<dl className="lunar-dl-editor__summary">
					{ summary.map( ( [ label, value ] ) => (
						<div key={ label }>
							<dt>{ label }</dt>
							<dd>{ value }</dd>
						</div>
					) ) }
				</dl>

				{ ! hasFields && (
					<p className="lunar-dl-editor__hint">
						{ settingsUrl
							? createInterpolateElement(
									__(
										'There are no fields yet. Create them in <a>Settings → Lunar Download → Fields</a>.',
										'lunar-download'
									),
									// The anchor content comes from the translated string above.
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									{ a: <a href={ settingsUrl } /> }
								)
							: __(
									'There are no fields yet. Ask an administrator to create them.',
									'lunar-download'
								) }
					</p>
				) }

				{ 0 === links.length && (
					<p className="lunar-dl-editor__hint">
						{ __(
							'Add a download link so that readers have something to click.',
							'lunar-download'
						) }
					</p>
				) }
			</div>
		</>
	);
}
