import { dateI18n, getSettings } from '@wordpress/date';
import { __ } from '@wordpress/i18n';

import { parseDate } from './entry';

/**
 * Formats a stored date with the date format of the site.
 *
 * @param {{year: number, month: number, day: number}} parts Date parts.
 * @return {string} The formatted date.
 */
export function formatSiteDate( { year, month, day } ) {
	return dateI18n(
		getSettings().formats.date,
		new Date( Date.UTC( year, month - 1, day, 12 ) ),
		'UTC'
	);
}

function getImage( media, size ) {
	if ( ! media ) {
		return null;
	}

	const sized = media.media_details?.sizes?.[ size ];
	const src = sized?.source_url || media.source_url;

	return src ? { src, width: sized?.width, height: sized?.height } : null;
}

/**
 * Shows an entry the way the page does, with the same markup and classes, so
 * that the stylesheet of the block applies. It is not interactive: the
 * editors below it are the way to change the entry.
 *
 * @param {Object}                                                        props
 * @param {Object}                                                        props.entry      Entry from buildEntry().
 * @param {?Object}                                                       props.media      Media item of the image, if any.
 * @param {(parts: {year: number, month: number, day: number}) => string} props.formatDate Formats date parts; the site format by default.
 */
export default function EntryPreview( {
	entry,
	media,
	formatDate = formatSiteDate,
} ) {
	const image = getImage(
		media,
		'compact' === entry.preset ? 'medium' : 'large'
	);

	if ( ! image && ! entry.rows.length && ! entry.links.length ) {
		return null;
	}

	return (
		<article
			className={ `lunar-dl-entry lunar-dl-entry--${ entry.preset }` }
			aria-hidden="true"
			inert=""
		>
			{ image && (
				<figure className="lunar-dl-entry__media">
					<img
						className="lunar-dl-entry__image"
						src={ image.src }
						width={ image.width }
						height={ image.height }
						alt={ entry.imageAlt || media.alt_text || '' }
					/>
				</figure>
			) }

			{ entry.rows.length > 0 && (
				<dl className="lunar-dl-entry__rows">
					{ entry.rows.map( ( row, index ) => {
						const parts =
							'date' === row.type ? parseDate( row.value ) : null;

						return (
							<div className="lunar-dl-entry__row" key={ index }>
								<dt>{ row.label }</dt>
								<dd>
									{ parts ? (
										<time dateTime={ row.value }>
											{ formatDate( parts ) }
										</time>
									) : (
										row.value
									) }
								</dd>
							</div>
						);
					} ) }
				</dl>
			) }

			{ entry.links.length > 0 && (
				<ul className="lunar-dl-entry__links">
					{ entry.links.map( ( link, index ) => (
						<li key={ index }>
							<a className="lunar-dl-button" href={ link.url }>
								{ link.label }
								{ link.newTab && (
									<span className="lunar-dl-visually-hidden">
										{ ' ' }
										{ __(
											'(opens in a new tab)',
											'lunar-download'
										) }
									</span>
								) }
							</a>
						</li>
					) ) }
				</ul>
			) }
		</article>
	);
}
