import { Button, VisuallyHidden } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';

import { getShortcut } from '../utils';

function InactiveLocales( {
	options,
	onSelectInactiveLanguage,
	onAddLanguage,
} ) {
	return (
		<div className="inactive-locales wp-clearfix">
			<label htmlFor="preferred-languages-inactive-locales">
				<VisuallyHidden>
					{ __( 'Inactive Locales', 'preferred-languages' ) }
				</VisuallyHidden>
			</label>
			<div className="inactive-locales-list">
				<InactiveLocalesSelect
					options={ options }
					onSelect={ onSelectInactiveLanguage }
				/>
			</div>
			<InactiveControls
				onAddLanguage={ onAddLanguage }
				hasItems={ Boolean( options.length ) }
			/>
		</div>
	);
}

function InactiveLocalesSelect( { options, onSelect } ) {
	const installedLanguages = options.filter( ( { installed } ) =>
		Boolean( installed )
	);
	const availableLanguages = options.filter(
		( { installed } ) => ! installed
	);

	return (
		// eslint-disable-next-line jsx-a11y/no-onchange
		<select
			id="preferred-languages-inactive-locales"
			name="preferred-languages-inactive-locales"
			onChange={ ( evt ) => onSelect( evt.target.value ) }
		>
			{ installedLanguages.length > 0 && (
				<optgroup
					label={ _x(
						'Installed',
						'translations',
						'preferred-languages'
					) }
				>
					{ installedLanguages.map(
						( { locale, lang, nativeName } ) => (
							<option
								key={ locale }
								value={ locale }
								lang={ lang }
							>
								{ nativeName }
							</option>
						)
					) }
				</optgroup>
			) }
			{ availableLanguages.length > 0 && (
				<optgroup
					label={ _x(
						'Available',
						'translations',
						'preferred-languages'
					) }
				>
					{ availableLanguages.map(
						( { locale, lang, nativeName } ) => (
							<option
								key={ locale }
								value={ locale }
								lang={ lang }
							>
								{ nativeName }
							</option>
						)
					) }
				</optgroup>
			) }
		</select>
	);
}

function InactiveControls( { hasItems, onAddLanguage } ) {
	return (
		<div className="inactive-locales-controls">
			<Button
				isSecondary
				shortcut={ getShortcut( 'A' ) }
				showTooltip
				aria-keyshortcuts="Alt+A"
				label={
					/* translators: accessibility text */
					__( 'Add to list', 'preferred-languages' )
				}
				disabled={ ! hasItems }
				onClick={ onAddLanguage }
			>
				{ __( 'Add', 'preferred-languages' ) }
			</Button>
		</div>
	);
}

export default InactiveLocales;
