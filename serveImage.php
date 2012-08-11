<?

$resolutions   = array(1382, 992, 768, 480);
$filename=$_SERVER['DOCUMENT_ROOT']."/Adaptive-Images/01.png";
$filename=$source_file;
$browser_cache = 60*60*24*7; // How long the BROWSER cache should last (seconds, minutes, hours, days. 7days by default)
$requested_uri  = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
  $document_root  = $_SERVER['DOCUMENT_ROOT'];
/* helper function: Send headers and returns an image. */
function sendImage($filename, $browser_cache) {
  $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  if (in_array($extension, array('png', 'gif', 'jpeg'))) {
    header("Content-Type: image/".$extension);
  } else {
    header("Content-Type: image/jpeg");
  }
  //header("Cache-Control: private, max-age=".$browser_cache);
  //header('Expires: '.gmdate('D, d M Y H:i:s', time()+$browser_cache).' GMT');
  header('Content-Length: '.filesize($filename));
  readfile($filename);
  exit();
}


/* Check to see if a valid cookie exists */
if (isset($_COOKIE['resolution'])) {
  $cookie_value = $_COOKIE['resolution'];

  // does the cookie look valid? [whole number, comma, potential floating number]
  if (! preg_match("/^[0-9]+[,]*[0-9\.]+$/", "$cookie_value")) { // no it doesn't look valid
    setcookie("resolution", "$cookie_value", time()-100); // delete the mangled cookie
  }
  else { // the cookie is valid, do stuff with it
    $cookie_data   = explode(",", $_COOKIE['resolution']);
    $client_width  = (int) $cookie_data[0]; // the base resolution (CSS pixels)
    $total_width   = $client_width;
    $pixel_density = 1; // set a default, used for non-retina style JS snippet
    if (@$cookie_data[1]) { // the device's pixel density factor (physical pixels per CSS pixel)
      $pixel_density = $cookie_data[1];
    }

    rsort($resolutions); // make sure the supplied break-points are in reverse size order
    $resolution = $resolutions[0]; // by default use the largest supported break-point

    // if pixel density is not 1, then we need to be smart about adapting and fitting into the defined breakpoints
    if($pixel_density != 1) {
      $total_width = $client_width * $pixel_density; // required physical pixel width of the image

      // the required image width is bigger than any existing value in $resolutions
      if($total_width > $resolutions[0]){
        // firstly, fit the CSS size into a break point ignoring the multiplier
        foreach ($resolutions as $break_point) { // filter down
          if ($total_width <= $break_point) {
            $resolution = $break_point;
          }
        }
        // now apply the multiplier
        $resolution = $resolution * $pixel_density;
      }
      // the required image fits into the existing breakpoints in $resolutions
      else {
        foreach ($resolutions as $break_point) { // filter down
          if ($total_width <= $break_point) {
            $resolution = $break_point;
          }
        }
      }
    }
    else { // pixel density is 1, just fit it into one of the breakpoints
      foreach ($resolutions as $break_point) { // filter down
        if ($total_width <= $break_point) {
          $resolution = $break_point;
        }
      }
    }
  }
}

/* No resolution was found (no cookie or invalid cookie) */
if (!$resolution) {
  // We send the lowest resolution for mobile-first approach, and highest otherwise
  $resolution = $is_mobile ? min($resolutions) : max($resolutions);
}

/* if the requested URL starts with a slash, remove the slash */
if(substr($requested_uri, 0,1) == "/") {
  $requested_uri = substr($requested_uri, 1);
}


/* whew might the cache file be? */
//$cache_file = $document_root."/$cache_path/$resolution/".$requested_uri;

if($resolution==480){
$cache_file = $document_root."/small".$requested_uri;	
}
else{
$cache_file = $document_root.'/'.$requested_uri;	
}

/* Use the resolution value as a path variable and check to see if an image of the same name exists at that path */
if (file_exists($cache_file)) { // it exists cached at that size
  /*if ($watch_cache) { // if cache watching is enabled, compare cache and source modified dates to ensure the cache isn't stale
    //$cache_file = refreshCache($source_file, $cache_file, $resolution);
  }*/

  sendImage($cache_file, $browser_cache);
}
//echo json_encode($requested_uri);

// check if the file exists at all
if (!file_exists($source_file)) {
  header("Status: 404 Not Found-".$requested_uri.'-'.$cache_file);
  //exit();

}
?>