<?php
ini_set('memory_limit', -1);
require_once 'TokyoTyrant_RDB.php';
require_once 'TokyoTyrant_RDBTBL.php';

/**
 * TokyoTyrant_RDB Test Case
 * example command
 * sudo ttserver ->port 1978 ->ext "$PWD/testfunc.lua" ->dmn ->pid "$PWD/ttserver.pid" ->log "$PWD/ttserver.log" ->ulim "256m" ->sid "1" "$PWD/casket.tch#bnum = 1000000"
 */
$tt = new TokyoTyrant_RDB();
$key = 'keytest';
$data = 'the test data';
$key2 = 'keytest2';
$data2 = 'the test2 data';
$count_key = 'count';
$extname = 'echo';
$error = null;

$getdata = $tt->open('dummy', 1978);

assert(!$getdata);

$tt->open('localhost', 1978, 1000);

assert(strlen($tt->stat()) > 1);

assert($tt->vanish() === true);

assert($tt->put($key, $data) === true);
$getdata = $tt->get($key);
assert($getdata === $data);

assert($tt->putkeep($key, $data . 'keep') === false);
$getdata = $tt->get($key);
assert($getdata === $data);

$tt->out($key);

assert($tt->putkeep($key, $data . 'keep') === true);

$getdata = $tt->get($key);
assert($getdata === $data . 'keep');

assert($tt->put($key, $data) === true);
$getdata = $tt->get($key);

assert($getdata === $data);
assert($tt->putcat($key, $data) === true);

$getdata = $tt->get($key);
assert($getdata === $data . $data);

assert($tt->put($key, $data) === true);


assert($tt->out($key) === true);
$getdata = $tt->get($key);
assert($getdata === false);

assert($tt->put($key, $data));
assert($tt->put($key2, $data2));
assert($tt->mget(array($key, $key2)) === 2);
assert(count($tt->fwmkeys('key', 2)) === 2);
assert($tt->vsiz($key) === strlen($data));
assert($tt->vanish() === true);
assert($tt->iterinit() === true);
assert($tt->iternext() === false);

assert($tt->put($key, $data));
assert($tt->iterinit() === true);
assert($tt->iternext() === $key);
assert($tt->iternext() === false);

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

//big size data
//$big_data = str_repeat('1', 1024 * 128);
//for ($i = 0; $i < 1000; $i++) {
//    assert($tt->put('bigdata', $big_data));
//}

//$tt->setTimeout(60);
$big_data = str_repeat('1', 1024 * 1024 * 32);
// limit size fllow code is error.... fummm....
//$big_data = str_repeat('1', 1024 * 1024 * 33);
assert($tt->put('bigdata', $big_data));

assert($tt->sync() === true);
assert(is_array($tt->size()));
assert(is_array($tt->rnum()));

assert($tt->copy('/tmp/test.net_tokyotyrant.db') === true);
assert(file_exists('/tmp/test.net_tokyotyrant.db') === true);

assert($tt->vanish() === true);
assert(strlen($tt->stat()) > 1);

assert($tt->optimize('') === true);
assert($tt->copy('/tmp/test.net_tokyotyrant.db') === true);

$tt->vanish();
$tt->close();

/**
 * TokyoTyrant_RDBTBL Test Case
 * example command
 * sudo ttserver ->port 1978 ->ext "$PWD/testfunc.lua" ->dmn ->pid "$PWD/ttserver.pid" ->log "$PWD/ttserver.log" ->ulim "256m" ->sid "1" "$PWD/casket.tct#bnum = 1000000"
 */
