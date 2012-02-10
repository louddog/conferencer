<?php

new Postype(array(
	'slug' => 'session',
	'archive' => 'sessions',
	'singular' => "Session",
	'plural' => "Sessions",
	'fields' => array(
		array('name' => '_keynote', 'type' => 'boolean', 'label' => "Keynote", 'description' => "This session is a keynote session."),
		array('name' => '_room', 'type' => 'post', 'label' => "Room", 'description' => "The room in which the session is to be held.", 'options' => array('post_type' => 'room')),
		array('name' => '_time_slot', 'type' => 'post', 'label' => "Time Slot", 'description' => "The time slot for the session.", 'options' => array('post_type' => 'time_slot')),
		array('name' => '_track', 'type' => 'post', 'label' => "Track", 'description' => "The track the session is in.", 'options' => array('post_type' => 'track')),
		array('name' => '_speakers', 'type' => 'multi-post', 'label' => "Speakers", 'description' => "The people speaking at the session.", 'options' => array('post_type' => 'speaker')),
		array('name' => '_sponsors', 'type' => 'multi-post', 'label' => "Sponsors", 'description' => "The companies sponsoring the session.", 'options' => array('post_type' => 'sponsor')),
	),
));

new Postype(array(
	'slug' => 'speaker',
	'archive' => 'speakers',
	'singular' => "Speaker",
	'plural' => "Speakers",
	'fields' => array(
		array('name' => '_title', 'type' => 'text', 'label' => "Title", 'description' => "The speaker's title."),
		array('name' => '_company', 'type' => 'post', 'label' => "Company", 'description' => "The speaker's company.", 'options' => array('post_type' => 'company')),
	),
));

new Postype(array(
	'slug' => 'company',
	'archive' => 'companies',
	'singular' => "Company",
	'plural' => "Companies",
));

new Postype(array(
	'slug' => 'room',
	'archive' => 'rooms',
	'singular' => "Room",
	'plural' => "Rooms",
));

new Postype(array(
	'slug' => 'time_slot',
	'archive' => 'time-slots',
	'singular' => "Time Slot",
	'plural' => "Time Slots",
	'fields' => array(
		array('name' => '_start', 'type' => "datetime", 'label' => "Start Time", 'description' => "The date and time the time slot starts."),
		array('name' => '_end', 'type' => "datetime", 'label' => "End Time", 'description' => "The date and time the time slot ends."),
		array('name' => '_non_session', 'type' => "boolean", 'label' => "Non Session", 'description' => "This time slot is not intended sessions (like lunch time)."),
		array('name' => '_link', 'type' => 'url', 'label' => "Link", 'description' => "Alternative URL for time slots."),
	),
));

new Postype(array(
	'slug' => 'track',
	'archive' => 'tracks',
	'singular' => "Track",
	'plural' => "Tracks",
));

new Postype(array(
	'slug' => 'sponsor',
	'archive' => 'sponsors',
	'singular' => "Sponsor",
	'plural' => "Sponsors",
	'fields' => array(
		array('name' => '_link', 'type' => 'url', 'label' => "Link", 'description' => "A link to the sponsor's page."),
		array('name' => '_level', 'type' => 'post', 'label' => "Sponsor Level", 'description' => "The sponsorship level for this sponsor.", 'options' => array('post_type' => 'sponsor_level')),
	),
));

new Postype(array(
	'slug' => 'sponsor_level',
	'archive' => 'sponsor-levels',
	'singular' => "Sponsor Level",
	'plural' => "Sponsor Levels",
));