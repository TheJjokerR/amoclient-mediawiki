<?php
/**
 * Created by PhpStorm.
 * User: timlu
 * Date: 18/11/2017
 * Time: 12:22
 */

class AmoLoginCallback extends SpecialPage {
    function __construct() {
        parent::__construct( 'AmoLoginCallback' );
    }

    function execute( $par ) {
        $output = $this->getOutput();

        $output->addWikiText( "Please wait, you are being redirected." );
    }
}