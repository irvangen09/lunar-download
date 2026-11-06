import { createRoot } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Notice, Spinner, TabPanel } from '@wordpress/components';

import FieldsTab from './fields/FieldsTab';
import useSettings from './use-settings';

import './style.scss';

function SettingsApp() {
	const { settings, error, isSaving, save } = useSettings();

	const tabs = [
		{ name: 'fields', title: __( 'Fields', 'lunar-download' ) },
		{ name: 'links', title: __( 'Links', 'lunar-download' ) },
		{ name: 'appearance', title: __( 'Appearance', 'lunar-download' ) },
	];

	const messages = {
		links: __( 'Link settings are not available yet.', 'lunar-download' ),
		appearance: __(
			'Appearance settings are not available yet.',
			'lunar-download'
		),
	};

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ sprintf(
					/* translators: %s: error message from the server. */
					__(
						'The settings could not be loaded. %s',
						'lunar-download'
					),
					error
				) }
			</Notice>
		);
	}

	if ( ! settings ) {
		return <Spinner />;
	}

	return (
		<div className="lunar-dl-settings">
			<TabPanel tabs={ tabs }>
				{ ( tab ) =>
					'fields' === tab.name ? (
						<FieldsTab
							fields={ settings.fields }
							isSaving={ isSaving }
							onSave={ ( fields ) => save( { fields } ) }
						/>
					) : (
						<p>{ messages[ tab.name ] }</p>
					)
				}
			</TabPanel>
		</div>
	);
}

const root = document.getElementById( 'lunar-download-settings' );

if ( root ) {
	createRoot( root ).render( <SettingsApp /> );
}
