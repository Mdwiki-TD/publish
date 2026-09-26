<?php

namespace Publish\Helps;
/*
Usage:
use function Publish\Helps\pub_test_print;
*/

function pub_test_print($s)
{
    if (!isset($_REQUEST['test'])) return;
    if (gettype($s) == 'string') {
        echo "\n<br>\n$s";
    } else {
        echo "\n<br>\n";
        print_r($s);
    }
}
