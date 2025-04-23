<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WikiProjectMed Tools</title>
    <link href='https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css' rel='stylesheet'>
    <script src='https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/3.7.0/jquery.min.js'></script>
    <script src='https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js'></script>
    <style>
        a {
            text-decoration: none;
        }
    </style>
</head>

<?php

// Enable error reporting for debugging
if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Constants
define('PUBLISH_REPORTS_DIR', __DIR__ . '/reports/');

// Functions
function ensureDirectoryExists($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function checkDirectories()
{
    ensureDirectoryExists(PUBLISH_REPORTS_DIR);
    ensureDirectoryExists(getYearDirectory());
    ensureDirectoryExists(getMonthDirectory());
}

function getYearDirectory()
{
    return PUBLISH_REPORTS_DIR . date('Y') . '/';
}

function getMonthDirectory()
{
    return getYearDirectory() . date('m') . '/';
}

function addBadge($directory)
{
    $today = date('Y-m-d');
    $lastModified = date('Y-m-d', filemtime($directory));

    return $today === $lastModified ? ' <span class="badge text-bg-primary">Today</span>' : " ($lastModified)";
}

function makeYearsNav($currentYear)
{
    $years = array_filter(scandir(PUBLISH_REPORTS_DIR), function ($item) {
        return is_dir(PUBLISH_REPORTS_DIR . $item) && !in_array($item, ['.', '..']);
    });

    if (empty($years)) {
        return '<p>No years available.</p>';
    }

    $nav = '<ul class="nav nav-tabs">';

    foreach ($years as $year) {
        $active = $year === $currentYear ? 'active' : '';
        $nav .= <<<HTML
                <li class="nav-item">
                    <a class="nav-link $active" href="?y=$year">$year</a>
                </li>
            HTML;
    }

    $nav .= '</ul>';
    return $nav;
}

function makeMonthsNav($currentYear, $currentMonth)
{
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $nav = '<ul class="nav nav-tabs">';

    foreach ($months as $monthNum => $monthName) {
        $monthLink = str_pad($monthNum, 2, '0', STR_PAD_LEFT);
        $active = $monthNum == $currentMonth || $monthLink == $currentMonth ? 'active' : '';
        $monthDir = PUBLISH_REPORTS_DIR . "$currentYear/$monthLink";

        if (is_dir($monthDir)) {
            $nav .= <<<HTML
                    <li class="nav-item">
                        <a class="nav-link $active" href="?y=$currentYear&m=$monthLink">$monthName</a>
                    </li>
                HTML;
        } else {
            $nav .= <<<HTML
                    <li class="nav-item">
                        <span class="nav-link disabled">$monthName</span>
                    </li>
                HTML;
        }
    }

    $nav .= '</ul>';
    return $nav;
}

function makeReports($year, $month)
{
    $monthDir = PUBLISH_REPORTS_DIR . "$year/" . str_pad($month, 2, '0', STR_PAD_LEFT);

    if (!is_dir($monthDir)) {
        return '<p>No reports available.</p>';
    }

    $reportsByDate = [];

    foreach (scandir($monthDir) as $report) {
        if ($report === '.' || $report === '..') continue;
        $reportDir = $monthDir . '/' . $report;

        if (is_dir($reportDir)) {
            $dateKey = date('Y-m-d', filemtime($reportDir));
            $reportsByDate[$dateKey][] = $report;
        }
    }

    krsort($reportsByDate);

    $reportLinks = '';

    foreach ($reportsByDate as $date => $dailyReports) {
        $dailyReportLinks = '';

        usort($dailyReports, function ($a, $b) use ($monthDir) {
            return filemtime($monthDir . '/' . $b) - filemtime($monthDir . '/' . $a);
        });

        foreach ($dailyReports as $report) {
            $reportDir = $monthDir . '/' . $report;
            $badge = addBadge($reportDir);
            $time = date('H:i', filemtime($reportDir));
            $jsonFiles = glob($reportDir . '/*.json');

            $ul = '<ul class="list-group">';

            foreach ($jsonFiles as $jsonFile) {
                $name = basename($jsonFile);
                $url = "reports/$year/$month/$report/$name";
                $ul .= <<<HTML
                        <li class="list-group-item">
                            <a target="_blank" href="$url">$name</a>
                        </li>
                    HTML;
            }

            $ul .= '</ul>';

            $dailyReportLinks .= <<<HTML
                    <div class="col-md-3">
                        <div class="card px-0 m-1">
                            <div class="card-header">
                                <span class="card-title h5">$time</span>
                                <div style="float: right">$badge</div>
                            </div>
                            <div class="card-body p-0">$ul</div>
                        </div>
                    </div>
                HTML;
        }

        $formattedDate = date('d M Y', strtotime($date));
        $reportLinks .= <<<HTML
                <div class="card px-0 m-1 mt-3">
                    <div class="card-header bg-secondary text-white">
                        <span class="card-title h4">$formattedDate</span>
                    </div>
                    <div class="card-body">
                        <div class="row">$dailyReportLinks</div>
                    </div>
                </div>
            HTML;
    }

    return $reportLinks;
}

// Main Logic
checkDirectories();

$year = isset($_GET['y']) ? $_GET['y'] : date('Y');
$month = isset($_GET['m']) ? $_GET['m'] : date('m');

$yearsNav = makeYearsNav($year);
$monthsNav = makeMonthsNav($year, $month);
$reports = makeReports($year, $month);

?>

<body>
    <header class="mb-3 border-bottom">
        <nav id="mainnav" class="navbar navbar-expand-lg shadow">
            <div class="container-fluid" id="navbardiv">
                <a class="navbar-brand mb-0 h1" href="/publish_reports" style="color:#0d6efd;">Publish Reports</a>
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
            <?php echo $yearsNav; ?>
            <?php echo $monthsNav; ?>
            <div class="tab-content">
                <div class="tab-pane fade show active pt-3">
                    <?php echo $reports; ?>
                </div>
            </div>
        </div>
    </main>

</body>

</html>
