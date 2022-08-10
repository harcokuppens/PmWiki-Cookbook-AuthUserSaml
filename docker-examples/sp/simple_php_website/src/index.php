<?php

require_once(dirname(__FILE__).'/simplesamlphp/lib/_autoload.php');

$as = new \SimpleSAML\Auth\Simple('default-sp');
if (!$as->isAuthenticated()) {
  $url = $as->getLoginURL();
  print('Use simple login script:');
  print('<a href="' . htmlspecialchars($url) . '">Login</a><br><br>');
  print('Or use <a href="/simplesaml/">simplesamlphp client app</a>.');
  #pretty_print($_SESSION);
  exit;
}

$as->requireAuth();

$url = $as->getLogoutURL();
print('<a href="' . htmlspecialchars($url) . '">Logout</a>');

#pretty_print($_SESSION);

$attributes = $as->getAttributes();
pretty_print($attributes);

function pretty_print( $var ) {
  ob_start();
  print_r( $var);
  $output = ob_get_contents();
  ob_end_clean();
  $output = htmlentities($output);
  $output = preg_replace("/\n/","<br>",$output);
  print "<pre>$output</pre>";
}
