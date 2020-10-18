import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';

export function getShortcut( shortcut ) {
	return {
		display: displayShortcut.alt( shortcut ),
		ariaLabel: shortcutAriaLabel.alt( shortcut ),
	};
}
