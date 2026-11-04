import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	const blockProps = useBlockProps( { className: 'lunar-dl-editor' } );

	return (
		<div { ...blockProps }>
			<p className="lunar-dl-editor__title">
				{ __( 'Download Info', 'lunar-download' ) }
			</p>
			<p className="lunar-dl-editor__notice">
				{ __(
					'The download entry editor is not available yet.',
					'lunar-download'
				) }
			</p>
		</div>
	);
}
