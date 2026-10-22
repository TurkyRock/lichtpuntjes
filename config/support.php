<?php
return [
 'enabled'=>env('SUPPORT_ENABLED',false),
 'key'=>env('MOLLIE_API_KEY',''),
 'recipient'=>env('SUPPORT_RECIPIENT',''),
 'contact'=>env('SUPPORT_CONTACT_EMAIL',''),
];
