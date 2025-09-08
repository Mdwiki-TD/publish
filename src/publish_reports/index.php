<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" xmlns="http://www.w3.org/1999/xhtml">

<?php

// Enable error reporting for debugging
if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
//---
function get_host()
{
    // $hoste = get_host();
    //---
    static $cached_host = null;
    //---
    if ($cached_host !== null) {
        return $cached_host; // استخدم القيمة المحفوظة
    }
    //---
    $hoste = ($_SERVER["SERVER_NAME"] == "localhost")
        ? "https://cdnjs.cloudflare.com"
        : "https://tools-static.wmflabs.org/cdnjs";
    //---
    if ($hoste == "https://tools-static.wmflabs.org/cdnjs") {
        $url = "https://tools-static.wmflabs.org";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // لا نريد تحميل الجسم
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // لمنع الطباعة

        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // المهلة القصوى للاتصال
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CDN-Checker)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // إذا فشل الاتصال أو لم تكن الاستجابة ضمن 200–399، نستخدم cdnjs
        if ($result === false || !empty($curlError) || $httpCode < 200 || $httpCode >= 400) {
            $hoste = "https://cdnjs.cloudflare.com";
        }
    }

    $cached_host = $hoste;

    return $hoste;
}
//---
$hoste = get_host();
//---
echo <<<HTML
	<head>
		<meta charset="UTF-8">
		<meta name="robots" content="noindex">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>WikiProjectMed Tools</title>
		<link href='$hoste/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css' rel='stylesheet'>
		<script src='$hoste/ajax/libs/jquery/3.7.0/jquery.min.js'></script>
		<script src='$hoste/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js'></script>
		<style>
			a {
				text-decoration: none;
			}
		</style>
	</head>
HTML;
// Constants
// define('REPORTS_DIR', 'reports');
define('REPORTS_DIR', 'reports_by_day');

define('PUBLISH_REPORTS_DIR', __DIR__ . '/reports_by_day/');

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

function add_time_badge($dir_time, $formattedDate)
{
    $today = date('Y-m-d'); // d M Y
    if ($today != $formattedDate) {
        return "";
    }
    $diff = time() - $dir_time;

    if ($diff < 86400) {
        if ($diff < 60) {
            return $diff . 's ago';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . 'm ago';
        } else {
            $hours = floor($diff / 3600);
            return $hours . 'h ago';
        }
    }

    return date('H:i', $dir_time);
}


function addTodayBadge($dir_date)
{
    // $dir_date = "$year-$month-$day";
    // ---
    $today = date('Y-m-d'); // d M Y

    return $today === $dir_date ? ' <span class="badge text-bg-warning" style="float: right">Today</span>' : "";
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
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December'
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

function makeDayReports($year, $month, $day, $dayReportDir, $monthDir)
{
    // ---
    $formattedDate = "$year-$month-$day";
    // ---
    // $todayBadge = addTodayBadge($formattedDate);
    $todayBadge = "";
    // ---
    $dailyReports = scandir($dayReportDir);
    // ---
    usort($dailyReports, function ($a, $b) use ($dayReportDir) {
        return filectime($dayReportDir . '/' . $b) - filectime($dayReportDir . '/' . $a);
    });
    // ---
    $dailyReportLinks = "";
    // ---
    $count = 0;
    // ---
    foreach ($dailyReports as $report) {
        if ($report === '.' || $report === '..') continue;
        // ---
        $oneReportDir = $dayReportDir . '/' . $report;
        // ---
        $jsonFiles = glob($oneReportDir . '/*.json');
        // ---
        if (!$jsonFiles) continue;
        // ---
        $dir_time = filectime($oneReportDir);
        // ---
        $time = add_time_badge($dir_time, $formattedDate);
        // ---
        $user = "";
        $lang = "";
        // ---
        $ul = '<ul class="list-group">';
        // ---
        foreach ($jsonFiles as $jsonFile) {
            // ---
            $name = basename($jsonFile);
            // ---
            // if (empty($user) && $name == "success.json") {
            if (empty($user)) {
                $json = json_decode(file_get_contents($jsonFile), true);
                $user = $json['user'] ?? '';
                $target_title = $json['title'] ?? '';
                $lang = $json['lang'] ?? '';
            }
            // ---
            $url = "$monthDir/$day/$report/$name";
            // ---
            $ul .= <<<HTML
                <li class="list-group-item">
                    <a target="_blank" href="$url">$name</a>
                </li>
            HTML;
        }
        // ---
        if (!empty($lang) && !empty($target_title)) {
            $lang = "<a href='https://$lang.wikipedia.org/wiki/$target_title' target='_blank'>$lang</a>";
        }
        // ---
        $lang = $lang ? "$lang: " : "";
        // ---
        if (!$jsonFiles) {
            $ul .= <<<HTML
            <li class="list-group-item">
                No files!
            </li>
        HTML;
        }
        // ---
        $ul .= '</ul>';
        // ---
        $count++;
        // ---
        $dailyReportLinks .= <<<HTML
            <div class="col-md-3 p-2">
                <div class="card">
                    <div class="card-header p-2">
                        <span class="card-title h5">
                            $lang $user
                            <span class='badge text-bg-success' style='float: right'>$time</span>
                        </span>
                    </div>
                    <div class="card-body p-0">$ul</div>
                </div>
            </div>
        HTML;
        // }

    }
    // ---
    $retsult = "";
    // ---
    if (!empty($dailyReportLinks)) {
        $retsult = <<<HTML
            <div class="card px-0 m-1 mt-3">
                <div class="card-header bg-secondary text-white">
                    <span class="card-title h4">$formattedDate ($count) $todayBadge</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        $dailyReportLinks
                    </div>
                </div>
            </div>
        HTML;
    }
    // ---
    return $retsult;
}
function makeMonthReports($year, $month)
{
    // $monthDir = PUBLISH_REPORTS_DIR . "$year/" . str_pad($month, 2, '0', STR_PAD_LEFT);
    $monthDir = REPORTS_DIR . "/$year/" . str_pad($month, 2, '0', STR_PAD_LEFT);

    if (!is_dir(__DIR__ . "/$monthDir")) {
        $monthDir = REPORTS_DIR . "/$year/01";
    }
    $monthDirPath = __DIR__ . "/$monthDir";

    if (!is_dir($monthDirPath)) {
        return '<p>No reports available.</p>';
    }
    // ---
    $MonthReportLinks = '';
    // ---
    $daysDirs = scandir($monthDirPath);
    // ---
    // sort days by name bigger first
    usort($daysDirs, function ($a, $b) {
        return strcmp($b, $a);
    });
    // ---
    foreach ($daysDirs as $day) {
        // ---
        if ($day === '.' || $day === '..') continue;
        // ---
        $dayReportDir = $monthDirPath . '/' . $day;
        // ---
        if (!is_dir($dayReportDir)) {
            continue;
        }
        // ---
        $dailyReportLinks = makeDayReports($year, $month, $day, $dayReportDir, $monthDir);
        // ---
        $MonthReportLinks .= $dailyReportLinks;
        // ---
    }
    // ---
    return $MonthReportLinks;
}

// Main Logic
checkDirectories();

$year = isset($_GET['y']) ? $_GET['y'] : date('Y');
$month = isset($_GET['m']) ? $_GET['m'] : date('m');

$yearsNav = makeYearsNav($year);
$monthsNav = makeMonthsNav($year, $month);
$reports = makeMonthReports($year, $month);

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
