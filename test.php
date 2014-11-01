<?php
require_once 'FF_PDO.class.php';

function db(){
    $db = new FF_PDO('sqlite:'.__FILE__.'.sqlite.db',null,null,array(
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
    $db->exec("PRAGMA busy_timeout = 5000;");
    $db->exec("PRAGMA synchronous = OFF");
    $db->exec("PRAGMA journal_mode = MEMORY");
    $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_WARNING);
    return $db;
}

$db =& db();
$db->exec("DROP TABLE IF EXISTS ff");
$db->exec("CREATE TABLE ff (a,b,c)");
$db->q("insert into ff (a,b,c) VALUES (?,?,?)",1,2,3);
$db->q("insert into ff (a,b,c) VALUES (?,?,?)",1,2,3);
$db->q("insert into ff (a,b,c) VALUES (?,?,?)",1,2,3);


print_r($db->qt("select * from ff where a=?",1));
print_r($db->qv("select a from ff"));
