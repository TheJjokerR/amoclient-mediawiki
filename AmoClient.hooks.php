<?php
/**
 * Hooks for AmoClient extension
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use MediaWiki\Auth\CreatedAccountAuthenticationRequest;
use MediaWiki\Auth\AuthManager;

class AmoClientHooks {
    const USERNAME_SESSION_KEY = 'AmoClientLoginUsername';
    const REALNAME_SESSION_KEY = 'AmoClientLoginRealname';
    const EMAIL_SESSION_KEY = 'AmoClientLoginEmail';
    const SPECIAL_PAGE_NAME = 'Special:AmoLoginCallback'; // Don't change

    private static function getPageTitle( $canonicalPageName, $namespace = NS_MAIN ){
        $titleFormatter = MediaWikiServices::getInstance()->getService('TitleFormatter');

        if($namespace == NS_SPECIAL){
            $canonicalPageName = SpecialPage::getSafeTitleFor($canonicalPageName);
        }

        $titleValue = $titleFormatter->parseTitle($canonicalPageName, $namespace);
        return Title::newFromTitleValue($titleValue);
    }

	public static function onBeforeInitialize( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
        global $wgAmoLoginClientPages;

        $requestTitle = $request->getVal('title');

        // Get the page titles with their prefixes
        $loginPageTitlePrefixed = self::getPageTitle('Userlogin', NS_SPECIAL)->getPrefixedDBkey();
        $registerPageTitlePrefixed = self::getPageTitle('CreateAccount', NS_SPECIAL)->getPrefixedDBkey();
        $logoutPageTitlePrefixed = self::getPageTitle('Userlogout', NS_SPECIAL)->getPrefixedDBkey();
        $passChangePageTitlePrefixed = self::getPageTitle('ChangePassword', NS_SPECIAL)->getPrefixedDBkey();
        $passResetPageTitlePrefixed = self::getPageTitle('PasswordReset', NS_SPECIAL)->getPrefixedDBkey();

        // Get the callback URL
        $callbackPageTitle = self::getPageTitle(self::SPECIAL_PAGE_NAME);

	    if($requestTitle == $loginPageTitlePrefixed || $requestTitle == $registerPageTitlePrefixed){
            self::handleLogin($callbackPageTitle->getFullURL());
        }elseif($requestTitle == $callbackPageTitle->getPrefixedDBKey()) {
            self::handleCallback($output, $request);
        }elseif($requestTitle == $logoutPageTitlePrefixed) {
            self::handleLogout();
        }elseif($requestTitle == $passResetPageTitlePrefixed || $requestTitle == $passChangePageTitlePrefixed){
            $passwordInfoPage = self::getPageTitle('PasswordChangeOrReset', NS_HELP)->getFullURL();

            header('Location: ' . $passwordInfoPage);
            exit;
        }
	}

	private static function handleLogin( $callbackPageURL ){
        global $wgAmoLoginClientClientID, $wgAmoLoginClientClientSecret, $wgAmoLoginClientRemoteURL;

        if( empty($wgAmoLoginClientClientID) || empty($wgAmoLoginClientClientSecret) )
        {
            die( 'Error! Please alert a developer to set $wgAmoLoginClientID and $wgAmoLoginClientSecret in AMOSettings.php');
        }

        header( 'Location: '.$wgAmoLoginClientRemoteURL.'?client_id=' . $wgAmoLoginClientClientID . '&redirect_id=' . $callbackPageURL . '&response_type=code');
    }

	private static function handleCallback(OutputPage &$output, &$request){
        global $wgServer, $wgScriptPath, $wgAmoLoginClientClientID, $wgAmoLoginClientClientSecret, $wgAmoLoginClientRemoteTokenURL;

	    $code = $_GET['code'];

	    if( empty($code) ){
	        die( 'Error! Please alert a developer and tell them no code was received on callback!' );
        }

        /*
         * The following part was made by Bart Roos (StudioKaa) for: https://github.com/StudioKaa/amoclient/
         */
		$http = new \GuzzleHttp\Client;
		try {
			//Exchange authcode for tokens
		    $response = $http->post( $wgAmoLoginClientRemoteTokenURL, [
		        'form_params' => [
		            'client_id' => $wgAmoLoginClientClientID,
		            'client_secret' => $wgAmoLoginClientClientSecret,
		            'code' => $code,
		            'grant_type' => 'authorization_code'
		        ]
		    ] );
		    //Get id_token from the reponse
		    $tokens = json_decode( (string) $response->getBody() );
			$id_token = (new Parser())->parse((string) $tokens->id_token);
			//Verify id_token
			if( !$id_token->verify(new Sha256(), $wgAmoLoginClientClientSecret) )
			{
				die('Error! Please alert a developer! Token cannot be verified.');
			}
			//Get 'user' claim
			$id_token->getClaims();
			$token_user = $id_token->getClaim('user');
			$token_user = json_decode($token_user);
			//Check if user may login
			if( isset($wgAmoLoginClientTeachersOnly) && $wgAmoLoginClientTeachersOnly === true && $token_user->type != 'teacher' )
			{
                die('Oops: this app is only availble to teacher-accounts');
			}

            $params = [
                'email' => $token_user->email,
                'email_authenticated' => time(),
                'real_name' => $token_user->name,
            ];

            $pass = PasswordFactory::generateRandomPasswordString(128);

            //Create new user, will return null if it already exists
            $user = User::newFromName( $token_user->id );

            // If the user doesn't exist continue setting settings
            if ( $user !== null || $user->getId() == 0 )
            {
                if ( !$user->isLoggedIn() ) {
                    // [New in MW 1.27]
                    // User does not exist,
                    // so we need to add them to the DB before changing fields.
                    $user->addToDatabase();
                }
                if ($token_user->name) {
                    $user->setRealName($token_user->name);
                }

                $user->setPassword($pass);

                if($token_user->email) {
                    $user->setEmail($token_user->email);
                    $user->setEmailAuthenticationTimestamp(time());
                }
                $user->saveSettings();

				//user_groups
                $user->addGroup($token_user->type);
			}

			$user->setPassword($pass);
            $user->setCookies(null, null, true);

            wfDebug( 'User is authorized.' );

            header('Location: '. $wgServer . $wgScriptPath);
		} catch ( \GuzzleHttp\Exception\BadResponseException $e ) {
		    die( 'Unable to retrieve access token: ' . $e->getResponse()->getBody() );
		}
    }

    private static function handleLogout(){

    }
}
