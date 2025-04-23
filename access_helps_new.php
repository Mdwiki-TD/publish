<?php

namespace Publish\AccessHelpsNew;
/*
Usage:
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function Publish\AccessHelpsNew\del_access_from_db_new;
*/

include_once __DIR__ . '/include.php';

use function Publish\MdwikiSql\execute_query;
use function Publish\MdwikiSql\fetch_query;
use function Publish\Helps\encode_value;
use function Publish\Helps\decode_value;

function get_access_from_db_new($user)
{
    // تأكد من تنسيق اسم المستخدم
    $user = trim($user);

    // SQL للاستعلام عن a_k و a_s بناءً على اسم المستخدم
    $query = <<<SQL
        SELECT a_k, a_s
        FROM keys_new
        WHERE u_n = ?;
    SQL;

    // تنفيذ الاستعلام وتمرير اسم المستخدم كمعامل
    $result = fetch_query($query, encode_value($user));

    // التحقق مما إذا كان قد تم العثور على نتائج
    if ($result) {
        $result = $result[0];
        return [
            'access_key' => decode_value($result['a_k']),
            'access_secret' => decode_value($result['a_s'])
        ];
    } else {
        // إذا لم يتم العثور على نتيجة، إرجاع null أو يمكنك تخصيص رد معين
        return null;
    }
}

function del_access_from_db_new($user)
{
    $user = trim($user);

    $query = <<<SQL
        DELETE FROM keys_new WHERE u_n = ?;
    SQL;

    execute_query($query, [encode_value($user)]);
}
