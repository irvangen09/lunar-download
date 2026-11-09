import { useBlockProps } from '@wordpress/block-editor';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { activeFields, getEditorData } from './editor-data';
import Inspector, { getPresets } from './Inspector';
import LinksEditor from './LinksEditor';
import RowsEditor from './RowsEditor';

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

	return (
		<>
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<div { ...blockProps }>
				<p className="lunar-dl-editor__title">
					{ name || __( 'Download Info', 'lunar-download' ) }
				</p>
				<p className="lunar-dl-editor__meta">
					{ imageId > 0
						? `${ style.label } · ${ __(
								'with image',
								'lunar-download'
							) }`
						: style.label }
				</p>

				<div className="lunar-dl-editor__section">
					<p className="lunar-dl-editor__heading">
						{ __( 'Rows', 'lunar-download' ) }
					</p>

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

					<RowsEditor
						rows={ rows }
						fields={ fields }
						onChange={ ( next ) => setAttributes( { rows: next } ) }
					/>
				</div>

				<div className="lunar-dl-editor__section">
					<p className="lunar-dl-editor__heading">
						{ __( 'Links', 'lunar-download' ) }
					</p>

					<LinksEditor
						links={ links }
						onChange={ ( next ) =>
							setAttributes( { links: next } )
						}
					/>
				</div>
			</div>
		</>
	);
}
