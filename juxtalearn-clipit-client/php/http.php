<?php  #if (!defined( 'BASEPATH' )) exit( 'No direct script access allowed' );
/**
 * Generic HTTP library.
 * (Clip-It API client library for JuxtaLearn.)
 * NDF, 13 March 2014.
 *
 * @copyright 2014 The Open University.
 */

/**WAS: Dummy CodeIgniter object. */


/**
 * HTTP request library.
 * Code from base_service.php, using cURL.
 *
 * @copyright Copyright 2011 The Open University.
 * @author N.D.Freear, 6 March 2012.
 */
class Http {

  protected $CI;

  public function __construct() {
    // CodeIgniter compatibility.
    if (function_exists('get_instance')) {
      $this->CI =& get_instance();
    }
  }

  protected function base_url() { /*return base_url();*/ }
  protected function _debug($s) { /*$this->CI->debug();*/ }
  protected function _error($s) { /*$this->CI->_error();*/ }
  protected function config($s) { /*$this->CI->.config->.item()*/ }
  protected function cookie($s) { /*$this->CI->.input->cookie()*/ }


  public function request($url, $spoof=TRUE, $options=array()) {
    $result = $this->_prepare_request($url, $spoof, $options);

    return $this->_http_request_curl($url, $spoof, $options, $result);
  }


  /** Prepare the HTTP request.
  */
#http://api.drupal.org/api/drupal/core%21includes%21common.inc/function/drupal_http_request/8
  protected function _prepare_request($url, $spoof, &$options) {
	$result = new stdClass();

    // Parse the URL and make sure we can handle the schema.
    $uri = @parse_url($url);

    if ($uri == FALSE) {
      $result->error = 'unable to parse URL';
      $result->code = -1001;
      return $result;
    }

    if (!isset($uri['scheme'])) {
      $result->error = 'missing schema';
      $result->code = -1002;
      return $result;
    }

    #timer_start(__FUNCTION__);


    // Bug #1334, Proxy mode to fix VLE caption redirects (Timedtext controller).
    if (isset($options['proxy_cookies'])) {
      $cookie_names =  $this->config('httplib_proxy_cookies');
      if (! is_array($cookie_names)) {
        $this->CI->_error('Array expected for $config[httplib_proxy_cookies]', 400);
      }

      $cookies = '';
      foreach ($cookie_names as $cname) {
        $cookies .= "$cname=". $this->cookie($cname) .'; ';
      }
      $options['cookie'] = rtrim($cookies, '; ');
    }


    $ua = 'OU Player/0.9 (PHP/cURL)';
    if ($spoof) {
       // Updated, April 2012.
       $ua = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19";
       #$ua="Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-GB; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3";
    }

    // Merge the default options.
    $options += array(
      'proxy' => NULL, #$this->config('http_proxy'),
      'headers' => array(),
      'method' => 'GET',
      'data' => NULL,
      'max_redirects' => 2,  #3,
      'timeout' => 5,  #15, 30.0 seconds,
      #'context' => NULL,

      'cookie' => NULL,
      'ua' => $ua,
      'debug' => FALSE,
      'auth' => NULL, #'[domain\]user:password'
      'ssl_verify' => TRUE,

      #'method' => 'GET',
    );
    if (1 == $options['proxy']) {
      $options['proxy'] = $this->config('http_proxy');
    }

    return $result;
  }


  /** Perform the HTTP request using cURL.
  */
  protected function _http_request_curl($url, $spoof, $options, $result) {
    if (!function_exists('curl_init'))  die('Error, cURL is required.');

    $this->_debug($options);

    $h_curl = curl_init($url);

    if ('GET' !== $options['method']) {
      curl_setopt($h_curl, CURLOPT_CUSTOMREQUEST, $options['method']); 
    }

    if (is_array($options['data'])) {
      curl_setopt($h_curl, CURLOPT_POSTFIELDS, http_build_query($options['data']));
    }
    elseif (is_string($options['data'])) {
      curl_setopt($h_curl, CURLOPT_POSTFIELDS, $options['data']);
    }

    curl_setopt($h_curl, CURLOPT_USERAGENT, $options['ua']);
    if (!$spoof) {
      curl_setopt($h_curl, CURLOPT_REFERER, $this->base_url());
    }

    /*if ($options['cookie']) {
		curl_setopt($h_curl, CURLOPT_COOKIE, $options['cookie']);
		header('X-Proxy-Cookie: '.$options['cookie']);
    }*/

    if (! $options['ssl_verify']) {
      curl_setopt($h_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($h_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    curl_setopt($h_curl, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($h_curl, CURLOPT_MAXREDIRS, $options['max_redirects']);
    curl_setopt($h_curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($h_curl, CURLOPT_TIMEOUT, $options['timeout']);
    curl_setopt($h_curl, CURLOPT_CONNECTTIMEOUT, $options['timeout']);

    if ($options['debug']) {
      curl_setopt($h_curl, CURLOPT_HEADER, TRUE);
      curl_setopt($h_curl, CURLINFO_HEADER_OUT, TRUE);
    }

    if ($options['auth']) {
      //TODO: http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
      curl_setopt($h_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($h_curl, CURLOPT_SSL_VERIFYHOST, FALSE);

      curl_setopt($h_curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
      curl_setopt($h_curl, CURLOPT_USERPWD, $options['auth']);
    }

	$http_proxy = $options['proxy']; #$this->config('http_proxy');
	if ($http_proxy) {
	  curl_setopt($h_curl, CURLOPT_PROXY, $http_proxy);
	}
    curl_setopt($h_curl, CURLOPT_RETURNTRANSFER, TRUE);

    $result->data = curl_exec($h_curl);
    $result->http_code = FALSE;

    $result->_headers = NULL;
    // Fragile: rely on cURL always putting 'Content-Length' last..
    if ($options['debug'] && preg_match('#^(HTTP\/1\..+Content\-Length: \d+\s)(.*)$#ms', $result->data, $matches)) {
      $result->_headers = $matches[1];
      $result->data = trim($matches[2], "\r\n");
    }
    if ($errno = curl_errno($h_curl)) {
      //Error. Quietly log?
      $this->CI->_log('error', "cURL $errno, ".curl_error($h_curl)." GET $url");
      #$this->CI->firephp->fb("cURL $errno", "cURL error", "ERROR");
      $result->http_code = "500." . $errno;
      $result->curl_errno = $errno;
      $result->curl_error = curl_error($h_curl);
      $result->success = FALSE;
    }
    $result->info = curl_getinfo($h_curl);
    if (!$result->http_code) {
      $result->success = ($result->info['http_code'] < 300);
      $result->http_code = $result->info['http_code'];
    }

    curl_close($h_curl);

    return (object) $result;
  }
  
}
