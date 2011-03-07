<?

function file_post_contents($url_path, $post_data='', $preserve_response_headers=false) {
    $url = parse_url($url_path);

    if (!isset($url['port'])) {
      if ($url['scheme'] == 'http') { $url['port']=80; }
      elseif ($url['scheme'] == 'https') { $url['port']=443; }
    }
    $url['query']=isset($url['query'])?$url['query']:'';

    $url['protocol']=$url['scheme'].'://';
    $eol="\r\n";

    $headers =  "POST ".$url_path." HTTP/1.0".$eol.
                "Host: ".$url['host'].$eol.
                "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
                "Content-Type: application/x-www-form-urlencoded".$eol.
                "Content-Length: ".strlen($post_data).$eol.
                $eol.$post_data;
    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
    if($fp) {
      fputs($fp, $headers);
      $result = '';
      while(!feof($fp)) { $result .= fgets($fp, 128); }
      fclose($fp);
      if (!$preserve_response_headers) {
        //removes headers
        $pattern="/^.*\r\n\r\n/s";
        $result=preg_replace($pattern,'',$result);
      }
      return $result;
    }
}

function xhtmlValid($text) 
{
  if (strlen(trim($text))==0) return array();
  $validator_output = file_post_contents("http://localhost/w3c-markup-validator/check", "output=soap12&fragment=".urlencode($text));
  $domDoc = new DOMDocument();
  $domDoc->loadXML($validator_output);
  $xpathString = "//*/m:error/m:message";
  $domErrors = getNodes($domDoc, $xpathString, array('m'=>'http://www.w3.org/2005/10/markup-validator'));
  $ignores = array(
    'attribute "target"',
    'attribute "alt"',
    'attribute "action"',
    'attribute "rows"',
    'attribute "cols"'
    
    );
  $domErrors = filter_errors($domErrors, $ignores);
  $xpathString = "//*/m:warning/m:message";
  $domWarnings = getNodes($domDoc, $xpathString, array('m'=>'http://www.w3.org/2005/10/markup-validator'));
  return count($domWarnings)+count($domErrors);
}


function getNodes($domDoc, $xpathString, $namespaces)
{
  $xp = new DOMXPath($domDoc);
  $xp->registerNamespace('x', 'http://www.w3.org/1999/xhtml');
  $xp->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
  $xp->registerNamespace('i18n', 'http://apache.org/cocoon/i18n/2.1');
  $xp->registerNamespace('env', 'http://www.w3.org/2003/05/soap-envelope');
  foreach($namespaces as $k=>$v)
  {
    $xp->registerNamespace($k,$v);
  }
  
  
  $ret = array();
  $nodes = $xp->query($xpathString);
  foreach ($nodes as $node) {
      array_push($ret, $node);
  }
  return $ret;
}



function filter_errors($domList, $ignores)
{
  foreach($ignores as $k=>$v)
  {
    $ignores[$k] = preg_quote($v);
  }
  $ignores = join("|",$ignores);
  $domErrors = array();
  foreach($domList as $elem)
  {
    if (preg_match("/$ignores/mis", $elem->textContent)==0)
    {
      $domErrors[] = $elem->textContent;
    }
  }
  return $domErrors;
}
    
function cssValid($text)
{
  $ch = curl_init();
  $data = array('output'=>'soap12','profile'=>'css21','usermedium'=>'all','warning'=>'1','text'=>$text);
  curl_setopt($ch, CURLOPT_URL, 'http://localhost/css-validator/validator');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $validator_output = curl_exec($ch);
  curl_close($ch);
  $domDoc = new DOMDocument();
  $domDoc->loadXML($validator_output);
  $xpathString = "//*/m:error";
  $domList = getNodes($domDoc, $xpathString, array('m'=>'http://www.w3.org/2005/07/css-validator'));
  $ignores = array(
    "-moz",
    "-webkit",
    "opacity",
    "zoom",
    "mask",
    'DXImageTransform',
    'attempt to find a semi-colon before the property name. add it'
  );
  $domErrors = filter_errors($domList, $ignores);
  return count($domErrors);
}