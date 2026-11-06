import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Notice, TextareaControl } from '@wordpress/components';

export const MAX_DOMAINS = 100;

const toLines = ( text ) =>
	text
		.split( /\r?\n/ )
		.map( ( line ) => line.trim() )
		.filter( Boolean );

/**
 * Links tab: the domains that count as the site's own, and how links are
 * marked. The server normalizes the list, so the text shows what was stored
 * after saving.
 *
 * @param {Object}                                                  props
 * @param {string[]}                                                props.ownDomains Stored own domains.
 * @param {boolean}                                                 props.isSaving   Whether a save is running.
 * @param {(domains: string[]) => Promise<{own_domains: string[]}>} props.onSave     Saves the list; resolves with the stored settings and rejects on failure.
 */
export default function LinksTab( { ownDomains, isSaving, onSave } ) {
	const [ text, setText ] = useState( ownDomains.join( '\n' ) );
	const [ notice, setNotice ] = useState( null );
	const [ error, setError ] = useState( '' );

	async function handleSubmit( event ) {
		event.preventDefault();

		const lines = toLines( text );

		setNotice( null );

		if ( lines.length > MAX_DOMAINS ) {
			setError(
				sprintf(
					/* translators: %d: maximum number of domains. */
					__( 'Use %d domains or fewer.', 'lunar-download' ),
					MAX_DOMAINS
				)
			);

			return;
		}

		setError( '' );

		try {
			const saved = await onSave( lines );
			const removed = lines.length - saved.own_domains.length;

			setText( saved.own_domains.join( '\n' ) );
			setNotice( {
				status: removed > 0 ? 'warning' : 'success',
				message:
					removed > 0
						? sprintf(
								/* translators: %d: number of lines that were removed. */
								_n(
									'Domains saved. %d line was removed because it is not a valid domain or was repeated.',
									'Domains saved. %d lines were removed because they are not valid domains or were repeated.',
									removed,
									'lunar-download'
								),
								removed
							)
						: __( 'Domains saved.', 'lunar-download' ),
			} );
		} catch ( reason ) {
			setNotice( {
				status: 'error',
				message:
					reason.message ||
					__( 'The domains could not be saved.', 'lunar-download' ),
			} );
		}
	}

	return (
		<div className="lunar-dl-links">
			{ notice && (
				<Notice
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
					className="lunar-dl-links__notice"
				>
					{ notice.message }
				</Notice>
			) }

			<form onSubmit={ handleSubmit } className="lunar-dl-links__form">
				<TextareaControl
					__nextHasNoMarginBottom
					label={ __( 'Your own domains', 'lunar-download' ) }
					help={
						error ||
						__(
							'One domain per line, for example example.com. A domain also covers its subdomains. The domain of this site is always included.',
							'lunar-download'
						)
					}
					value={ text }
					onChange={ setText }
					rows={ 8 }
					aria-invalid={ !! error }
				/>

				<div>
					<Button
						variant="primary"
						type="submit"
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ __( 'Save domains', 'lunar-download' ) }
					</Button>
				</div>
			</form>

			<h3>{ __( 'How links are marked', 'lunar-download' ) }</h3>
			<ul className="lunar-dl-links__rules">
				<li>
					{ __(
						'Links to other websites are marked nofollow.',
						'lunar-download'
					) }
				</li>
				<li>
					{ __(
						'Links to this site, to the domains above, and to paths on this site are left followed.',
						'lunar-download'
					) }
				</li>
				<li>
					{ __(
						'A link that opens in a new tab also gets noopener.',
						'lunar-download'
					) }
				</li>
				<li>
					{ __(
						'Each link can override the rule in its Download Info block.',
						'lunar-download'
					) }
				</li>
			</ul>
		</div>
	);
}
