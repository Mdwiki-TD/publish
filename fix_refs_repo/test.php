<!DOCTYPE html>
<HTML lang=en dir=ltr data-bs-theme="light" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="robots" content="noindex">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Tools</title>
    <link href='https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css' rel='stylesheet' type='text/css'>

    <script src='https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/3.7.0/jquery.min.js'></script>
    <script src='https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js'></script>
</head>

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/index.php';

use function WpRefs\FixPage\DoChangesToText1;

$lang         = isset($_POST['lang']) ? trim($_POST['lang']) : '';
$text         = isset($_POST['text']) ? trim($_POST['text']) : '';
$revid        = isset($_POST['revid']) ? trim($_POST['revid']) : '';
$sourcetitle  = isset($_POST['sourcetitle']) ? trim($_POST['sourcetitle']) : '';
$title  = isset($_POST['title']) ? trim($_POST['title']) : '';

echo "
    <body>
        <div id='maindiv' class='container'>
            <div class='card'>
                <div class='card-header aligncenter' style='font-weight:bold;'>
                    input infos
                </div>
                <div class='card-body'>
";

// ---
if (empty($lang) || empty($text) || empty($revid) || empty($sourcetitle)) {
    // عرض نموذج لإرسال البيانات إلى text_changes.php
    echo <<<HTML
        <form action='test.php' method='POST'>
            <div class='container'>
                <div class='row'>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>Langcode</span>
                            </div>
                            <input class='form-control' type='text' name='lang' id='lang' value='es' required />
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>title</span>
                            </div>
                            <input class='form-control' type='text' id='title' name='title' value='Enantato_de_noretisterona' />
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>sourcetitle</span>
                            </div>
                            <input class='form-control' type='text' id='sourcetitle' name='sourcetitle' value='Norethisterone enanthate' required />
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>revid</span>
                            </div>
                            <input class='form-control' type='text' id='revid' name='revid' value='1440525' required />
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>test</span>
                            </div>
                            <input class='form-control' type='text' id='test' name='test' value='1' />
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <h4 class='aligncenter'>
                            <input class='btn btn-outline-primary' type='submit' value='start'>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="text" class="form-label">Text:</label>
                <textarea id="text" name="text" rows="5" class="form-control" required>!!</textarea>
            </div>
        </form>
    HTML;
} else {
    // استدعاء الدالة التي تجري التعديلات على النص
    $new_text = DoChangesToText1($sourcetitle, $title, $text, $lang, $revid);
    // $new_text = htmlspecialchars($new_text, ENT_QUOTES, 'UTF-8');
    $no_changes = trim($new_text) === trim($text);
    echo <<<HTML
    <h2>New Text: (no_changes: $no_changes)</h2>
        <textarea name="new_text" rows="15" cols="140">$new_text</textarea>
    HTML;
}
// ---
echo "
                        </div>
                    </div>
                </div>
            </body>
";

?>

</html>
