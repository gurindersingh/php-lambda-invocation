<?php
require 'lib/invoke-lambda.php';
invokeLambda('eu-west-1', 'lambda-proxy-echo');
# invokeLambda('eu-west-1', 'lambda-proxy-html');
# invokeLambda('eu-west-1', 'lambda-proxy-echo', '{"bla":blub"}', '$LATEST');
# invokeLambda('eu-west-1', null, '{"bla":"blub"}', '$LATEST');
# invokeLambda('eu-west-1', 'lambda-proxy-echo', '{"bla":"blub"}');
# invokeLambda('eu-west-1', 'lambda-proxy-exception');
# invokeLambda('eu-west-1', 'lambda-proxy-exceptio');
?>
