<?php
cors();
/**
 * AJAX Cross Domain (PHP) Proxy 0.8
 *    by Iacovos Constantinou (http://www.iacons.net)
 * 
 * Released under CC-GNU GPL
 */

/**
 * Enables or disables filtering for cross domain requests.
 * Recommended value: true
 */
define( 'CSAJAX_FILTERS', true );

/**
 * If set to true, $valid_requests should hold only domains i.e. a.example.com, b.example.com, usethisdomain.com
 * If set to false, $valid_requests should hold the whole URL ( without the parameters ) i.e. http://example.com/this/is/long/url/
 * Recommended value: false (for security reasons - do not forget that anyone can access your proxy)
 */
define( 'CSAJAX_FILTER_DOMAIN', false );

/**
 * Set debugging to true to receive additional messages - really helpful on development
 */
define( 'CSAJAX_DEBUG', false );

/**
 * A set of valid cross domain requests
 */
$valid_requests = array(
  'api-v2launch.trakt.tv'
);

/* * * STOP EDITING HERE UNLESS YOU KNOW WHAT YOU ARE DOING * * */

// identify request headers
$request_headers = array('Content-Type: application/json');
$request_headers = array('Content-Type: application/json');
foreach ( $_SERVER as $key => $value ) {
  if ( substr( $key, 0, 5 ) == 'HTTP_' ) {
    $headername = str_replace( '_', ' ', substr( $key, 5 ) );
    $headername = str_replace( ' ', '-',  strtolower( $headername ) );
    if ( in_array( $headername, array( 'trakt-api-version', 'trakt-api-key', 'trakt-authorization' ) ) ) {
      if($headername == 'trakt-authorization') {
        $request_headers[] = "Authorization: $value";
      } else {
        $request_headers[] = "$headername: $value";
      }
    }
  }
}

// identify request method, url and params
$request_method = $_SERVER['REQUEST_METHOD'];
if ( 'GET' == $request_method ) {
  $request_params = $_GET;
} elseif ( 'POST' == $request_method ) {
  $request_params = $_POST;
  if ( empty( $request_params ) ) {
    $data = file_get_contents( 'php://input' );
    if ( !empty( $data ) ) {
      $request_params = $data;
    }
  }
} elseif ( 'OPTIONS' == $request_method || 'PUT' == $request_method || 'DELETE' == $request_method ) {
  $request_params = file_get_contents( 'php://input' );
} else {
  $request_params = null;
}

// Get URL from `csurl` in GET or POST data, before falling back to X-Proxy-URL header.
if ( isset( $_REQUEST['csurl'] ) ) {
    $request_url = urldecode( $_REQUEST['csurl'] );
} else if ( isset( $_SERVER['HTTP_X_PROXY_URL'] ) ) {
    $request_url = urldecode( $_SERVER['HTTP_X_PROXY_URL'] );
} else if('OPTIONS' == $request_method) {
    header( $_SERVER['SERVER_PROTOCOL'] . ' 204 No Content');
    header("Status: 204 No Content");
    $_SERVER['REDIRECT_STATUS'] = 204;
    exit;
} else {
    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    header( 'Status: 404 Not Found' );
    $_SERVER['REDIRECT_STATUS'] = 404;
    exit;
}

$p_request_url = parse_url( $request_url );

// csurl may exist in GET request methods
if ( is_array( $request_params ) && array_key_exists('csurl', $request_params ) )
  unset( $request_params['csurl'] );

// ignore requests for proxy :)
if ( preg_match( '!' . $_SERVER['SCRIPT_NAME'] . '!', $request_url ) || empty( $request_url ) || count( $p_request_url ) == 1 ) {
  csajax_debug_message( 'Invalid request - make sure that csurl variable is not empty' );
  exit;
}

// check against valid requests
if ( CSAJAX_FILTERS ) {
  $parsed = $p_request_url;
  if ( CSAJAX_FILTER_DOMAIN ) {
    if ( !in_array( $parsed['host'], $valid_requests ) ) {
      csajax_debug_message( 'Invalid domain - ' . $parsed['host'] . ' does not included in valid requests' );
      exit;
    }
  } else {
    $check_url .= isset( $parsed['host'] ) ? $parsed['host'] : '';
    if ( !in_array( $check_url, $valid_requests ) ) {
      csajax_debug_message( 'Invalid domain - ' . $request_url . ' does not included in valid requests' );
      exit;
    }
  }
}

// append query string for GET requests
if ( $request_method == 'GET' && count( $request_params ) > 0 && (!array_key_exists( 'query', $p_request_url ) || empty( $p_request_url['query'] ) ) ) {
  $request_url .= '?' . http_build_query( $request_params );
}

// let the request begin
$ch = curl_init( $request_url );
curl_setopt( $ch, CURLOPT_HTTPHEADER, $request_headers );   // (re-)send headers
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );  // return response
curl_setopt( $ch, CURLOPT_HEADER, true );    // enabled response headers
curl_setopt( $ch, CURLOPT_VERBOSE, 1);
// add data for POST, PUT or DELETE requests
if ( 'POST' == $request_method ) {
  $post_data = is_array( $request_params ) ? http_build_query( $request_params ) : $request_params;
  curl_setopt( $ch, CURLOPT_POST, true );
  curl_setopt( $ch, CURLOPT_POSTFIELDS,  $post_data );
} elseif ( 'PUT' == $request_method || 'DELETE' == $request_method ) {
  curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_method );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $request_params );
}

// retrieve response (headers and content)
$response = curl_exec( $ch );
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close( $ch );

// finally, output the content
print( $body );

function csajax_debug_message( $message )
{
  if ( true == CSAJAX_DEBUG ) {
    print $message . PHP_EOL;
  }
}

function cors () {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: Authorization, Content-Type, trakt-api-version, trakt-api-key, trakt-authorization,X-Proxy-URL");
}

?>