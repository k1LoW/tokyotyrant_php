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

    /*
      # Create a query object.%%
      # `<i>rdb</i>' specifies the remote database object.%%
      # The return value is the new query object.%%
      def initialize(rdb)
      raise ArgumentError if !rdb.is_a?(TokyoTyrant::RDBTBL)
      @rdb = rdb
      @args = Array.new
      def self.setmax(max) # for backward compatibility
      setlimit(max)
      end
      end
    */
    protected $rdb = null;
    protected $args = array();

    public function __construct($rdb){
        //TODO:Refactor code
        if (!$rdb) {
            return false;
        }
        $this->rdb = $rdb;
        $this->args = array();
    }

    /*
      # Add a narrowing condition.%%
      # `<i>name</i>' specifies the name of a column.  An empty string means the primary key.%%
      # `<i>op</i>' specifies an operation type: `TokyoCabinet::RDBQRY::QCSTREQ' for string which is equal to the expression, `TokyoCabinet::RDBQRY::QCSTRINC' for string which is included in the expression, `TokyoCabinet::RDBQRY::QCSTRBW' for string which begins with the expression, `TokyoCabinet::RDBQRY::QCSTREW' for string which ends with the expression, `TokyoCabinet::RDBQRY::QCSTRAND' for string which includes all tokens in the expression, `TokyoCabinet::RDBQRY::QCSTROR' for string which includes at least one token in the expression, `TokyoCabinet::RDBQRY::QCSTROREQ' for string which is equal to at least one token in the expression, `TokyoCabinet::RDBQRY::QCSTRRX' for string which matches regular expressions of the expression, `TokyoCabinet::RDBQRY::QCNUMEQ' for number which is equal to the expression, `TokyoCabinet::RDBQRY::QCNUMGT' for number which is greater than the expression, `TokyoCabinet::RDBQRY::QCNUMGE' for number which is greater than or equal to the expression, `TokyoCabinet::RDBQRY::QCNUMLT' for number which is less than the expression, `TokyoCabinet::RDBQRY::QCNUMLE' for number which is less than or equal to the expression, `TokyoCabinet::RDBQRY::QCNUMBT' for number which is between two tokens of the expression, `TokyoCabinet::RDBQRY::QCNUMOREQ' for number which is equal to at least one token in the expression.  All operations can be flagged by bitwise-or: `TokyoCabinet::RDBQRY::QCNEGATE' for negation, `TokyoCabinet::RDBQRY::QCNOIDX' for using no index.%%
      # `<i>expr</i>' specifies an operand exression.%%
      # The return value is always `nil'.%%
      def addcond(name, op, expr)
      @args.push("addcond" + "\0" + name + "\0" + op.to_s + "\0" + expr)
      return nil
      end
    */
    public function addcond($name, $op, $expr) {
        $this->args[] = "addcond" . "\0" + $name . "\0" . (string) $op ."\0" + $expr;
        return null;
    }

    /*
      # Set the order of the result.%%
      # `<i>name</i>' specifies the name of a column.  An empty string means the primary key.%%
      # `<i>type</i>' specifies the order type: `TokyoCabinet::RDBQRY::QOSTRASC' for string ascending, `TokyoCabinet::RDBQRY::QOSTRDESC' for string descending, `TokyoCabinet::RDBQRY::QONUMASC' for number ascending, `TokyoCabinet::RDBQRY::QONUMDESC' for number descending.%%
      # The return value is always `nil'.%%
      def setorder(name, type)
      @args.push("setorder" + "\0" + name + "\0" + type.to_s)
      return nil
      end
    */
    public function setorder($name, $type){
        $this->args[] = "setorder" . "\0" . $name . "\0" + (string)$type;
        return null;
    }

    /*
      # Set the maximum number of records of the result.%%
      # `<i>max</i>' specifies the maximum number of records of the result.  If it is not defined or negative, no limit is specified.%%
      # `<i>skip</i>' specifies the maximum number of records of the result.  If it is not defined or not more than 0, no record is skipped.%%
      # The return value is always `nil'.%%
      def setlimit(max = -1, skip = -1)
      @args.push("setlimit" + "\0" + max.to_s + "\0" + skip.to_s)
      return nil
      end
    */
    public function setlimit($max = -1, $skip = -1){
        $this->args[] = "setlimit" . "\0" . (string)$max . "\0" + (string)$skip;
        return null;
    }

    /*
      # Execute the search.%%
      # The return value is an array of the primary keys of the corresponding records.  This method does never fail and return an empty array even if no record corresponds.%%
      def search()
      rv = @rdb.misc("search", @args, RDB::MONOULOG)
      return rv ? rv : Array.new
      end
    */
    public function search(){
        $rv = $this->rdb->misc("search", $this->args, TokyoTyrant_RDB::MONOULOG);
        return ($rv) ? $rv : array();
    }

    /*
      # Remove each corresponding record.%%
      # If successful, the return value is true, else, it is false.%%
      def searchout()
      args = Array.new(@args)
      args.push("out")
      rv = @rdb.misc("search", args, 0)
      return rv ? true : false
      end
    */
    public function searchout(){
        $args = $this->args;
        $args[] = "out";
        $rv = $this->rdb->misc("search", $args, 0);
        return ($rv) ? true : false;
    }

    /*
      # Get records corresponding to the search.%%
      # `<i>names</i>' specifies an array of column names to be fetched.  An empty string means the primary key.  If it is not defined, every column is fetched.%%
      # The return value is an array of column hashes of the corresponding records.  This method does never fail and return an empty list even if no record corresponds.%%
      # Due to the protocol restriction, this method can not handle records with binary columns including the "\0" chracter.%%
      def searchget(names = nil)
      raise ArgumentError if names && !names.is_a?(Array)
      args = Array.new(@args)
      if names
      args.push("get\0" + names.join("\0"))
      else
      args.push("get")
      end
      rv = @rdb.misc("search", args, RDB::MONOULOG)
      return Array.new if !rv
      for i in 0...rv.size
      cols = Hash.new
      cary = rv[i].split("\0")
      cnum = cary.size - 1
      j = 0
      while j < cnum
      cols[cary[j]] = cary[j+1]
      j += 2
      end
      rv[i] = cols
      end
      return rv
      end
    */
    public function searchget($names = null) {
        if (!is_array($names)) {
            return false;
        }
        $args = array();
        if (!empty($names)) {
            $args[] = "get\0" + join($names,"\0");
        } else {
            $args[] = "get";
        }
        $rv = $this->rdb->misc("search", $args, TokyoTyrant_RDB::MONOULOG);
        if (!$rv) {
            return array();
        }
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

    /*
      # Get the count of corresponding records.%%
      # The return value is the count of corresponding records or 0 on failure.%%
      def searchcount()
      args = Array.new(@args)
      args.push("count")
      rv = @rdb.misc("search", args, RDB::MONOULOG)
      return rv ? rv[0].to_i : 0
      end
    */
    public function searchcount() {
        $args = $this->args;
        $args[] = "count";
        $rv = $this->rdb->misc("search", $args, TokyoTyrant_RDB::MONOULOG);
        return ($rv) ? (int)$rv[0] : 0;
    }

  }
