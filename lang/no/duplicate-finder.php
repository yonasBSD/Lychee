<?php
return [
    /*
	|--------------------------------------------------------------------------
	| Duplicate Finder Page
	|--------------------------------------------------------------------------
	*/
    'title' => 'Vedlikehold',
    'intro' => 'On this page you will find the duplicate pictures found in your database.',
    'found' => ' duplicates found!',
    'invalid-search' => ' At least the checksum or title condition must be checked.',
    'checksum-must-match' => 'Checksum must match.',
    'title-must-match' => 'Title must match.',
    'must-be-in-same-album' => 'Must be in the same album.',
    'columns' => [
        'album' => 'Album',
        'photo' => 'Photo',
        'checksum' => 'Checksum',
    ],
    'warning' => [
        'no-original-left' => 'No original left.',
        'keep-one' => 'You selected all duplicates in this group. Please chose at least one duplicate to keep.',
    ],
    'delete-selected' => 'Delete selected',
];
