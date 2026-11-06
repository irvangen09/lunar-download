import { createRoot } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Notice, Spinner, TabPanel } from '@wordpress/components';

import FieldsTab from './fields/FieldsTab';
import LinksTab from './links/LinksTab';
import useSettings from './use-settings';

import './style.scss';

function SettingsApp() {
	const { settings, error, isSaving, save } = useSettings();

	const tabs = [
		{ name: 'fields', title: __( 'Fields', 'lunar-download' ) },
		{ name: 'links', title: __( 'Links', 'lunar-download' ) },
		{ name: 'appearance', title: __( 'Appearance', 'lunar-download' ) },
	];

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
				{ ( tab ) => {
					if ( 'fields' === tab.name ) {
						return (
							<FieldsTab
								fields={ settings.fields }
								isSaving={ isSaving }
								onSave={ ( fields ) => save( { fields } ) }
							/>
						);
					}

					if ( 'links' === tab.name ) {
						return (
							<LinksTab
								ownDomains={ settings.own_domains }
								isSaving={ isSaving }
								onSave={ ( ownDomains ) =>
									save( { own_domains: ownDomains } )
								}
							/>
						);
					}

					return (
						<p>
							{ __(
								'Appearance settings are not available yet.',
								'lunar-download'
							) }
						</p>
					);
				} }
			</TabPanel>
		</div>
	);
}

const root = document.getElementById( 'lunar-download-settings' );

if ( root ) {
	createRoot( root ).render( <SettingsApp /> );
}
