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

include_once __DIR__ . '/Citation.php';

// include_once __DIR__ . '/helps.php';
include_once __DIR__ . '/md_cat.php';
include_once __DIR__ . '/text_fix_refs.php';
include_once __DIR__ . '/text_fix.php';


use function Publish\TextFix\DoChangesToText;

$lang     = $_POST['target'] ?? '';
$text     = $_POST['text'] ?? '';
$revid    = $_POST['revid'] ?? '';
$sourcetitle = $_POST['sourcetitle'] ?? '';

// تأكد من أن جميع المتغيرات مُعرفة مسبقاً، مثلاً عن طريق استقبالها عبر $_POST
$lang         = isset($_POST['lang']) ? trim($_POST['lang']) : '';
$text         = isset($_POST['text']) ? trim($_POST['text']) : '';
$revid        = isset($_POST['revid']) ? trim($_POST['revid']) : '';
$sourcetitle  = isset($_POST['sourcetitle']) ? trim($_POST['sourcetitle']) : '';
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
        <form action='index.php' method='POST'>
            <div class='container'>
                <div class='row'>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>Langcode</span>
                            </div>
                            <input class='form-control' type='text' name='lang' id='lang' value='fr' required />
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>sourcetitle</span>
                            </div>
                            <input class='form-control' type='text' id='sourcetitle' name='sourcetitle' value='test' required />
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='input-group mb-3'>
                            <div class='input-group-prepend'>
                                <span class='input-group-text'>revid</span>
                            </div>
                            <input class='form-control' type='text' id='revid' name='revid' value='000' required />
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
    $new_text = DoChangesToText($sourcetitle, $text, $lang, $revid);
    echo <<<HTML
    <h2>New Text: </h2>
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
