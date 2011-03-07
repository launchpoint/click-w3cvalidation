<?


global $css_errors;
$key = md5($path);
$fpath = $this_module_cache_fpath."/$key";
if(file_exists($fpath))
{
  $src = stat($fpath);
  $dst = stat($path);
  if ($src['mtime'] < $dst['mtime']) unlink($fpath);
}
if(!file_exists($fpath))
{
  $text = file_get_contents($path);
  $errCount = cssValid($text);
  if ($errCount>0)
  {
    $vpath = ftov($path);
    $link = '<a href="/css-validator/validator?output=xhtml&profile=css21&usermedium=all&warning=1&lang=en&text='.urlencode($text).'" target="_blank">'.$vpath.'</a>' . " ($errCount)";
    $css_errors[] = $link;
  } else {
    touch($fpath);
  }
}
