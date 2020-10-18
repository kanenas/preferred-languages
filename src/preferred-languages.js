import { render, useCallback, useEffect, useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './styles.css';
import ActiveLocales from './components/ActiveLocales';
import InactiveLocales from './components/InactiveLocales';

function reorder( list, srcIndex, destIndex ) {
	if ( srcIndex < destIndex ) {
		return [
			...list.slice( 0, srcIndex ),
			list.slice( srcIndex + 1, destIndex + 1 ),
			list[ srcIndex ],
			...list.slice( destIndex + 1 ),
		];
	}

	return [
		...list.slice( 0, destIndex ),
		list[ srcIndex ],
		list.slice( destIndex, srcIndex ),
		...list.slice( srcIndex + 1 ),
	];
}

function PreferredLanguages( props ) {
	const {
		options = [],
		hasMissingTranslations = false,
		currentLocale = 'en_US',
	} = props;

	const [ preferredLanguages, setPreferredLanguages ] = useState(
		props.preferredLanguages
	);

	const [ selectedLanguage, setSelectedLanguage ] = useState( null );
	const [ selectedInactiveLanguage, setSelectedInactiveLanguage ] = useState(
		options[ 0 ]
	);

	const onSelectInactiveLanguage = ( locale ) => {
		setSelectedInactiveLanguage(
			options.find( ( language ) => locale === language.locale )
		);
	};

	useEffect( () => {
		if ( ! selectedLanguage && preferredLanguages.length > 0 ) {
			setSelectedLanguage( preferredLanguages[ 0 ] );
		}
	}, [ preferredLanguages.length ] );

	const removePreferredLanguage = useCallback( () => {
		const foundIndex = preferredLanguages.findIndex(
			( { locale } ) => locale === selectedLanguage?.locale
		);

		setSelectedLanguage(
			foundIndex !== undefined
				? preferredLanguages[ foundIndex + 1 ]
				: preferredLanguages[ 0 ] || null
		);

		setPreferredLanguages( ( current ) =>
			current.filter(
				( { locale } ) => locale !== selectedLanguage.locale
			)
		);
	} );

	const inactiveLocales = options.filter(
		( language ) =>
			! preferredLanguages.find(
				( { locale } ) => locale === language.locale
			)
	);

	const onAddLanguage = useCallback( () => {
		const foundIndex = options.findIndex(
			( { locale } ) => locale === selectedInactiveLanguage?.locale
		);

		setSelectedInactiveLanguage(
			foundIndex !== undefined
				? options[ foundIndex + 1 ]
				: options[ 0 ] || null
		);

		if ( foundIndex !== undefined ) {
			setPreferredLanguages( ( current ) => [
				...current,
				options[ foundIndex ],
			] );
		}
	} );

	return (
		<div className="preferred-languages">
			<HiddenFormField preferredLanguages={ preferredLanguages } />
			<p>
				{ __(
					'Choose languages for displaying WordPress in, in order of preference.',
					'preferred-languages'
				) }
			</p>
			<ActiveLocales
				languages={ preferredLanguages }
				selectedLanguage={ selectedLanguage }
				onSelectLanguage={ setSelectedLanguage }
				onRemoveLanguage={ removePreferredLanguage }
			/>
			<InactiveLocales
				options={ inactiveLocales }
				onSelectInactiveLanguage={ onSelectInactiveLanguage }
				onAddLanguage={ onAddLanguage }
			/>
			{ hasMissingTranslations && <MissingTranslationsNotice /> }
		</div>
	);
}

function MissingTranslationsNotice() {
	return (
		<Notice status="warning" isDismissible={ false }>
			{ __(
				'Some of the languages are not installed. Re-save changes to download translations.',
				'preferred-languages'
			) }
		</Notice>
	);
}

function HiddenFormField( { preferredLanguages } ) {
	const value = preferredLanguages
		.filter( ( language ) => Boolean( language ) )
		.map( ( { locale } ) => locale )
		.join( ',' );

	return <input type="hidden" name="preferred_languages" value={ value } />;
}

global.PreferredLanguages = global.PreferredLanguages || {};
global.PreferredLanguages.render = ( id, props ) => {
	render(
		<PreferredLanguages { ...props } />,
		document.getElementById( id )
	);
};
