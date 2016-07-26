<?php
require '__DIR__/../vendor/aws/aws-autoloader.php';

function invokeLambda($lambdaRegion, $lambdaFunction, $lambdaParameters = '{}', $lambdaQualifier = '$LATEST') {
  try {
    static $hasRun = false;
    if ($hasRun) {
      error_log('invokeLambda() called more than once per request');
      return;
    } else {
      $hasRun = true;
    }

    $dummyHeader = 'X-HTTP-Function-Protocol: https://github.com/digital-sailors/http-function-protocol';

    if (strlen($lambdaParameters) === 0) {
      $lambdaParameters = '{}';
    }

    if (strlen($lambdaQualifier) === 0) {
      $lambdaParameters = '{}';
    }

    $sdk = new Aws\Sdk();

    $client = $sdk->createLambda([
        'region'   => $lambdaRegion,
        'version'  => 'latest'
    ]);

    # check function name
    if (strlen($lambdaFunction) === 0) {
      header($dummyHeader, true, 500);
      echo 'Error 500: Missing function name.';
      return;
    }

    # check parameters
    json_decode($lambdaParameters);
    if (json_last_error() != JSON_ERROR_NONE) {
      header($dummyHeader, true, 500);
      echo 'Error 500: Parameters is not a valid JSON-String.';
      return;
    }

    # normalize headers
    $headers = array_filter($_SERVER, function($key) { return strpos($key, 'HTTP_', 0) === 0; }, ARRAY_FILTER_USE_KEY);
    $headers = array_change_key_case($headers, CASE_LOWER);

    foreach ($headers as $name => $value) {
      $headers[str_replace('_', '-', substr($name, 5))] = $value;
      unset($headers[$name]);
    }

    $payload = array(
      'method' => $_SERVER['REQUEST_METHOD'],
      'scheme' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http'),
      'host' => $_SERVER['HTTP_HOST'],
      'url' => $_SERVER['REQUEST_URI'],
      'httpVersion' => explode('/', $_SERVER['SERVER_PROTOCOL'])[1], # see https://tools.ietf.org/html/rfc7230#section-2.6
      'parameters' => $lambdaParameters,
      'headers' => $headers,
      'body' => file_get_contents('php://input')
    );
    # echo '<p>JSON Payload:<br>', json_encode($payload), '</p>';


    $result = $client->invoke([
        'FunctionName' => $lambdaFunction,
        'InvocationType' => 'RequestResponse',
        'Payload' => json_encode($payload),
        'Qualifier' => $lambdaQualifier,
    ]);

    $payload = json_decode($result->get('Payload'), true);

    $functionError = $result->get('FunctionError');
    if ($functionError == 'Handled' || $functionError == 'Unhandled') {
      header($dummyHeader, true, 500);
      echo $payload['errorMessage'];
      return;
    }

    $status = ((isset($payload['status']) && $payload['status']) ? $payload['status'] : 500);
    if ($status == 500) {
      header($dummyHeader, true, 500);
      echo 'Error 500: Payload missing status property';
      return;
    }

    header($dummyHeader, true, $status);
    foreach ($payload['headers'] as $name => $value) {
      header($name . ': ' . $value);
    }

    $body = ((isset($payload['body']) && $payload['body']) ? $payload['body'] : '');
    echo $body;
    # echo '<p>Raw Result:<br>', $result, '</p>';

  } catch (Exception $e) {
      header($dummyHeader, true, 500);
      echo $e->getMessage();
      return;
  }
}

?>
