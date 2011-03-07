<?

if(!startswith($request_path, 'api/'))
{
  global $xhtml_errors;
  
  $key = md5($rendered_page);
  $fpath = $this_module_cache_fpath."/$key";
  if(!file_exists($fpath))
  {
    $errCount = xhtmlValid($rendered_page);
    if ($errCount>0)
    {
      $link = '<form target="_blank" id="xhtml_errors" action="/w3c-markup-validator/check" method="post"><input type="hidden" name="fragment" value="'.htmlentities($rendered_page).'"/><a href="#" onclick="$(\'#xhtml_errors\').submit();return true;" >XHTML Errors</a>' . " ($errCount) </form>";
      $xhtml_errors[] = $link;
    } else {
      touch($fpath);
    }
  }
}
