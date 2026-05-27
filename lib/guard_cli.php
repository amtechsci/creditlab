<?php
if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	exit('Forbidden. Run this script from the command line.');
}
