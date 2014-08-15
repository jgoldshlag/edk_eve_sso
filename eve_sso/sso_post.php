<?php

session::create(session::isAdmin());

function query($url, $http_method, array $params = null, array $headers = null)
{
  //curl magic
  $ch = curl_init();
  
  //curl_setopt($ch, CURLOPT_VERBOSE, true);
  //$verbose = fopen('php://temp', 'rw+');
  //curl_setopt($ch, CURLOPT_STDERR, $verbose);
  
  if ($params)
  {
    if ($http_method == "POST")
    {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
    }
    else
    {
      $url .= '?' . http_build_query($params, null, '&');
    }
  }
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_TIMEOUT, 600);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
  if ($headers)
  {
    $header = array();
    foreach($headers as $key => $parsed_urlvalue) {
      $header[] = "$key: $parsed_urlvalue";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  }
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'readHeader'));
  curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
  $result = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  if ($curl_error = curl_error($ch)) {
    throw new Exception($curl_error, Exception::CURL_ERROR);
  } else {
    $json_decode = json_decode($result, true);
  }
  
  //rewind($verbose);
  //$verboseLog = stream_get_contents($verbose);
  //echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
  
  curl_close($ch);
  return array(
    'result' => (null === $json_decode) ? $result : $json_decode,
    'code' => $http_code,
    'content_type' => $content_type
  );
}

function showError($msg, $when)
{
  $page = new Page( "EVE SSO Error" );
  $html .= "<H1>Error with EVE SSO Plugin $when</H1>";
  $html .= "The following error was returned from the EVE SSO service:<br/>";
  $html .= $msg;
  $html .= "<br/>Click <a href='".$_SESSION['sso_redir_url']."'>here</a> to return to the kill<br/>";
  $page->setContent( $html );
  $page->generate();
  die();
}

if ($_GET['state'] != $_SESSION['state_val'])
{
  showError("invalid state, do you have cookies off?", "on callback");
}

$http_headers = array();
$http_headers['Authorization'] = 'Basic ' . base64_encode(config::get('eve_sso_client_id') . ':' . config::get('eve_sso_secret'));
$http_headers['User-Agent'] = 'EDK Killboard SSO Comments 1.0';
$results = query('https://sisilogin.testeveonline.com/oauth/token', 'POST', array('grant_type' => 'authorization_code', 'code' => $_GET['code']), $http_headers);

if ($results['result']['error'])
{
  showError($results['result']['error_description'], "while getting token");
}

$char_info = query('https://sisilogin.testeveonline.com/oauth/verify', 'GET', null, array('Authorization' => 'Bearer ' . $results['result']['access_token'], 'User-Agent' => 'EDK Killboard SSO Comments 1.0'));
if ($char_info['result']['error'])
{
  showError($char_info['result']['error_description'], "while verifying token");
}

if (config::get('eve_sso_owners_only'))
{
  $sso_char_id = $char_info['result']['CharacterID'];
  $sso_char = new Pilot(0, $sso_char_id);
  $corp = $sso_char->getCorp();
  
  $ok = false;
  if (count(config::get('cfg_pilotid')) > 0)
  {
    if (in_array($sso_char->getID(), config::get('cfg_pilotid')))
      $ok = true;
  }
  if ($corp && count(config::get('cfg_corpid')) > 0)
  {
    if (in_array($corp->getID(), config::get('cfg_corpid')))
      $ok = true;
  }
  if ($corp && count(config::get('cfg_allianceid')) > 0)
  {
    $alliance = $corp->getAlliance();
    if ($alliance && in_array($alliance->getID(), config::get('cfg_allianceid')))
      $ok = true;
  }
  
  if (!$ok)
  {
    $page = new Page( "Access Denied" );
    $html .= "<H1>This killboard only allows posting from members of the owning corporation or alliance</H1>";
    $html .= "Click <a href='".$_SESSION['sso_redir_url']."'>here</a> to return to the kill<br/>";
    $html .= print_r($results, TRUE);
    $page->setContent( $html );
    $page->generate();
    die();
  }
}

$_SESSION['eve_sso_character'] = $char_info['result']['CharacterName'];

if (isset($_SESSION['sso_kill_id']))
{
  $comments = new Comments($_SESSION['sso_kill_id']);

  $comments->addComment($_SESSION['eve_sso_character'], $_SESSION['sso_comment']);
  if(config::get('cache_enabled')) cache::deleteCache();

  header('Location: '.$_SESSION['sso_redir_url'],TRUE,302);
  die();
}
else
{
  showError("missing killid", "after authentication");
}

