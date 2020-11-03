<?php
/*
Plugin Name:  Referrer Field for Gravity Forms
Description:  A custom field for Gravity Forms that's automatically populated with the referrer URL for the current user
Version:      1.1.2
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'GF_REFERRER_ADDON_VERSION', '1.1.2' );
define( 'REFERRER_COOKIE_NAME', 'referrer_url');
define( 'SOURCE_COOKIE_NAME', 'source_url');
define( 'SOURCE_NAME_SESSION_NAME', 'source_name');

function get_referrer() {
  $referrer_url = '';
  if (!empty($_SERVER['HTTP_REFERER'])) {
    error_log('HTTP_REFERER found.');
    $referrer_url = $_SERVER['HTTP_REFERER'];
  } else if(!empty($_SERVER['X-FORWARDED-FOR'])){
    error_log('X-FORWARDED-FOR found.');
    $referrer_url = $_SERVER['X-FORWARDED-FOR'];
  } else {
    error_log('No HTTP referrer found');
    error_log('HTTP Headers: ');
    error_log(wp_json_encode(getallheaders()));
  }
  return $referrer_url;
}

function set_referrer_cookie() {

  //exclude AJAX calls and admin pages
  if (wp_doing_ajax() || is_admin() || strpos($_SERVER['REQUEST_URI'],'wp-content') !== false) {
    return;
  }

  error_log('Setting cookie --------------------------');

  $current_url =  "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
  error_log('Current URL: '.$current_url);

  if (empty($_COOKIE[REFERRER_COOKIE_NAME])) {

    error_log("No referrer cookie value found. Checking HTTP referrers.");
    $referrer_url = get_referrer();

    if(!empty($referrer_url)) {

      error_log("Referrer found: ".$referrer_url);

      $url_parts = parse_url($referrer_url);
      $referrer_host = $url_parts['host'] . (!empty($url_parts['port']) ? ':'.$url_parts['port'] : '');
      $current_host = $_SERVER['HTTP_HOST'];

      //exclude cron job calls
      if (strtolower($url_parts['path']) === 'wp-cron.php') {
        error_log("Cookie not set: Cron job request");
        return;
      }

      error_log("is current host: ". var_export(strtolower($referrer_host) === strtolower($current_host), true));
      error_log("is localhost: ". var_export(strtolower($url_parts['host']) === 'localhost', true));
      error_log("is admin: ". var_export(is_admin(), true));
      error_log("is empty: ". var_export(empty($referrer_url), true));

      if (strtolower($referrer_host) !== strtolower($current_host)) {
        if (strtolower($url_parts['host']) !== 'localhost') {
          error_log("Setting value to referrer cookie: ".$referrer_url);
          setcookie( REFERRER_COOKIE_NAME, $referrer_url, time() + 30 * DAY_IN_SECONDS, "/", null );
          var_dump($referrer_url);
        } else {
          error_log("Cookie not set: Request is from localhost");
        }
      } else {
        error_log("Cookie not set: Request is from the current host");
      }
    } else {
      error_log("Cookie not set: Referrer value is empty");
    }
  }

}

// add_action( 'init', 'set_source_cookie' );
function set_source_cookie() {

  set_referrer_cookie();
  if (!isset($_COOKIE[SOURCE_COOKIE_NAME])) {

    $host = $_SERVER['HTTP_HOST'];
    $source = $host . $_SERVER['REQUEST_URI'];

    //exclude cron job calls
    if (substr_compare($source, $host . '/wp-cron.php', strlen($host), 12, true) == 0) {
      error_log("Cookie not set: Cron job request");
      return;
    }

    error_log("Setting value to source cookie: ".$source);
    setcookie( SOURCE_COOKIE_NAME, $source, time() + 30 * DAY_IN_SECONDS, '/; SameSite=strict' );
    var_dump($source);

  }

  // var_dump($_COOKIE[SOURCE_NAME_SESSION_NAME]);
  // var_dump($_COOKIE[SOURCE_COOKIE_NAME]);
  // var_dump($_COOKIE[REFERRER_COOKIE_NAME]);
}

// add_action( 'wp', 'set_source_name' );
// function set_source_name() {
//   $id = get_the_ID();
//   if ($id) {
//     $custom = get_post_custom($id);
//     $source_name = $custom["source_name"];
//     if ($source_name && source_name[0]) {
//       $source_name = $source_name[0];
//       var_dump($source_name);
//       error_log("SOURCE NAME CUSTOM FIELD: " . $source_name);
//       setcookie( SOURCE_NAME_SESSION_NAME, $source_name, time() + 30 * DAY_IN_SECONDS, '/; SameSite=strict' );
//     }
//   }
// }


add_action( 'gform_loaded', array( 'GF_Referrer_AddOn_Bootstrap', 'load' ), 5 );
class GF_Referrer_AddOn_Bootstrap {
  public static function load() {
    if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
        return;
    }
    require_once( 'class-gfreferrer-addon.php' );
    GFAddOn::register( 'GFReferrerAddOn' );
  }
}

add_action ( 'wp_head', 'gf_referrer_head' );
function gf_referrer_head() {
  $id = get_the_ID();
  $custom = get_post_custom($id);
  $source_name = $custom["source_name"];
  if ($source_name && source_name[0]) {
    $source_name = $source_name[0];
    $source_name_escaped = htmlspecialchars($source_name);
    echo "
      <meta id=\"source_name\" value=\"$source_name_escaped\" />
    ";
    error_log("SOURCE NAME CUSTOM FIELD: " . $source_name_escaped);
  }

  // $host = $_SERVER['HTTP_HOST'];
  // $source = $host . $_SERVER['REQUEST_URI'];
  // if ($source) {
  //   $source_escaped = htmlspecialchars($source);
  //   echo "
  //     <meta name=\"source\" value=\"$source_escaped\" />
  //   "
  // }

  $SOURCE_COOKIE_NAME = SOURCE_COOKIE_NAME;
  $SOURCE_NAME_SESSION_NAME = SOURCE_NAME_SESSION_NAME;
  $REFERRER_COOKIE_NAME = REFERRER_COOKIE_NAME;

  echo "
  <script>

    function getCookie(cname) {
      var name = cname + \"=\";
      var decodedCookie = decodeURIComponent(document.cookie);
      var ca = decodedCookie.split(';');
      for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
          c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
          return c.substring(name.length, c.length);
        }
      }
      return \"\";
    }

    function setCookie(cname, cvalue, exdays) {
      var d = new Date();
      d.setTime(d.getTime() + (exdays*24*60*60*1000));
      var expires = \"expires=\"+ d.toUTCString();
      document.cookie = cname + \"=\" + cvalue + \";\" + expires + \";path=/\";
    }


    const source_name = document.getElementById('source_name');
    if (source_name) {

      const source_name_val = decodeURI(source_name.getAttribute('value'));
      // console.log(source_name_val);
      const existing_source_name_val = getCookie('$SOURCE_NAME_SESSION_NAME');
      if (existing_source_name_val == '') {
        setCookie('$SOURCE_NAME_SESSION_NAME', source_name_val, 180);
      }
    }


    const existing_source = getCookie('$SOURCE_COOKIE_NAME');
    if (existing_source == '') {
      const source = window.location.href;
      setCookie('$SOURCE_COOKIE_NAME', source, 180);
    }

    const existing_ref = getCookie('$REFERRER_COOKIE_NAME');
    if (existing_ref == '') {
      const referrer = document.referrer;
      setCookie('$REFERRER_COOKIE_NAME', referrer, 180);
    }
    
  </script>
  
  ";


}

?>
