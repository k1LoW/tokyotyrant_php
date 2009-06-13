<?php
  /**
   * Pure PHP interface of Tokyo Tyrant
   * Copyright (C) 2009 Kenichirou Oyama
   *
   * This package refers to tokyotyrant.rb and Net_TokyoTyrant.
   *
   * tokyotyrant.rb lisence:
   * #--
   * # Pure Ruby interface of Tokyo Cabinet
   * #                                                       Copyright (C) 2006-2008 Mikio Hirabayashi
   * #  This file is part of Tokyo Cabinet.
   * #  Tokyo Cabinet is free software; you can redistribute it and/or modify it under the terms of
   * #  the GNU Lesser General Public License as published by the Free Software Foundation; either
   * #  version 2.1 of the License or any later version.  Tokyo Cabinet is distributed in the hope
   * #  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   * #  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
   * #  License for more details.
   * #  You should have received a copy of the GNU Lesser General Public License along with Tokyo
   * #  Cabinet; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330,
   * #  Boston, MA 02111-1307 USA.
   * #++
   * URL:http://tokyocabinet.sourceforge.net/tyrantrubypkg/
   *
   * Net_TokyoTyrant lisence:
   * MIT License
   * URL:http://openpear.org/package/Net_TokyoTyrant
   *
   */
require_once('TokyoTyrant_RDB.php');

class TokyoTyrant_RDBTBL extends TokyoTyrant_RDB{
    /*
     * constants
     */

    // index type: lexical string
    const ITLEXICAL = 0;
    // index type: decimal string
    const ITDECIMAL = 1;
    // index type: optimize
    const ITOPT = 9998;
    // index type: void
    const ITVOID = 9999;
    // index type: keep existing index
    // ITKEEP = 1 << 24
    const ITKEEP = 1;

    /*
     * public methods
     */

    /**
     * put
     *
     * Store a record.
     *
     * @param String $pkey
     * @cols Array $cols
     * @return Boolean
     */
    public function put($pkey, $cols) {
        if (!is_array($cols)) {
            return false;
        }
        $args = array();
        $args[] = $pkey;
        foreach ($cols as $ckey => $cvalue) {
            $args[] = $ckey;
            $args[] = $cvalue;
        }
        $rv = $this->misc("put", $args, 0);
        //for empty array()
        return ($rv !== false) ? true : false;
    }

    /**
     * putkeep
     *
     * Store a new record.
     *
     * @param String $pkey
     * @cols Array $cols
     * @return Boolean
     */
    public function putkeep($pkey, $cols) {
        if (!is_array($cols)) {
            return false;
        }
        $args = array();
        $args[] = $pkey;
        foreach ($cols as $ckey => $cvalue) {
            $args[] = $ckey;
            $args[] = $cvalue;
        }
        $rv = $this->misc("putkeep", $args, 0);
        if ($rv !== false) {
            if ($this->ecode == EMISC) {
                $this->ecode = EKEEP;
            }
            return false;
        }
        return true;
    }

    /**
     * putcat
     *
     * Concatenate columns of the existing record.
     *
     * @param String $pkey
     * @cols Array $cols
     * @return Boolean
     */
    public function putcat($pkey, $cols) {
        if (!is_array($cols)) {
            return false;
        }
        $args = array();
        $args[] = $pkey;
        foreach ($cols as $ckey => $cvalue) {
            $args[] = $ckey;
            $args[] = $cvalue;
        }
        $rv = $this->misc("putcat", $args, 0);
        return ($rv !== false) ? true : false;
    }

    /**
     * out
     *
     * Remove a record.
     *
     * @param String $pkey
     * @return Boolean
     */
    public function out($pkey) {
        $args = array();
        $args[] = $pkey;
        $rv = $this->misc("out", $args, 0);
        if ($rv !== false) {
            if ($this->ecode == EMISC) {
                $this->ecode = ENOREC;
            }
            return false;
        }
        return true;
    }

    /**
     * get
     *
     * Retrieve a record.
     *
     * @param String $pkey
     * @return Mixed
     */
    public function get($pkey) {
        $args = array();
        $args[] = $pkey;
        $rv = $this->misc("get", $args);
        if ($rv !== false) {
            if ($this->ecode == EMISC) {
                $this->ecode = ENOREC;
            }
            return false;
        }
        $cols = array();
        $cnum = count($rv);
        $cnum -= 1;
        $i = 0;
        while ($i < $cnum) {
            $cols[$rv[$i]] = $rv[$i+1];
            $i += 2;
        }
        return $cols;
    }

    /**
     * mget
     *
     * Retrieve records.
     *
     * @param Array $recs
     * @return Integer
     */
    public function mget($recs) {
        $rv = parent::mget($recs);
        if ($rv < 0) {
            return -1;
        }
        foreach ($recs as $pkey => $value) {
            $cols = array();
            $cary = split("\0",$value);
            $cnum = count($cary) -1;
            $i = 0;
            while ($i < $cnum) {
                $cols[$cary[$i]] = $cary[$i+1];
                $i += 2;
            }
            $recs[$pkey] = $cols;
        }
        return $rv;//return $recs;
    }

    /**
     * setindex
     *
     * Set a column index.%%
     *
     * @param String $name
     * @cols String $type
     * @return Boolean
     */
    public function setindex($name, $type) {
        $args = array();
        $args[] = $name;
        $args[] = $type;
        $rv = $this->misc("setindex", $args, 0);
        //for empty array()
        return ($rv !== false) ? true : false;
    }

    /**
     * genuid
     *
     * Generate a unique ID number.
     *
     * @return Integer
     */
    public function genuid() {
        $rv = $this->misc("genuid", array(), 0);
        if (!$rv) {
            return -1;
        }
        return $rv[0];
    }
}

?>