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
require_once('TokyoTyrant_RDB.php');

class TokyoTyrant_RDBTBL extends TokyoTyrant_RDB{
    /*
     * constants
     */
    //public
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

    /*
      # Store a record.%%
      # `<i>pkey</i>' specifies the primary key.%%
      # `<i>cols</i>' specifies a hash containing columns.%%
      # If successful, the return value is true, else, it is false.%%
      # If a record with the same key exists in the database, it is overwritten.%%
      def put(pkey, cols)
      pkey = _argstr(pkey)
      raise ArgumentError if !cols.is_a?(Hash)
      args = Array.new
      args.push(pkey)
      cols.each do |ckey, cvalue|
      args.push(ckey)
      args.push(cvalue)
      end
      rv = misc("put", args, 0)
      return rv ? true : false
      end
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
        return ($rv) ? true : false;
    }

    /*
      # Store a new record.%%
      # `<i>pkey</i>' specifies the primary key.%%
      # `<i>cols</i>' specifies a hash containing columns.%%
      # If successful, the return value is true, else, it is false.%%
      # If a record with the same key exists in the database, this method has no effect.%%
      def putkeep(pkey, cols)
      pkey = _argstr(pkey)
      raise ArgumentError if !cols.is_a?(Hash)
      args = Array.new
      args.push(pkey)
      cols.each do |ckey, cvalue|
      args.push(ckey)
      args.push(cvalue)
      end
      rv = misc("putkeep", args, 0)
      return rv ? true : false
      end
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
        return ($rv) ? true : false;
    }

    /*
      # Concatenate columns of the existing record.%%
      # `<i>pkey</i>' specifies the primary key.%%
      # `<i>cols</i>' specifies a hash containing columns.%%
      # If successful, the return value is true, else, it is false.%%
      # If there is no corresponding record, a new record is created.%%
      def putcat(pkey, cols)
      pkey = _argstr(pkey)
      raise ArgumentError if !cols.is_a?(Hash)
      args = Array.new
      args.push(pkey)
      cols.each do |ckey, cvalue|
      args.push(ckey)
      args.push(cvalue)
      end
      rv = misc("putcat", args, 0)
      return rv ? true : false
      end
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
        return ($rv) ? true : false;
    }

    /*
      # Remove a record.%%
      # `<i>pkey</i>' specifies the primary key.%%
      # If successful, the return value is true, else, it is false.%%
      def out(pkey)
      pkey = _argstr(pkey)
      return super(pkey)
      end
    */
    public function out($pkey) {
        return parent::out($pkey);
    }

    /*
      # Retrieve a record.%%
      # `<i>pkey</i>' specifies the primary key.%%
      # If successful, the return value is a hash of the columns of the corresponding record.  `nil' is returned if no record corresponds.%%
      def get(pkey)
      pkey = _argstr(pkey)
      args = Array.new
      args.push(pkey)
      rv = misc("get", args)
      return nil if !rv
      cols = Hash.new()
      cnum = rv.length
      cnum -= 1
      i = 0
      while i < cnum
      cols[rv[i]] = rv[i+1]
      i += 2
      end
      return cols
      end
    */
    public function get($pkey) {
        $args = array();
        $args[] = $pkey;
        $rv = $this->misc("get", $args);
        if (!$rv) {
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

    /*
      # Retrieve records.%%
      # `<i>recs</i>' specifies a hash containing the retrieval keys.  As a result of this method, keys existing in the database have the corresponding columns and keys not existing in the database are removed.%%
      # If successful, the return value is the number of retrieved records or -1 on failure.%%
      # Due to the protocol restriction, this method can not handle records with binary columns including the "\0" chracter.%%
      def mget(recs)
      rv = super(recs)
      return -1 if rv < 0
      recs.each do |pkey, value|
      cols = Hash.new
      cary = value.split("\0")
      cnum = cary.size - 1
      i = 0
      while i < cnum
      cols[cary[i]] = cary[i+1]
      i += 2
      end
      recs[pkey] = cols
      end
      return rv
      end
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

    /*
      # Set a column index.%%
      # `<i>name</i>' specifies the name of a column.  If the name of an existing index is specified, the index is rebuilt.  An empty string means the primary key.%%
      # `<i>type</i>' specifies the index type: `TokyoCabinet::RDBTBL::ITLEXICAL' for lexical string, `TokyoCabinet::RDBTBL::ITDECIMAL' for decimal string.  If it is `TokyoCabinet::RDBTBL::ITOPT', the index is optimized.  If it is `TokyoCabinet::RDBTBL::ITVOID', the index is removed.  If `TokyoCabinet::RDBTBL::ITKEEP' is added by bitwise-or and the index exists, this method merely returns failure.%%
      # If successful, the return value is true, else, it is false.%%
      def setindex(name, type)
      name = _argstr(name)
      type = _argnum(type)
      args = Array.new
      args.push(name)
      args.push(type)
      rv = misc("setindex", args, 0)
      return rv ? true : false
      end
    */
    public function setindex($name, $type) {
        $args = array();
        $args[] = $name;
        $args[] = $type;
        $rv = $this->misc("setindex", $args, 0);
        return ($rv) ? true : false;
    }

    /*
      # Generate a unique ID number.%%
      # The return value is the new unique ID number or -1 on failure.%%
      def genuid()
      rv = misc("genuid", Array.new, 0)
      return -1 if !rv
      return rv[0]
      end
      end
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
