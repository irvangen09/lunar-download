import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';

import './style.scss';

function SettingsApp() {
	const tabs = [
		{ name: 'fields', title: __( 'Fields', 'lunar-download' ) },
		{ name: 'links', title: __( 'Links', 'lunar-download' ) },
		{ name: 'appearance', title: __( 'Appearance', 'lunar-download' ) },
	];

	const messages = {
		fields: __(
			'Field management is not available yet.',
			'lunar-download'
		),
		links: __( 'Link settings are not available yet.', 'lunar-download' ),
		appearance: __(
			'Appearance settings are not available yet.',
			'lunar-download'
		),
	};

	return (
		<div className="lunar-dl-settings">
			<TabPanel tabs={ tabs }>
				{ ( tab ) => <p>{ messages[ tab.name ] }</p> }
			</TabPanel>
		</div>
	);
}

const root = document.getElementById( 'lunar-download-settings' );

if ( root ) {
	createRoot( root ).render( <SettingsApp /> );
}
