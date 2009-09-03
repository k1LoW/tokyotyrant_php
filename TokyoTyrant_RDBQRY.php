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
class TokyoTyrant_RDBQRY {
    // query condition: string is equal to
    const QCSTREQ = 0;
    // query condition: string is included in
    const QCSTRINC = 1;
    // query condition: string begins with
    const QCSTRBW = 2;
    // query condition: string ends with
    const QCSTREW = 3;
    // query condition: string includes all tokens in
    const QCSTRAND = 4;
    // query condition: string includes at least one token in
    const QCSTROR = 5;
    // query condition: string is equal to at least one token in
    const QCSTROREQ = 6;
    // query condition: string matches regular expressions of
    const QCSTRRX = 7;
    // query condition: number is equal to
    const QCNUMEQ = 8;
    // query condition: number is greater than
    const QCNUMGT = 9;
    // query condition: number is greater than or equal to
    const QCNUMGE = 10;
    // query condition: number is less than
    const QCNUMLT = 11;
    // query condition: number is less than or equal to
    const QCNUMLE = 12;
    // query condition: number is between two tokens of
    const QCNUMBT = 13;
    // query condition: number is equal to at least one token in
    const QCNUMOREQ = 14;
    // query condition: full-text search with the phrase of
    const QCFTSPH = 15;
    // query condition: full-text search with all tokens in
    const QCFTSAND = 16;
    // query condition: full-text search with at least one token in
    const QCFTSOR = 17;
    // query condition: full-text search with the compound expression of
    const QCFTSEX = 18;
    // query condition: negation flag
    //const QCNEGATE = 1 << 24;
    // query condition: no index flag
    //const QCNOIDX = 1 << 25;
    // order type: string ascending
    const QOSTRASC = 0;
    // order type: string descending
    const QOSTRDESC = 1;
    // order type: number ascending
    const QONUMASC = 2;
    // order type: number descending
    const QONUMDESC = 3;
    // set operation type: union
    const MSUNION = 0;
    // set operation type: intersection
    const MSISECT = 1;
    // set operation type: difference
    const MSDIFF = 2;
    protected $rdb = null;
    protected $args = array();
    protected $hint = '';

    public function __construct($rdb){
        if (!is_a($rdb, 'TokyoTyrant_RDBTBL')) {
            return false;
        }
        $this->rdb = $rdb;
        $this->args = array('hint');
    }

    /**
     * addcond
     *
     * Add a narrowing condition.
     *
     * @param String $name
     * @param String $op
     * @param String $expr
     * @return null
     */
    public function addcond($name, $op, $expr) {
        $this->args[] = "addcond" . "\0" . $name . "\0" . (string) $op ."\0" . (String)$expr;
        return null;
    }

    /**
     * setorder
     *
     * Set the order of the result.
     *
     * @param String $name
     * @param String $type
     * @return null
     */
    public function setorder($name, $type = QOSTRASC){
        $this->args[] = "setorder" . "\0" . $name . "\0" . (string)$type;
        return null;
    }

    /**
     * setlimit
     *
     * Set the maximum number of records of the result.
     *
     * @param Integer $max
     * @param Integer $skip
     * @return null
     */
    public function setlimit($max = -1, $skip = -1){
        $this->args[] = "setlimit" . "\0" . (string)$max . "\0" . (string)$skip;
        return null;
    }

    /**
     * search
     *
     * Execute the search.
     *
     * @return Array
     */
    public function search(){
        $this->hint = '';
        $rv = $this->rdb->misc("search", $this->args, TokyoTyrant_RDB::MONOULOG);
        if (!$rv) {
            return array();
        }
        $rv = $this->_popmeta($rv);
        return $rv;
    }

    /**
     * searchout
     *
     * Remove each corresponding record.
     *
     * return Boolean
     */
    public function searchout(){
        $args = $this->args;
        $args[] = "out";
        $this->hint = "";
        $rv = $this->rdb->misc("search", $args, 0);
        if (!$rv) {
            return false;
        }
        $rv = $this->_popmeta($rv);
        return true;
    }

    /**
     * searchget
     *
     * Get records corresponding to the search.
     *
     * @param Array $names
     * @return Array
     */
    public function searchget($names = null) {
        if (!is_array($names)) {
            return false;
        }
        $args = array();
        if (!empty($names)) {
            $args[] = "get\0" . join($names,"\0");
        } else {
            $args[] = "get";
        }
        $this->hint = "";
        $rv = $this->rdb->misc("search", $args, TokyoTyrant_RDB::MONOULOG);
        if (!$rv) {
            return array();
        }
        $rv = $this->_popmeta($rv);
        $size = count($rv);
        for ($i = 0; $i < $size; $i++) {
            $cols = array();
            $cary = split("\0",$rv[$i]);
            $cnum = count($cary) - 1;
            $j = 0;
            while ($j < $cnum) {
                $cols[$cary[$j]] = $cary[$j+1];
                $j += 2;
            }
            $rv[$i] = $cols;
        }
        return $rv;
    }

    /**
     * metasearch
     *
     * @param $
     * @return
     */
    function metasearch($others, $type = MSUNION) {
        $args = $this->args;
        foreach ($others as $key => $other) {
            if (!is_a($other, 'TokyoTyrant_RDBQRY')) {
            } else {
                array_push($other,'next');
                foreach ($other->_args as $key2 => $arg) {
                    array_push($args,$arg);
                }
            }
        }
        array_push($args, "mstype\0" . (string)$type);
        $this->hint = '';
        if (!$rv) {
            return array();
        }
        $rv = $this->_popmeta($rv);
        return $rv;
    }

    protected function _args() {
        return $this->args;
    }

    private function  _popmeta($res) {
        $i = strlen($res) - 1;
        while ($i >= 0) {
            $pkey = $res[$i];
            if (preg_match('/^\0\0\[\[HINT\]\]\n/', $pkey)) {
                $this->hint = preg_replace('/^\0\0\[\[HINT\]\]\n/', "");
                array_pop($res);
            } else {
                break;
            }
            $i -= 1;
        }
        return $rv;
    }

    /**
     * searchcount
     *
     * Get the count of corresponding records.
     *
     * @return Integer
     */
    public function searchcount() {
        $args = $this->args;
        $args[] = "count";
        $this->hint = "";
        $rv = $this->rdb->misc("search", $args, TokyoTyrant_RDB::MONOULOG);
        if (!$rv) {
            return 0;
        }
        $rv = $this->_popmeta($rv);
        return count($rv) > 0 ? (int)$rv[0] : 0;
    }

  }