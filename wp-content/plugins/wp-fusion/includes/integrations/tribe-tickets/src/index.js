import { addFilter } from '@wordpress/hooks';
import { handleTicketsFilter } from './handlers/handleTicketsFilter.js';
import { handleRsvpFilter } from './handlers/handleRsvpFilter.js';

// Tickets.
addFilter(
	'editor.BlockEdit',
	'wp-fusion/wp-fusion-event-tickets-extension',
	handleTicketsFilter
);

// RSVP.
addFilter(
	'editor.BlockEdit',
	'wp-fusion/wp-fusion-event-rsvp-extension',
	handleRsvpFilter
);
