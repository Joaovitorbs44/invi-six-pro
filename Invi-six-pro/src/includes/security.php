<?php
declare(strict_types=1);
function cspNonce():string{static $nonce;return $nonce??=base64_encode(random_bytes(18));}
function applicationSecurityHeaders():void{
 $nonce=cspNonce();
 header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
 if(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')header('Strict-Transport-Security: max-age=31536000');
}
