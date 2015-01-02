<?php

$modInfo['eve_sso']['name'] = "EVE SSO Authentication by Two step";
$modInfo['eve_sso']['abstract'] = "Allows people to comment on killmails using their EVE login.";
$modInfo['eve_sso']['about'] = "Open source, get the <a href='https://github.com/jgoldshlag/edk_eve_sso'>source here</a>.";

event::register('get_tpl', 'EVE_SSO::get_template');
event::register("killDetail_assembling", "EVE_SSO::kill_detail");

class EVE_SSO
{
  function get_template(&$template_name)
  {
    if ($template_name == "block_comments")
      $template_name = "../../../mods/eve_sso/block_comments.tpl";
    
    return $template_name;
  }
  
  function kill_detail(&$object)
  {
    session::create(session::isAdmin());
    
    if(isset($_POST['eve_sso']))
    {
	  if(trim($_POST['comment']) == '')
	  {
	    $object->commenthtml = 'Error: The silent type, hey? Good for you, bad for a comment.';
		return;
	  }
      $_SESSION['state_val'] = rand();
      $_SESSION['sso_kill_id'] = edkURI::getArg('kll_id', 1);
      $_SESSION['sso_comment'] = $_POST['comment'];
      $_SESSION['sso_redir_url'] = $_SERVER['REQUEST_URI'];
      $sso_url = "https://login.eveonline.com/oauth/authorize/?response_type=code&redirect_uri=".urlencode(edkURI::page('sso_post'))."&client_id=".config::get('eve_sso_client_id')."&scope=&state=".$_SESSION['state_val'];
      header('Location: '.$sso_url, true, 302);
      die();
    }
  }
}
