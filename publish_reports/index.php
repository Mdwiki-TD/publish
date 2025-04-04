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

$publish_reports = __DIR__ . "/reports/";

function check_dirs()
{
    global $publish_reports;
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

function add_badge($report_dir)
{
    // ---
    $today = date('Y-m-d');
    // $today = "2025";
    // ---
    $date = date('Y-m-d H:i', filemtime($report_dir));
    // ---
    // if $report_dir last changes is today then add badge
    if (date('Y-m-d', filemtime($report_dir)) === $today) {
        return " <span class='badge text-bg-primary'>Today</span>";
    };
    // ---
    return "($date)";
}

function make_years_nav($year)
{
    global $publish_reports;
    // make bootstrap5 tabs nav list with link= index.php?year=$year
    // ---
    $dirs = scandir($publish_reports);
    // ---
    $nav = '<ul class="nav nav-tabs">';
    // ---
    foreach ($dirs as $year_dir) {
        // ---
        if ($year_dir === '.' || $year_dir === '..') {
            continue;
        }
        // ---
        if (!is_dir($publish_reports . $year_dir)) {
            continue;
        }
        // ---
        $active = ($year_dir == $year) ? "active" : "";
        $nav .= <<<HTML
            <li class="nav-item">
                <a class="nav-link $active" href="index.php?y=$year_dir">$year_dir</a>
            </li>
            HTML;
    }
    // ---
    $nav .= "</ul>";
    // ---
    return $nav;
}

function make_months_nav($year, $month)
{
    global $publish_reports;
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $nav = '<ul class="nav nav-tabs">';

    foreach ($months as $month_num => $month_name) {
        // ---
        $month_link = str_pad($month_num, 2, '0', STR_PAD_LEFT);
        // ---
        $active = ($month_num == $month || $month == $month_link) ? 'active' : '';
        // ---
        $month_dir = $publish_reports . "/$year/$month_link"; // Ensure 2 digit month.
        // ---
        if (is_dir($month_dir)) {
            $nav .= <<<HTML
                <li class="nav-item">
                    <a class="nav-link $active" href="index.php?y=$year&m=$month_link">$month_name</a>
                </li>
            HTML;
        } else {
            $nav .= <<<HTML
                <li class="nav-item">
                    <span class="nav-link disabled">$month_name</span>
                </li>
            HTML;
        }
    }

    $nav .= '</ul>';
    return $nav;
}

function make_reports($year, $month)
{
    global $publish_reports;

    $month_dir = $publish_reports . "/$year/" . str_pad($month, 2, '0', STR_PAD_LEFT);

    if (!is_dir($month_dir)) {
        return "<p></p>";
    }

    $report_links = '';

    $reports = scandir($month_dir);

    // sort by date
    usort($reports, function ($a, $b) use ($month_dir) {
        return filemtime($month_dir . "/$b") - filemtime($month_dir . "/$a");
    });

    foreach ($reports as $report) {
        if ($report === '.' || $report === '..') continue;
        // ---
        $report_dir = $month_dir . "/" . $report;
        // ---
        if (!is_dir($report_dir)) continue;
        // ---
        $suff = add_badge($report_dir);
        // ---
        $ul = '<ul class="list-group">';
        $json_files = glob($report_dir . '/*.json');
        // ---
        foreach ($json_files as $json_file) {
            // ---
            $name = basename($json_file);
            // ---
            $url = "reports/$year/$month/" . $report . '/' . $name;
            // ---
            $ul .= <<<HTML
                <li class="list-group-item">
                    <a target="_blank" href='$url'>$name</a>
                </li>
            HTML;
        }
        // ---
        $ul .= "</ul>";
        // ---
        // $dir_title = $report;
        $dir_title = date('m-d H:i', filemtime($report_dir));
        // ---
        $report_links .= <<<HTML
            <div class="card px-0 m-1">
                <div class="card-header">
                    <span class="card-title h5">
                        $dir_title
                    </span>
                    <div style="float: right">
                        $suff
                    </div>
                </div>
                <div class="card-body p-0">
                    $ul
                </div>
            </div>
        HTML;
    }

    $report_links = '<div class="row row-cols-auto row-cols-md-5">' . $report_links . '</div>';

    return $report_links;
}

check_dirs();

// ---
$year = isset($_GET['y']) ? $_GET['y'] : date('Y');
$month = isset($_GET['m']) ? $_GET['m'] : date('m');

$years_nav = make_years_nav($year);
$months_nav = make_months_nav($year, $month);
$m_reports = make_reports($year, $month);

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
    <main>
        <div class="container">
            $years_nav
            $months_nav
            <div class="tab-content">
                <div class="tab-pane fade show active pt-3">
                    $m_reports
                </div>
            </div>
        </div>
    </main>
</body>

</html>
HTML;
