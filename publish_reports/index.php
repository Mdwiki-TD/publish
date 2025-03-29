<!DOCTYPE html>
<HTML lang=en dir=ltr data-bs-theme="light" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="robots" content="noindex">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WikiProjectMed Tools</title>
    <link href='https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css' rel='stylesheet' type='text/css'>
    <script src='https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/3.7.0/jquery.min.js'></script>
    <script src='https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js'></script>
    <style>
        a {
            text-decoration: none;
        }
    </style>
</head>

<?php

if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
};

echo <<<HTML
<body>
    <header class="mb-3 border-bottom">
        <nav id="mainnav" class="navbar navbar-expand-lg shadow">
            <div class="container-fluid" id="navbardiv">
                <a class="navbar-brand mb-0 h1" href="/publish_reports" style="color:#0d6efd;">
                    Publish Reports
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar" aria-controls="collapsibleNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="collapsibleNavbar">
                    <ul class="navbar-nav flex-row flex-wrap bd-navbar-nav">
                        <li class="nav-item col-4 col-lg-auto">
                            <a class="nav-link py-2 px-0 px-lg-2" href="#">
                                <span class="navtitles">Publish Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main id="body">
        <div id="maindiv" class="container">
HTML;

function check_dirs()
{
    $publish_reports = __DIR__ . "/reports/";
    // ---
    if (!is_dir($publish_reports)) {
        mkdir($publish_reports, 0755, true);
    }
    // ---
    $year_dir = $publish_reports . date("Y");
    // ---
    if (!is_dir($year_dir)) {
        mkdir($year_dir, 0755, true);
    }
    // ---
    $month_dir = $year_dir . "/" . date("m");
    // ---
    if (!is_dir($month_dir)) {
        mkdir($month_dir, 0755, true);
    }
}

function card($title, $text)
{
    echo <<<HTML
        <div class="card mb-3">
            <div class="card-header aligncenter" style="font-weight:bold;">
                <h3>$title</h3>
            </div>
            <div class="card-body">
                <div class="row row-cols-auto row-cols-md-3">
                    $text
                </div>
            </div>
        </div>
    HTML;
}

function make_ul($dir)
{
    // $text = "<ol>";
    $text = "";
    $json_files = glob(__DIR__ . '/' . $dir . '/*.json');
    // ---
    // sort by date
    usort($json_files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    // ---
    $today = date('Y-m-d');
    // ---
    foreach ($json_files as $json_file) {
        $name = basename($json_file);
        $url = rawurlencode($dir) . '/' . rawurlencode($name);
        // ---
        $date = date('Y-m-d H:i', filemtime($json_file));
        // ---
        $date_line = "($date) ";
        // ---
        if (date('Y-m-d', filemtime($json_file)) === $today) {
            // $date_line = "<span style='color:green;'>$date</span>";
            $date_line .= " <span class='badge text-bg-primary'>Today</span>";
        }
        // ---
        // $text .= "<li><a target="_blank" href='$url'>$name</a> ($date)</li>";
        // ---
        $text .= <<<HTML
            <div class="col"><a target="_blank" href='$url'>$name</a> $date_line</div>
        HTML;
    }
    // $text .= '</ol>';
    return $text;
}

check_dirs();

$reports_dirs = scandir(__DIR__ . "/reports/");
// ---
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
// ---
function make_years_nav($year)
{
    // make bootstrap5 years nav list with link= index.php?year=$year
    // ---
    $dirs = scandir(__DIR__ . "/reports/");
    // ---
    $nav = '<ul class="nav nav-pills">';
    // ---
    foreach ($dirs as $year_dir) {
        // ---
        if ($year_dir === '.' || $year_dir === '..') {
            continue;
        }
        // ---
        if (!is_dir(__DIR__ . "/reports/" . $year_dir)) {
            continue;
        }
        // ---
        $active = ($year_dir == $year) ? "active" : "";
        $nav .= <<<HTML
            <li class="nav-item">
                <a class="nav-link $active" href="index.php?year=$year_dir">$year_dir</a>
            </li>
            HTML;
    }
    // ---
    $nav .= "</ul>";
    // ---
    return $nav;
}

$years_nav = make_years_nav($year);

echo $years_nav;

echo <<<HTML
        </div>
    </main>
</body>

</html>
HTML;
