import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Picks the entry image from the Media Library. Only the attachment ID is kept.
 *
 * @param {Object}               props
 * @param {number}               props.imageId  Attachment ID, 0 for none.
 * @param {(id: number) => void} props.onSelect Called with the chosen attachment ID.
 * @param {() => void}           props.onRemove Called when the image is removed.
 */
export default function ImagePicker( { imageId, onSelect, onRemove } ) {
	const { media, isLoading } = useSelect(
		( select ) => {
			if ( ! imageId ) {
				return { media: null, isLoading: false };
			}

			const core = select( 'core' );

			return {
				media: core.getMedia( imageId ) || null,
				isLoading: ! core.hasFinishedResolution( 'getMedia', [
					imageId,
				] ),
			};
		},
		[ imageId ]
	);

	const thumbnail =
		media?.media_details?.sizes?.thumbnail?.source_url || media?.source_url;

	const choose = ( label, variant ) => (
		<MediaUpload
			allowedTypes={ [ 'image' ] }
			value={ imageId }
			onSelect={ ( chosen ) => onSelect( chosen.id ) }
			render={ ( { open } ) => (
				<Button variant={ variant } onClick={ open }>
					{ label }
				</Button>
			) }
		/>
	);

	return (
		<MediaUploadCheck
			fallback={
				<p>
					{ __(
						'You are not allowed to use the Media Library.',
						'lunar-download'
					) }
				</p>
			}
		>
			<div className="lunar-dl-image-picker">
				{ imageId > 0 && isLoading && <Spinner /> }

				{ imageId > 0 && ! isLoading && thumbnail && (
					<img
						className="lunar-dl-image-picker__preview"
						src={ thumbnail }
						alt=""
					/>
				) }

				{ imageId > 0 && ! isLoading && ! media && (
					<p className="lunar-dl-image-picker__missing">
						{ __(
							'The image is missing from the Media Library. Choose another one.',
							'lunar-download'
						) }
					</p>
				) }

				<div className="lunar-dl-image-picker__actions">
					{ imageId > 0
						? choose(
								__( 'Replace image', 'lunar-download' ),
								'secondary'
							)
						: choose(
								__( 'Choose image', 'lunar-download' ),
								'secondary'
							) }

					{ imageId > 0 && (
						<Button
							variant="tertiary"
							isDestructive
							onClick={ onRemove }
						>
							{ __( 'Remove image', 'lunar-download' ) }
						</Button>
					) }
				</div>
			</div>
		</MediaUploadCheck>
	);
}
