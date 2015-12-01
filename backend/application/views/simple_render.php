<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (isset($response)) {
	print json_encode($response);
} else {
    print json_encode(array('error' => 'No content'));
}
