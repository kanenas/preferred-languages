import { useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';

import { getShortcut } from '../utils';

export function ActiveLocales( {
	languages = [],
	showOptionSiteDefault = false,
	selectedLanguage = '',
	onSelectLanguage = () => {},
	onRemoveLanguage = () => {},
} ) {
	const isEmpty = languages.length === 0;
	const activeDescendant = isEmpty ? '' : selectedLanguage;

	const className = isEmpty
		? 'active-locales-list empty-list'
		: 'active-locales-list';

	useEffect( () => {
		let emptyMessageA11y = sprintf(
			/* translators: %s: English (United States) */
			__(
				'No languages selected. Falling back to %s.',
				'preferred-languages'
			),
			'English (United States)'
		);

		if ( showOptionSiteDefault ) {
			emptyMessageA11y = __(
				'No languages selected. Falling back to Site Default.',
				'preferred-languages'
			);
		}

		speak( emptyMessageA11y );
	}, [ languages.length ] );

	let emptyMessage = sprintf(
		/* translators: %s: English (United States) */
		__( 'Falling back to %s.', 'preferred-languages' ),
		'English (United States)'
	);

	if ( showOptionSiteDefault ) {
		emptyMessage = __(
			'Falling back to Site Default.',
			'preferred-languages'
		);
	}

	return (
		<div className="active-locales wp-clearfix">
			{ isEmpty && (
				<div id="active-locales-empty-message">
					{ __( 'Nothing set.', 'preferred-languages' ) }
					<br />
					{ emptyMessage }
				</div>
			) }
			<ul
				role="listbox"
				aria-labelledby="preferred-languages-label"
				tabIndex="0"
				aria-activedescendant={ activeDescendant }
				id="preferred_languages"
				className={ className }
			>
				{ languages.map( ( language ) => {
					const { locale, nativeName, lang } = language;
					return (
						// eslint-disable-next-line jsx-a11y/click-events-have-key-events
						<li
							key={ locale }
							role="option"
							aria-selected={
								locale === selectedLanguage?.locale
							}
							id={ locale }
							lang={ lang }
							className="active-locale"
							onClick={ () => onSelectLanguage( language ) }
						>
							{ nativeName }
						</li>
					);
				} ) }
			</ul>
			<ActiveControls
				languages={ languages }
				selectedLanguage={ selectedLanguage }
				onMoveUp={ () => {} }
				onMoveDown={ () => {} }
				onRemove={ onRemoveLanguage }
			/>
		</div>
	);
}

function ActiveControls( {
	languages = [],
	selectedLanguage,
	onMoveUp,
	onMoveDown,
	onRemove,
} ) {
	const isMoveUpDisabled =
		! selectedLanguage ||
		languages[ 0 ]?.locale === selectedLanguage?.locale;
	const isMoveDownDisabled =
		! selectedLanguage ||
		languages[ languages.length - 1 ]?.locale === selectedLanguage?.locale;
	const isRemoveDisabled = ! selectedLanguage;

	return (
		<div className="active-locales-controls">
			<ul>
				<li>
					<Button
						isSecondary
						shortcut={ getShortcut( 'Up' ) }
						showTooltip
						aria-keyshortcuts="Alt+ArrowUp"
						label={
							/* translators: accessibility text */
							__( 'Move up', 'preferred-languages' )
						}
						disabled={ isMoveUpDisabled }
						onClick={ onMoveUp }
					>
						{ __( 'Move Up', 'preferred-languages' ) }
					</Button>
				</li>
				<li>
					<Button
						isSecondary
						shortcut={ getShortcut( 'Down' ) }
						showTooltip
						aria-keyshortcuts="Alt+ArrowDown"
						label={
							/* translators: accessibility text */
							__( 'Move down', 'preferred-languages' )
						}
						disabled={ isMoveDownDisabled }
						onClick={ onMoveDown }
					>
						{ __( 'Move Down', 'preferred-languages' ) }
					</Button>
				</li>
				<li>
					<Button
						isSecondary
						shortcut={ getShortcut( 'Delete' ) }
						showTooltip
						aria-keyshortcuts="Alt+Delete"
						label={
							/* translators: accessibility text */
							__( 'Remove from list', 'preferred-languages' )
						}
						disabled={ isRemoveDisabled }
						onClick={ onRemove }
					>
						{ __( 'Remove', 'preferred-languages' ) }
					</Button>
				</li>
			</ul>
		</div>
	);
}

export default ActiveLocales;
