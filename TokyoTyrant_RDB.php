<?php
  /**
   * Pure PHP interface of Tokyo Tyrant
   * Copyright (C) 2009 Kenichirou Oyama
   *
   * This package refers to tokyotyrant.rb and Net_TokyoTyrant.
   *
   * tokyotyrant.rb lisence:
   * #--
   * # Pure Ruby interface of Tokyo Tyrant
   * #                                                       Copyright (C) 2006-2008 Mikio Hirabayashi
   * #  This file is part of Tokyo Tyrant.
   * #  Tokyo Tyrant is free software; you can redistribute it and/or modify it under the terms of
   * #  the GNU Lesser General Public License as published by the Free Software Foundation; either
   * #  version 2.1 of the License or any later version.  Tokyo Tyrant is distributed in the hope
   * #  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   * #  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
   * #  License for more details.
   * #  You should have received a copy of the GNU Lesser General Public License along with Tokyo
   * #  Tyrant; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330,
   * #  Boston, MA 02111-1307 USA.
   * #++
   * URL:http://tokyocabinet.sourceforge.net/tyrantrubypkg/
   *
   * Net_TokyoTyrant lisence:
   * MIT License
   * URL:http://openpear.org/package/Net_TokyoTyrant
   *
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
    // XOLCKREC = 1 << 0
    const XOLCKREC = 1;
    // scripting extension option: global locking
    // XOLCKGLB = 1 << 1
    const XOLCKGLB = 2;
    // versatile function option: omission of the update log
    //const MONOULOG = 1 << 0;
    const MONOULOG = 1;

    protected $ecode;
    protected $enc;
    protected $sock;

    /**
     * __construct
     *
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
     * @return Last Error Code
     */
    public function ecode() {
        return $this->ecode;
    }

    /**
     * open
     *
     * Open a remote database connection.%%
     *
     * @param String $host
     * @param Integer $port
     * @param Integer $timeout
     * @return Boolean
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
     * close
     *
     * Close the database connection.
     *
     * @return Boolean
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
     * put
     *
     * Store a record.
     *
     * @param String $key
     * @param Strint $value
     * @return Boolean
     */
    public function put($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $sbuf = pack("CC", 0xC8, 0x10);
        $sbuf .= pack("N", strlen($key)) . pack("N", strlen($value));
        $sbuf .= $key . $value;

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
     * putkeep
     *
     * Store a new record.
     *
     * @param String $key
     * @param String $value
     * @return Boolean
     */
    public function putkeep ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x11);
        $sbuf .= pack("N", strlen($key)) . pack("N", strlen($value));
        $sbuf .= $key . $value;

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
     * putcat
     *
     * Concatenate a value at the end of the existing record.
     *
     * @param String $key
     * @param String $value
     * @return Boolean
     */
    public function putcat ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x12);
        $sbuf .= pack("N", strlen($key)) . pack("N", strlen($value));
        $sbuf .= $key . $value;

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
     * putshl
     *
     * Concatenate a value at the end of the existing record and shift it to the left.
     *
     * @param String $key
     * @param String $value
     * @return Boolean
     */
    public function putshl ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x13);
        $sbuf .= pack("N", strlen($key)) . pack("N", strlen($value));
        $sbuf .= $key . $value;

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
     * putnr
     *
     * Store a record without response from the server.
     *
     * @param String $key
     * @param String $value
     * @return Boolean
     */
    public function putnr ($key, $value) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x18);
        $sbuf .= pack("N", strlen($key)) . pack("N", strlen($value));
        $sbuf .= $key . $value;

        if (!$this->_send($sbuf)) {
            $this->ecode = ESEND;
            return false;
        }
        return true;

    }

    /**
     * out
     *
     * Remove a record.
     *
     * @param String $key
     * @return Boolean
     */
    public function out ($key) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x20);
        $sbuf .= pack("N", strlen($key));
        $sbuf .= $key;

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
     * get
     *
     * Retrieve a record.
     *
     * @param String $key
     * @return Mixed
     */
    public function get ($key) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x30);
        $sbuf .= pack("N", strlen($key));
        $sbuf .= $key;

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
     * mget
     *
     * Retrieve records.
     *
     * @param Array $recs
     * @return Mixed
     */
    public function mget ($recs) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return -1;
        }

        $rnum = 0;
        $sbuf = "";
        foreach($recs as $key) {
            $key = $this->_argstr($key);
            $sbuf .= pack("N", strlen($key)) . $key;
            $rnum += 1;
        }

        $sbuf = pack("CC", 0xC8, 0x31) . pack("N", $rnum) . $sbuf;

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
     * vsiz
     *
     * Get the size of the value of a record.
     *
     * @param String $key
     * @return Mixed
     */
    public function vsiz ($key) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x38);
        $sbuf .= pack("N", strlen($key));
        $sbuf .= $key;

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
     * iterinit
     *
     * Initialize the iterator.
     *
     * @return Mixed
     */
    public function iterinit() {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x50);

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
     * iternext
     *
     * Get the next key of the iterator.
     *
     * @return Mixed
     */
    public function iternext() {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x51);

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
     * fwmkeys
     *
     * Get forward matching keys.
     *
     * @param String $prefix
     * @param Integer $int
     * @return Array
     */
    public function fwmkeys ($prefix, $max = -1) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x58);
        $sbuf .= pack("N", strlen($prefix)) . pack("N", $max);
        $sbuf .= $prefix;

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
     * addint
     *
     * Add an integer to a record.
     *
     * @param String $key
     * @param Integer $num
     * @return Mixed
     */
    public function addint ($key, $num = 0) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x60);
        $sbuf .= pack("N", strlen($key)) . pack("N", $num);
        $sbuf .= $key;

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
     * adddouble
     *
     * Add a real number to a record.
     *
     * @param String $key
     * @param Integer $num
     * @return Mixed
     */
    public function adddouble ($key, $num) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }
        $integ = floor($num);
        $fract = floor(($num - $integ) * 1000000000000);

        /*
          $sbuf = pack("CC", 0xC8, 0x61);
          $sbuf .= pack("N", strlen($key)) . pack("N", $integ) . pack("N", $fract);
          $sbuf .= $key . $this->_packquad($integ) . $this->_packquad($fract);
        */

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

        //TODO:use float?
        //return array($integ, $fract);
        return (float) $integ + $fract / 1000000000000.0;
    }

    /**
     * ext
     *
     * Call a function of the script language extension.
     *
     * @param String $name
     * @param String $key
     * @param String $value
     * @param Integer $opts
     * @return Mixed
     */
    public function ext ($name, $key = "", $value = "", $opts = 0) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x68);
        $sbuf .= pack("N", strlen($name)) . pack("N", $opts) . pack("N", strlen($key)) . pack("N", strlen($value));
        $sbuf .= $name . $key . $value;

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

        $sbuf = pack("CC", 0xC8, 0x70);

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
     * @param String $param
     * @return Boolean
     */
    public function optimize($param) {

        $sbuf = pack("CC", 0xC8, 0x71);
        $sbuf .= pack("N", strlen($param));
        $sbuf .= $param;

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
     * @return Boolean
     */
    public function vanish () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x72);

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
     * copy
     *
     * Copy the database file.
     *
     * @param String $path
     * @return Boolean
     */
    public function copy ($path) {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x73);
        $sbuf .= pack("N", strlen($path));
        $sbuf .= $path;

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
     * @return Mixed
     */
    public function rnum () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return 0;
        }

        $sbuf = pack("CC", 0xC8, 0x80);

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
     * @return Mixed
     */
    public function size () {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return 0;
        }

        $sbuf = pack("CC", 0xC8, 0x81);

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
     * @return Mixed
     */
    public function stat() {
        if (!$this->sock) {
            $this->ecode = EINVALID;
            return false;
        }

        $sbuf = pack("CC", 0xC8, 0x88);

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
     * @param String $name
     * @param Array $args
     * @param Integer $opts
     * @return Mixed
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
     * store
     *
     * Alias of `put'.
     *
     */
    public function store($key, $value) {
        return $this->put($key, $value);
    }

    /**
     * delete
     *
     * Alias of `out'.
     *
     */
    public function delete($key) {
        return $this->out($key);
    }

    /**
     * fetch
     *
     * Alias of `get'.
     *
     */
    public function fetch($key) {
        return $this->get($key);
    }

    /**
     * has_key
     *
     * Check existence of a key.
     *
     * @param String $key
     * @return Boolean
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
     * has_value
     *
     * Check existence of a value.
     *
     * @param String $value
     * @return Boolean
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
     * clear
     *
     * Alias of `vanish'.
     *
     */
    public function clear() {
        return $this->vanish();
    }

    /**
     * length
     *
     * Alias of `rnum'.
     *
     */
    public function length(){
        return $this->rnum();
    }

    /**
     * is_empty
     *
     * Alias of `if (rnum > 0) {}'
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

    /**
     * keys
     *
     * Get an array of all keys.
     *
     * @return Array
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
     * values
     *
     * Get an array of all keys.
     *
     * @return Array
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
    /**
     * _argstr
     *
     * Get a string argument.
     *
     * @param Mixed $obj
     * @return String
     */
    private function _argstr($obj) {
        if (is_numeric($obj)) {
            $obj = (string) $obj;
        } elseif (is_string($obj)) {
        } else {
            return false;
        }
        return $obj;
    }

    /**
     * _argnum
     *
     * Get a numeric argument.
     *
     * @param $obj
     * @return Integer
     */
    private function _argnum($obj) {
        if (is_string($obj)) {
            $obj = (int) $obj;
        } elseif (is_numeric($obj)) {
        } else {
            return false;
        }
        return $obj;
    }

    /**
     * _retstr
     *
     * Get a normalized string to be returned
     *
     * @param String $str
     * @return String
     */
    private function _retstr($str) {
        if ($this->enc) {
            //TODO:encode $str
        } else {

        }
        return $str;
    }

    /**
     * _send
     *
     * Send a series of data.
     *
     * @param String $buf
     * @return Boolean
     */
    private function _send($buf) {
        $result = $this->_fullwrite($this->sock, $buf);

        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * _makeBuf
     *
     */
    private function _makeBuf($cmd, $values = array()) {
        return $cmd . $this->_makeBin($values);
    }

    /**
     * _makeBin
     *
     */
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
     * _recv
     *
     * Receive a series of data.
     *
     * @param Integer $len
     * @return Mixed
     */
    private function _recv($len){
        if ($len < 1) {
            return "";
        }

        if (@feof($this->sock)) {
            return false;
        }

        $result = $this->_fullread($this->sock, (int) $len);

        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * _recvcode
     *
     * Receive a byte code.
     *
     * @return Integer
     */
    private function _recvcode() {
        $rbuf = $this->_recv(1);

        if (!$rbuf) {
            return -1;
        } else {
            $rbuf = unpack('c', $rbuf);
            return $rbuf[1];
        }
    }

    /**
     * _recvint32
     *
     * Receive an int32 number.
     *
     * @return Integer
     */
    private function _recvint32() {
        $result = '';
        $res = $this->_recv(4);
        $res = unpack('N', $res);
        return $res[1];
    }

    /**
     * _recvint64
     *
     * Receive an int64 number.
     * but this is less-accurate function
     *
     * @return Array
     */
    private function _recvint64() {
        $result = '';
        $res = $this->_recv(8);
        $res = unpack('N*', $res);
        //return array($res[1], $res[2]);
        $s = $res[1] . $res[2];
        return (double) $s;
    }

    /**
     * _fullread
     * Read large binary data.
     * http://buyukliev.blogspot.com/2007/08/no-more-socket-trouble-in-php.html
     *
     * @return
     */
    private function _fullread ($sd, $len) {
        $ret = '';
        $read = 0;

        while ($read < $len && ($buf = fread($sd, $len - $read))) {
            $read += strlen($buf);
            $ret .= $buf;
        }

        return $ret;
    }

    /**
     * _fullread
     * Write large binary data.
     * http://buyukliev.blogspot.com/2007/08/no-more-socket-trouble-in-php.html
     *
     * @return
     */
    private function _fullwrite ($sd, $buf) {
        $total = 0;
        $len = strlen($buf);

        while ($total < $len && ($written = fwrite($sd, $buf))) {
            $total += $written;
            $buf = substr($buf, $written);
        }

        return $total;
    }

  }
?>