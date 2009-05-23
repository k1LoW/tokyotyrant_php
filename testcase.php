<?php
ini_set('memory_limit', -1);
require_once 'TokyoTyrant_RDB.php';
require_once 'TokyoTyrant_RDBTBL.php';
require_once 'TokyoTyrant_RDBQRY.php';

/**
 * TokyoTyrant_RDB Test Case
 * example command
 * sudo ttserver -port 1978 -ext "$PWD/testfunc.lua" -dmn -pid "$PWD/ttserver.pid" -log "$PWD/ttserver.log" -ulim "256m" -sid "1" "$PWD/casket.tch#bnum=1000000"
 */
$tt = new TokyoTyrant_RDB();
$key = 'keytest';
$data = 'the test data';
$key2 = 'keytest2';
$data2 = 'the test2 data';
$count_key = 'count';
$extname = 'echo';
$error = null;

//TokyoTyrant_RDB->open()
$getdata = $tt->open('dummy', 1978);
assert(!$getdata);
$tt->open('localhost', 1978, 1000);
assert(strlen($tt->stat()) > 1);

//initialize
assert($tt->vanish() === true);

//TokyoTyrant_RDB->put()
assert($tt->put($key, $data) === true);

//TokyoTyrant_RDB->get()
$getdata = $tt->get($key);
assert($getdata === $data);

//TokyoTyrant_RDB->putkeep()
assert($tt->putkeep($key, $data . 'keep') === false);
$getdata = $tt->get($key);
assert($getdata === $data);

//initialize
$tt->out($key);

//TokyoTyrant_RDB->putkeep()
assert($tt->putkeep($key, $data . 'keep') === true);

//TokyoTyrant_RDB->putcat()
$getdata = $tt->get($key);
assert($getdata === $data . 'keep');
assert($tt->put($key, $data) === true);
$getdata = $tt->get($key);
assert($getdata === $data);
assert($tt->putcat($key, $data) === true);
$getdata = $tt->get($key);
assert($getdata === $data . $data);

//TokyoTyrant_RDB->out()
assert($tt->put($key, $data) === true);
assert($tt->out($key) === true);
$getdata = $tt->get($key);
assert($getdata === false);

//TokyoTyrant_RDB->mget()
assert($tt->put($key, $data));
assert($tt->put($key2, $data2));
assert($tt->mget(array($key, $key2)) === 2);

//TokyoTyrant_RDB->fwmkeys()
assert(count($tt->fwmkeys('key', 2)) === 2);

//TokyoTyrant_RDB->vsiz()
assert($tt->vsiz($key) === strlen($data));

//TokyoTyrant_RDB->iterinit(), TokyoTyrant_RDB->iternext()
assert($tt->vanish() === true);
assert($tt->iterinit() === true);
assert($tt->iternext() === false);
assert($tt->put($key, $data));
assert($tt->iterinit() === true);
assert($tt->iternext() === $key);
assert($tt->iternext() === false);

//TokyoTyrant_RDB->addint()
assert($tt->addint($count_key, 1) === 1);
assert($tt->addint($count_key, 2) === 3);
assert($tt->addint($count_key, -2) === 1);
assert($tt->addint($count_key, 1) === 2);
assert($tt->addint($count_key, -3) === -1);

/*
 * Lua Extension
 * use testfunc.lua for test
 */
$value = 'data';
assert($tt->ext($extname, $key, $value) === $value);
assert($tt->ext($extname, $key, $value, TokyoTyrant_RDB::XOLCKREC) === $value);
assert($tt->ext($extname, $key, $value, TokyoTyrant_RDB::XOLCKGLB) === $value);

$big_data = str_repeat('1', 1024 * 1024 * 32);
//$big_data = str_repeat('1', 1024 * 1024 * 33);
assert($tt->put('bigdata', $big_data));

assert($tt->sync() === true);
assert(!is_array($tt->size()));
assert(!is_array($tt->rnum()));

//TokyoTyrant_RDB->copy()
assert($tt->copy('/tmp/test.tokyotyrant_php.db') === true);
assert(file_exists('/tmp/test.tokyotyrant_php.db') === true);

assert($tt->vanish() === true);

//TokyoTyrant_RDB->stat()
assert(strlen($tt->stat()) > 1);

assert($tt->optimize('') === true);
assert($tt->copy('/tmp/test.tokyotyrant_php.db') === true);

$tt->vanish();
$tt->close();

/**
 * TokyoTyrant_RDBTBL Test Case
 * example command
 * sudo ttserver -port 1980 -ext "$PWD/testfunc.lua" -dmn -pid "$PWD/ttserver.pid" -log "$PWD/ttserver.log" -ulim "256m" -sid "1" "$PWD/casket.tct#bnum=1000000"
 */
$tb = new TokyoTyrant_RDBTBL();
$data = array("OS" => "Ubuntu", "DBM" => "TT/TC", "Language" => "PHP", "Web Server" => "Apache/mod_php", "Memory" => "1000000000");
$data2 = array("OS" => "CentOS", "DBM" => "PostgreSQL", "Language" => "Ruby", "Web Server" => "Apache/passenger", "Memory" => "2000000000");
$error = null;

//TokyoTyrant_RDBTBL->open()
$getdata = $tb->open('dummy', 1980);
assert(!$getdata);
$tb->open('localhost', 1980, 1000);
assert(strlen($tb->stat()) > 1);

assert($tb->vanish() === true);

//TokyoTyrant_RDBTBL->setindex()
assert($tb->setindex('OS', TokyoTyrant_RDBTBL::ITLEXICAL));

//TokyoTyrant_RDBTBL->genuid()
$pkey = $tb->genuid();
assert($pkey !== -1);
assert($tb->put($pkey, $data));
assert($tb->get($pkey) === $data);

//TokyoTyrant_RDBTBL->get()
$pkey = $tb->genuid();
assert($tb->putkeep($pkey, $data));
assert($tb->get($pkey) === $data);

//TokyoTyrant_RDBTBL->out()
assert($tb->out($pkey) === true);
assert($tb->get($pkey) === false);

$tb->vanish();

$pkey = $tb->genuid();
assert($tb->putkeep($pkey, $data));
assert($tb->get($pkey) === $data);
$pkey = $tb->genuid();
assert($tb->putkeep($pkey, $data2));

//TokyoTyrant_RDBQRY::QCSTRINC, TokyoTyrant_RDBQRY::QOSTRDESC
$tq = new TokyoTyrant_RDBQRY($tb);
$tq->addcond("DBM", TokyoTyrant_RDBQRY::QCSTRINC, "TT");
$tq->setorder("Memory", TokyoTyrant_RDBQRY::QOSTRDESC);
$tq->setlimit(10);

assert($tq->searchcount() == 1);

$result = $tq->search();

assert($tb->get($result[0]) === $data);

//TokyoTyrant_RDBQRY::QOSTRDESC
$tq = new TokyoTyrant_RDBQRY($tb);
$tq->setorder("Memory", TokyoTyrant_RDBQRY::QOSTRDESC);
assert($tq->searchcount() == 2);
$result = $tq->search();
assert($tb->get($result[0]) === $data2);

$tb->vanish();
$tb->close();