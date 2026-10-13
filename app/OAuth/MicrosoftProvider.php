<?php
namespace App\OAuth;

// Only request the Graph profile fields this website uses.
class MicrosoftProvider extends \SocialiteProviders\Microsoft\Provider
{
    protected const DEFAULT_FIELDS_USER = ['id', 'displayName', 'mail', 'userPrincipalName'];
}
