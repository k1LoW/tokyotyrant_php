<?php
  /**
     tokyotyrant.rb
     lisence:
     #--
     # Pure Ruby interface of Tokyo Cabinet
     #                                                       Copyright (C) 2006-2008 Mikio Hirabayashi
     #  This file is part of Tokyo Cabinet.
     #  Tokyo Cabinet is free software; you can redistribute it and/or modify it under the terms of
     #  the GNU Lesser General Public License as published by the Free Software Foundation; either
     #  version 2.1 of the License or any later version.  Tokyo Cabinet is distributed in the hope
     #  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
     #  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
     #  License for more details.
     #  You should have received a copy of the GNU Lesser General Public License along with Tokyo
     #  Cabinet; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330,
     #  Boston, MA 02111-1307 USA.
     #++
     URL:http://tokyocabinet.sourceforge.net/tyrantrubypkg/

     Net_TokyoTyrant
     lisence:
     MIT License
     URL:http://openpear.org/package/Net_TokyoTyrant
  */
class TokyoTyrant_RDB {

    /**
     * constants
     */
    // error code: success
    const ESUCCESS = 0;
    // error code: invalid operation
    const EINVALID = 1;
    // error code: host not found
    const ENOHOST = 2;
    // error code: connection refused
    const EREFUSED = 3;
    // error code: send error
    const ESEND = 4;
    // error code: recv error
    const ERECV = 5;
    // error code: existing record
    const EKEEP = 6;
    // error code: no record found
    const ENOREC = 7;
    // error code: miscellaneous error
    const EMISC = 9999;
    // scripting extension option: record locking
    // Ruby:XOLCKREC = 1 << 0
    const XOLCKREC = 1;
    // scripting extension option: global locking
    // Ruby:XOLCKGLB = 1 << 1
    const XOLCKGLB = 2;
    // versatile function option: omission of the update log
    // Ruby:MONOULOG = 1 << 0
    const MONOULOG = 1;

    protected $ecode;
    protected $enc;
    protected $sock;

    /**
     * __construct
     */
    public function __construct() {
        $this->ecode = ESUCCESS;
        $this->enc = null;
        $this->sock = null;
    }

    /**
     * errmsg
     *
     * Get the message string corresponding to an error code.
     *
     * $param Integer $ecode Error Code
     * $return String Error Message
     */
    public function errmsg($ecode = null) {
        if (!$ecode) {
            $ecode = $this->ecode;
        }
        if ($ecode == ESUCCESS) {
            return "success";
        } elseif ($ecode == EINVALID) {
            return "invalid operation";
        } elseif ($ecode == ENOHOST) {
            return "host not found";
        } elseif ($ecode == EREFUSED) {
            return "connection refused";
        } elseif ($ecode == ESEND) {
            return "send error";
        } elseif ($ecode == ERECV) {
            return "recv error";
        } elseif ($ecode == EKEEP) {
            return "existing record";
        } elseif ($ecode == ENOREC) {
            return "no record found";
        } elseif ($ecode == EMISC) {
            return "miscellaneous error";
        } else {
            return "unknown";
        }
    }

    /**
     * ecode
     *
     * Get the last happened error code.
     *
     * return Last Error Code
     */
    public function ecode() {
        return $this->ecode;
    }

    /**
     * open
     *
     * Open a remote database connection.%%
     *
     * $host String
     * $port Integer
     * $timeout Integer
     */
    public function open($host, $port, $timeout = 10) {
        if ($this->sock) {
            $ecode = EINVALID;
            return false;
        }
        $this->sock = @fsockopen($host,$port, $this->errorNo, $errorMessage, $timeout);
        if (! $this->sock) {
            $ecode = EREFUSED;
            return false;
        }
        return true;
    }

    /**
     * Close the database connection.
     *
     */
    public function close() {
        if (!$this->sock) {
            $ecode = EINVALID;
            return false;
        }
        if (fclose($this->sock)) {
            return true;
        } else {
            $ecode = EMISC;
            $sock = null;
            return false;
        }
    }

    /**
     * Store a record.
     *
     */
    public function put($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $cmd = pack('c*', 0xC8,0x10);
        $sbuf = $this->_makeBuf($cmd, array((string) $key,(string) $value));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;
    }

    /**
     * Store a new record.
     *
     */
    public function putkeep ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x11);
        $sbuf = $this->_makeBuf($cmd, array((string) $key,(string) $value));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EKEEP;
            return false;
        }
        return true;
    }

    /**
     * Concatenate a value at the end of the existing record.
     *
     */
    public function putcat ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x12);
        $sbuf = $this->_makeBuf($cmd, array((string) $key,(string) $value));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;

    }

    /**
     * Concatenate a value at the end of the existing record and shift it to the left.
     *
     */
    public function putshl ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $cmd = pack('c*', 0xC8,0x13);
        $sbuf = $this->_makeBuf($cmd, array((string) $key,(string) $value));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;

    }

    /**
     * Store a record without response from the server.
     *
     */
    public function putnr ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $cmd = pack('c*', 0xC8,0x18);
        $sbuf = $this->_makeBuf($cmd, array((string) $key,(string) $value));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }
        return true;

    }

    /**
     * Remove a record.
     *
     */
    public function out ($key) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x20);
        $sbuf = $this->_makeBuf($cmd, array((string) $key));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = ENOREC;
            return false;
        }
        return true;

    }

    /**
     * Retrieve a record.
     *
     */
    public function get ($key) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x30);
        $sbuf = $this->_makeBuf($cmd, array((string) $key));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = ENOREC;
            return false;
        }

        $vsiz = $this->_recvint32();
        if ($vsiz < 0) {
            $this->ecode = ERECV;
            return false;
        }
        $vbuf = $this->_recv($vsiz);
        if (!$vbuf) {
            $this->ecode = ERECV;
            return false;
        }

        return $this->_retstr($vbuf);
    }

    /**
     * Retrieve records.
     *
     */
    public function mget ($recs) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x31);
        $rnum = count($recs);
        $values = array();
        $values[] = $rnum;
        foreach($recs as $key) {
            $values[] = array((string) $key);
        }
        $sbuf = $this->_makeBuf($cmd, array($values));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        $rnum = $this->_recvint32();
        if ($code == -1) {
            $this->ecode = ERECV;
            return -1;
        }
        if ($code != 0) {
            $this->ecode = ENOREC;
            return -1;
        }
        if ($rnum < 0) {
            $this->ecode = ERECV;
            return -1;
        }
        $recs = array();

        for($i = 0;$i < $rnum; $i++) {
            $ksiz = $this->_recvint32();
            $vsiz = $this->_recvint32();
            if ($ksiz < 0 || $vsiz < 0) {
                $this->ecode = ERECV;
                return -1;
            }
            $kbuf = $this->_recv($ksiz);
            $vbuf = $this->_recv($vsiz);
            if (!$kbuf || !$vbuf) {
                $this->ecode = ERECV;
                return -1;
            }
            $recs[$kbuf] = $this->_retstr($vbuf);
        }
        return $rnum;//return $recs;
    }

    /**
     * Get the size of the value of a record.
     *
     */
    public function vsiz ($key) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x38);
        $sbuf = $this->_makeBuf($cmd, array((string) $key));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = ENOREC;
            return false;
        }
        return $this->_recvint32();

    }

    /**
     * Initialize the iterator.
     *
     */
    public function iterinit() {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x50);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;
    }

    /**
     * Get the next key of the iterator.
     *
     */
    public function iternext() {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x51);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = ENOREC;
            return false;
        }
        $vsiz = $this->_recvint32();
        if ($vsiz < 0) {
            $this->ecode = ERECV;
            return false;
        }
        $vbuf = $this->_recv($vsiz);
        if (!$vbuf) {
            $this->ecode = ERECV;
            return false;
        }
        return $this->_retstr($vbuf);
    }

    /**
     * Get forward matching keys.
     *
     */
    public function fwmkeys ($prefix, $max = -1) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x58);
        $sbuf = $this->_makeBuf($cmd, array((string) $prefix,(int) $max));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return array();
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return array();
        }
        if ($code != 0) {
            $this->ecode = ENOREC;
            return array();
        }
        $knum = $this->_recvint32();
        if ($knum < 0) {
            $this->ecode = ERECV;
            return array();
        }
        $keys = array();
        for ($i = 0; $i < $knum; $i++) {
            $ksiz = $this->_recvint32();
            if ($ksiz < 0) {
                $this->ecode = ERECV;
                return array();
            }
            $kbuf = $this->_recv($ksiz);
            if (!$kbuf) {
                $this->ecode = ERECV;
                return array();
            }
            $keys[] = $this->_retstr($kbuf);
        }
        return $keys;
    }

    /**
     * Add an integer to a record.
     *
     */
    public function addint ($key, $num = 0) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x60);
        $sbuf = $this->_makeBuf($cmd, array((string) $key,(int) $num));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EKEEP;
            return false;
        }
        return $this->_recvint32();

    }

    /**
     * Add a real number to a record.
     *
     */
    public function adddouble ($key, $num) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $integ = floor($num);
        $fract = floor(($num - $integ) * 1000000000000);

        $cmd = pack('c*', 0xC8,0x61);
        $sbuf = $this->_makeBuf($cmd, array((string) $key, $integ, $flact));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EKEEP;
            return false;
        }
        $integ = $this->_recvint64();
        $fract = $this->_recvint64();

        //TODO:use double?
        return array($integ, $fract);

        //return $integ + $fract / 1000000000000.0;
    }

    /**
     * Call a function of the script language extension.
     *
     */
    public function ext ($name, $key = "", $value = "", $opts = 0) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x68);
        $sbuf = $this->_makeBuf($cmd, array((string) $name,(int) $opts, (string) $key, (string) $value));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        $vsiz = $this->_recvint32();
        if ($vsiz < 0) {
            $this->ecode = ERECV;
            return false;
        }
        $vbuf = $this->_recv($vsiz);
        if (!$vbuf) {
            $this->ecode = ERECV;
            return false;
        }
        return $this->_retstr($vbuf);
    }

    /**
     * sync
     *
     * Synchronize updated contents with the file and the device.%%
     *
     */
    public function sync () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x70);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;
    }

    /**
     * optimize
     *
     */
    public function optimize($param)
    {
        $cmd = pack('c*', 0xC8,0x71);
        $sbuf = $this->_makeBuf($cmd, array((string) $param));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;

    }

    /**
     * vanish
     *
     * Remove all records.
     *
     */
    public function vanish () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x72);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }
        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;
    }

    /**
     * Copy the database file.
     *
     */
    public function copy ($path) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $cmd = pack('c*', 0xC8,0x73);
        $sbuf = $this->_makeBuf($cmd, array((string) $path));

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }
        return true;

    }

    /**
     * rnum
     *
     *
     */
    public function rnum () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return 0;
        }

        $cmd = pack('c*', 0xC8,0x80);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return 0;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return 0;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return 0;
        }

        return $this->_recvint64();
    }

    /**
     * size
     *
     * Get the size of the database.
     *
     *
     */
    public function size () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return 0;
        }

        $cmd = pack('c*', 0xC8,0x81);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return 0;
        }

        $code = $this->_recvcode();
        if ($code == -1) {
            $this->ecode = ERECV;
            return 0;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return 0;
        }
        return $this->_recvint64();

    }

    /**
     * stat
     *
     * Get the status string of the database server.
     *
     */
    public function stat() {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $cmd = pack('c*', 0xC8,0x88);
        $sbuf = $this->_makeBuf($cmd);

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }

        $code = $this->_recvcode();

        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }

        if ($code != 0) {
            $this->ecode = ENOREC;
            return false;
        }

        $ssiz = $this->_recvint32();
        if ($ssiz < 0) {
            $this->ecode = ERECV;
            return false;
        }

        $sbuf = $this->_recv($ssiz);
        if (!$sbuf) {
            $this->ecode = ERECV;
            return false;
        }

        return $this->_retstr($sbuf);

    }

    /**
     * misc
     *
     * Call a versatile function for miscellaneous operations.
     *
     */
    public function misc($name, $args = array(), $opts = 0) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x90);
        $sbuf .= pack("N", strlen($name)) . pack("N", $opts) .pack("N", count($args));
        $sbuf .= $name;

        foreach ($args as $key => $value) {
            $sbuf .= pack("N", strlen($value)) . $value;
        }

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }
        $code = $this->_recvcode();
        $rnum = $this->_recvint32();

        if ($code == -1) {
            $this->ecode = ERECV;
            return false;
        }
        if ($code != 0) {
            $this->ecode = EMISC;
            return false;
        }

        $res = array();
        for ($i = 0; $i < $rnum; $i++) {
            $esiz = $this->_recvint32();

            if ($esiz < 0) {
                $this->ecode = ERECV;
                return false;
            }
            $ebuf = $this->_recv($esiz);

            if (!$ebuf) {
                $this->ecode = ERECV;
                return false;
            }
            $res[] = $this->_retstr($ebuf);
        }
        return $res;
    }

    /**
     * aliases and iterators
     */

    /**
     * Hash-compatible method.
     * Alias of `put'.
     *
     */
    public function store($key, $value) {
        return $this->put($key, $value);
    }

    /**
     * Hash-compatible method.
     * Alias of `out'.
     *
     */
    public function delete($key) {
        return $this->out($key);
    }

    /**
     * Hash-compatible method.
     * Alias of `get'.
     *
     */
    public function fetch($key) {
        return out($key);
    }

    /**
     * Hash-compatible method.
     * Check existence of a key.
     *
     */
    public function has_key($key){
        $vsiz = vsiz($key);
        if ($vsiz >= 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Hash-compatible method.
     * Check existence of a value.
     *
     */
    public function has_value($value) {
        if (!$this->iterinit()) {
            return false;
        }
        while ($tkey = $this->iternext()) {
            $tvalue = $this->get($tkey);
            if (!$tvalue) {
                break;
            }
            if ($value == $tvalue) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hash-compatible method.
     * Alias of `vanish'.
     *
     */
    public function clear() {
        return $this->vanish();
    }

    /**
     * Hash-compatible method.
     * Alias of `rnum'.
     *
     */
    public function length(){
        return $this->rnum();
    }

    /**
     * Hash-compatible method.
     * Alias of `rnum > 0'.
     *
     */
    public function is_empty(){
        $rnum = $this->rnum();
        if (empty($rnum)) {
            return true;
        } else {
            return false;
        }
    }

    /*
      # Hash-compatible method.%%
      # Iterator of pairs of the key and the value.%%
      def each
      return nil if !iterinit
      while key = iternext
      value = get(key)
      break if !value
      yield(key, value)
      end
      return nil
      end
    */

    // alias each_pair each
    /*
      # Hash-compatible method.%%
      # Iterator of the keys.%%
      def each_keys
      return nil if !iterinit
      while key = iternext
      yield(key)
      end
      return nil
      end
    */

    /*
      # Hash-compatible method.%%
      # Iterator of the values.%%
      def each_values
      return nil if !iterinit
      while key = iternext
      value = get(key)
      break if !value
      yield(value)
      end
      return nil
      end
    */

    /**
     * Hash-compatible method.
     * Get an array of all keys.
     *
     */
    public function keys(){
        $tkeys = array();
        if (!$this->iterinit()) {
            return $tkeys;
        }
        while ($key = $this->iternext()) {
            $tkeys[] = $key;
        }
        return $tkeys;
    }

    /**
     * Hash-compatible method.
     * Get an array of all keys.
     *
     */
    public function values() {
        $tvals = array();
        if (!$this->iterinit()) {
            return $tvals;
        }
        while ($key = $this->iternext()) {
            $value = $this->get($key);
            if (!$value) {
                break;
            }
            $tvals[] = $value;
        }
        return $tvals;
    }

    /**
     * private methods
     */
    //private
    /**
     * Get a string argument.
     def _argstr(obj)
     case obj
     when Numeric
     obj = obj.to_s
     when Symbol
     obj = obj.to_s
     when String
     else
     raise ArgumentError
     end
     if obj.respond_to?(:force_encoding)
     obj = obj.dup
     obj.force_encoding("ASCII-8BIT")
     end
     return obj
     end
    */

    /**
     * Get a numeric argument.
     def _argnum(obj)
     case obj
     when String
     obj = obj.to_i
     when Numeric
     else
     raise ArgumentError
     end
     return obj
     end
    */

    /**
     * Get a normalized string to be returned
     *
     */
    protected function _retstr($str) {
        if ($this->enc) {

        } else {

        }
        return $str;
    }

    /**
     * Send a series of data.
     *
     */
    protected function _send($buf) {
        $result = fwrite($this->sock, $buf);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    protected function _makeBuf($cmd, $values = array()) {
        return $cmd . $this->_makeBin($values);
    }

    private function _makeBin($values){
        $int = '';
        $str = '';

        foreach ($values as  $value) {
            if (is_array($value)) {
                $str .= $this->_makeBin($value);
                continue;
            }

            if (! is_int($value)) {
                $int .= pack('N', strlen($value));
                $str .= $value;
                continue;
            }

            $int .= pack('N', $value);
        }
        return $int . $str;
    }

    /**
     * Receive a series of data.
     *
     */
    protected function _recv($len){
        if ($len < 1) {
            return "";
        }

        if (@feof($this->sock)) {
            return false;
        }

        $result = fread($this->sock, (int) $len);

        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * Receive a byte code.
     *
     */
    protected function _recvcode() {
        $rbuf = $this->_recv(1);

        if (!$rbuf) {
            return -1;
        } else {
            $rbuf = unpack('c', $rbuf);
            return $rbuf[1];
        }
    }

    /**
     * Receive an int32 number.
     *
     */
    protected function _recvint32() {
        $result = '';
        $res = $this->_recv(4);
        $res = unpack('N', $res);
        return $res[1];
    }

    /**
     * Receive an int64 number.
     *
     */
    protected function _recvint64() {
        $result = '';
        $res = $this->_recv(8);
        $res = unpack('N*', $res);
        // TODO:
        return array($res[1], $res[2]);
    }

    /*
      # Pack an int64 value.%%
      def _packquad(num)
      high = (num / (1 << 32)).truncate
      low = num % (1 << 32)
      return [high, low].pack("NN")
      end
      end
    */

  }
?>