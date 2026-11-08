import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import ImagePicker from './ImagePicker';

export const NAME_MAX = 200;

export function getPresets() {
	return [
		{
			value: 'table',
			label: __( 'Table', 'lunar-download' ),
			help: __(
				'Labels and values in rows, with the buttons below.',
				'lunar-download'
			),
		},
		{
			value: 'card',
			label: __( 'Card', 'lunar-download' ),
			help: __(
				'A card with the image on top and the values as small tags.',
				'lunar-download'
			),
		},
		{
			value: 'compact',
			label: __( 'Compact', 'lunar-download' ),
			help: __(
				'One line with a small image, the first two values, and one button.',
				'lunar-download'
			),
		},
		{
			value: 'detail',
			label: __( 'Detail', 'lunar-download' ),
			help: __(
				'A grid of boxed values, with all buttons side by side.',
				'lunar-download'
			),
		},
	];
}

/**
 * Settings of the block in the editor sidebar.
 *
 * @param {Object}                    props
 * @param {Object}                    props.attributes    Block attributes.
 * @param {(changes: Object) => void} props.setAttributes Updates block attributes.
 */
export default function Inspector( { attributes, setAttributes } ) {
	const { name, preset, imageId, imageAlt } = attributes;
	const presets = getPresets();
	const current =
		presets.find( ( item ) => item.value === preset ) || presets[ 0 ];

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Entry', 'lunar-download' ) }>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Name', 'lunar-download' ) }
					help={ __(
						'Used for the names of the buttons and for structured data. It is not shown as a title: write the title in a Heading block above this one.',
						'lunar-download'
					) }
					value={ name }
					maxLength={ NAME_MAX }
					onChange={ ( value ) => setAttributes( { name: value } ) }
				/>
			</PanelBody>

			<PanelBody title={ __( 'Style', 'lunar-download' ) }>
				<SelectControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Style', 'lunar-download' ) }
					help={ current.help }
					value={ current.value }
					options={ presets.map( ( { value, label } ) => ( {
						value,
						label,
					} ) ) }
					onChange={ ( value ) => setAttributes( { preset: value } ) }
				/>
			</PanelBody>

			<PanelBody title={ __( 'Image', 'lunar-download' ) }>
				<ImagePicker
					imageId={ imageId }
					onSelect={ ( id ) => setAttributes( { imageId: id } ) }
					onRemove={ () =>
						setAttributes( { imageId: 0, imageAlt: '' } )
					}
				/>

				{ imageId > 0 && (
					<TextareaControl
						__nextHasNoMarginBottom
						className="lunar-dl-image-alt"
						label={ __( 'Alternative text', 'lunar-download' ) }
						help={ __(
							'Leave empty to use the alternative text from the Media Library.',
							'lunar-download'
						) }
						value={ imageAlt }
						maxLength={ NAME_MAX }
						rows={ 3 }
						onChange={ ( value ) =>
							setAttributes( { imageAlt: value } )
						}
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
}
