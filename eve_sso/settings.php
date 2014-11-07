<?php
require_once( "common/admin/admin_menu.php" );


if(isset($_POST['submit']))
{
  config::set('eve_sso_client_id',$_POST['client_id']);
  config::set('eve_sso_secret',$_POST['secret']);
  config::set('eve_sso_owners_only',($_POST['owners_only']) ? 1 : 0);
  $confirm = "<strong>Settings Saved</strong><br/>";
}

$page = new Page( "Settings - EVE SSO" );
$html .= $confirm;

$html .='<form action="" method="post">';
$html .= "<table class=kb-subtable>";
$html .= "<tr><td colspan=\"4\"><div class=block-header2>SSO options</div></td></tr>";
$html .= "<tr><td><b>EVE SSO Client id:</b></td><td><input type='text' size='45' name='client_id' value='".config::get('eve_sso_client_id')."'/></td></tr>";
$html .= "<tr><td><b>EVE SSO Client secret:</b></td><td><input type='text' size='45' name='secret' value='".config::get('eve_sso_secret')."'/></td></tr>";

$html .= "<tr><td><b>Only allow killboard corp/alliance members to post:</b></td><td><input type='checkbox' name='owners_only'";
if (config::get('eve_sso_owners_only'))
{
    $html .= " checked=\"checked\"";
}
$html .= "</td></tr>";

$html .= "<tr><td colspan=\"4\">&nbsp;</td></tr>";
$html .= "<tr></tr><tr><td></td><td colspan=3 ><input type=submit name=submit value=\"Save\"></td></tr>";
$html .= "<tr><td colspan=\"4\">&nbsp;</td></tr>";
$html .= "</table>";
$html .= "</form><br/>";

$html .="Your EVE SSO callback URL should be set to ".edkURI::page('sso_post');


$page->setContent( $html );
$page->addContext( $menubox->generate() );
$page->generate();
