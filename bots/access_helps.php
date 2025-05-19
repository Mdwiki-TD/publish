<?php

namespace Publish\AccessHelps;
/*
Usage:
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelps\del_access_from_db;
*/

include_once __DIR__ . '/../include.php';

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\Helps\encode_value;
use function Publish\Helps\decode_value;

function get_access_from_db($user)
{
    // تأكد من تنسيق اسم المستخدم
    $user = trim($user);

    // SQL للاستعلام عن access_key و access_secret بناءً على اسم المستخدم
    $query = <<<SQL
        SELECT access_key, access_secret
        FROM access_keys
        WHERE user_name = ?;
    SQL;

    // تنفيذ الاستعلام وتمرير اسم المستخدم كمعامل
    $result = fetch_query($query, [$user]);

    // التحقق مما إذا كان قد تم العثور على نتائج
    if ($result) {
        $result = $result[0];
        return [
            'access_key' => decode_value($result['access_key']),
            'access_secret' => decode_value($result['access_secret'])
        ];
    } else {
        // إذا لم يتم العثور على نتيجة، إرجاع null أو يمكنك تخصيص رد معين
        return null;
    }
}

function del_access_from_db($user)
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM access_keys WHERE user_name = ?;
    SQL;

    execute_query($query, [$user]);
}
