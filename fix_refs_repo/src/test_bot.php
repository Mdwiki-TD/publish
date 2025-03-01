<?php

namespace WpRefs\TestBot;

/*
usage:

use function WpRefs\TestBot\echo_test;

*/

function echo_test($str)
{
    // ---
    if (isset($_POST['test']) || isset($_GET['test'])) {
        echo $str;
    }
    // ---
}
