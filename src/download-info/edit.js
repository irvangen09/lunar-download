import { useBlockProps } from '@wordpress/block-editor';
import { createInterpolateElement, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { activeFields, getEditorData } from './editor-data';
import { buildEntry } from './entry';
import EntryPreview from './EntryPreview';
import Inspector from './Inspector';
import LinksEditor from './LinksEditor';
import RowsEditor from './RowsEditor';
import useMedia from './use-media';

function NoFieldsHint( { settingsUrl } ) {
	if ( ! settingsUrl ) {
		return (
			<p className="lunar-dl-editor__hint">
				{ __(
					'There are no fields yet. Ask an administrator to create them.',
					'lunar-download'
				) }
			</p>
		);
	}

	return (
		<p className="lunar-dl-editor__hint">
			{ createInterpolateElement(
				__(
					'There are no fields yet. Create them in <a>Settings → Lunar Download → Fields</a>.',
					'lunar-download'
				),
				{
					// The content of the anchor comes from the translated string above.
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					a: <a href={ settingsUrl } />,
				}
			) }
		</p>
	);
}

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const blockProps = useBlockProps( { className: 'lunar-dl-editor' } );
	const { fields, settingsUrl } = getEditorData();
	const rows = Array.isArray( attributes.rows ) ? attributes.rows : [];
	const links = Array.isArray( attributes.links ) ? attributes.links : [];
	const hasFields = activeFields( fields ).length > 0;

	const entry = useMemo(
		() =>
			buildEntry(
				attributes,
				fields,
				__( 'Download', 'lunar-download' )
			),
		// The fields are read once when the editor opens.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ attributes ]
	);
	const media = useMedia( entry.imageId );

	return (
		<>
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<div { ...blockProps }>
				<EntryPreview entry={ entry } media={ media } />

				{ ! isSelected &&
					0 === entry.rows.length &&
					0 === entry.links.length &&
					! entry.imageId && (
						<p className="lunar-dl-editor__note">
							{ __(
								'Nothing to show yet. Select the block to add rows and links.',
								'lunar-download'
							) }
						</p>
					) }

				{ isSelected && (
					<div className="lunar-dl-editor__panel">
						<div className="lunar-dl-editor__section">
							<p className="lunar-dl-editor__heading">
								{ __( 'Rows', 'lunar-download' ) }
							</p>

							{ ! hasFields && (
								<NoFieldsHint settingsUrl={ settingsUrl } />
							) }

							<RowsEditor
								rows={ rows }
								fields={ fields }
								onChange={ ( next ) =>
									setAttributes( { rows: next } )
								}
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
				) }
			</div>
		</>
	);
}
