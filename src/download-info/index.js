import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import { DownloadIcon } from './icons';
import './editor.scss';
import './style.scss';

registerBlockType( metadata.name, {
	icon: <DownloadIcon />,
	edit: Edit,
	save: () => null,
} );
