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
    //public
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
     *
     * # Create a remote database object.%%
     * # The return value is the new remote database object.%%
     * def initialize()
     * @ecode = ESUCCESS
     * @enc = nil
     * @sock = nil
     * end
     */
    public function __construct() {
        $this->ecode = ESUCCESS;
        $this->enc = null;
        $this->sock = null;
    }

    /**
     * errmsg
     *
     * # Get the message string corresponding to an error code.%%
     * # `<i>ecode</i>' specifies the error code.  If it is not defined or negative, the last happened error code is specified.%%
     * # The return value is the message string of the error code.%%
     * def errmsg(ecode = nil)
     * ecode = @ecode if !ecode
     * if ecode == ESUCCESS
     * return "success"
     * elsif ecode == EINVALID
     * return "invalid operation"
     * elsif ecode == ENOHOST
     * return "host not found"
     * elsif ecode == EREFUSED
     * return "connection refused"
     * elsif ecode == ESEND
     * return "send error"
     * elsif ecode == ERECV
     * return "recv error"
     * elsif ecode == EKEEP
     * return "existing record"
     * elsif ecode == ENOREC
     * return "no record found"
     * elsif ecode == EMISC
     * return "miscellaneous error"
     * end
     * return "unknown"
     * end
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
     * # Get the last happened error code.%%
     * # The return value is the last happened error code.%%
     * # The following error code is defined: `TokyoTyrant::RDB::ESUCCESS' for success, `TokyoTyrant::RDB::EINVALID' for invalid operation, `TokyoTyrant::RDB::ENOHOST' for host not found, `TokyoTyrant::RDB::EREFUSED' for connection refused, `TokyoTyrant::RDB::ESEND' for send error, `TokyoTyrant::RDB::ERECV' for recv error, `TokyoTyrant::RDB::EKEEP' for existing record, `TokyoTyrant::RDB::ENOREC' for no record found, `TokyoTyrant::RDB::EMISC' for miscellaneous error.%%
     * def ecode()
     * return @ecode
     * end
     *
     * return Last Error Code
     */
    public function ecode() {
        return $this->ecode;
    }

    /**
     * open
     * # Open a remote database connection.%%
     * # `<i>host</i>' specifies the name or the address of the server.%%
     * # `<i>port</i>' specifies the port number.  If it is not defined or not more than 0, UNIX domain socket is used and the path of the socket file is specified by the host parameter.%%
     * # If successful, the return value is true, else, it is false.%%
     * def open(host, port = 0)
     * host = _argstr(host)
     * port = _argnum(port)
     * if @sock
     * @ecode = EINVALID
     * return false
     * end
     * if port > 0
     * begin
     * info = TCPSocket.gethostbyname(host)
     * rescue Exception
     * @ecode = ENOHOST
     * return false
     * end
     * begin
     * sock = TCPSocket.open(info[3], port)
     * rescue Exception
     * @ecode = EREFUSED
     * return false
     * end
     * begin
     * sock.setsockopt(Socket::IPPROTO_TCP, Socket::TCP_NODELAY, true)
     * rescue Exception
     * end
     * else
     * begin
     * sock = UNIXSocket.open(host)
     * rescue Exception
     * @ecode = EREFUSED
     * return false
     * end
     * end
     * if sock.respond_to?(:set_encoding)
     * sock.set_encoding("ASCII-8BIT")
     * end
     * @sock = sock
     * return true
     * end
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

    /*
      # Close the database connection.%%
      # If successful, the return value is true, else, it is false.%%
      def close()
      if !@sock
      @ecode = EINVALID
      return false
      end
      begin
      @sock.close
      rescue Exception
      @ecode = EMISC
      @sock = nil
      return false
      end
      @sock = nil
      return true
      end
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

    /*
      # Store a record.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>value</i>' specifies the value.%%
      # If successful, the return value is true, else, it is false.%%
      # If a record with the same key exists in the database, it is overwritten.%%
      def put(key, value)
      key = _argstr(key)
      value = _argstr(value)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x10, key.length, value.length].pack("CCNN")
      sbuf += key + value
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Store a new record.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>value</i>' specifies the value.%%
      # If successful, the return value is true, else, it is false.%%
      # If a record with the same key exists in the database, this method has no effect.%%
      def putkeep(key, value)
      key = _argstr(key)
      value = _argstr(value)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x11, key.length, value.length].pack("CCNN")
      sbuf += key + value
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EKEEP
      return false
      end
      return true
      end
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

    /*
      # Concatenate a value at the end of the existing record.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>value</i>' specifies the value.%%
      # If successful, the return value is true, else, it is false.%%
      # If there is no corresponding record, a new record is created.%%
      def putcat(key, value)
      key = _argstr(key)
      value = _argstr(value)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x12, key.length, value.length].pack("CCNN")
      sbuf += key + value
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Concatenate a value at the end of the existing record and shift it to the left.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>value</i>' specifies the value.%%
      # `<i>width</i>' specifies the width of the record.%%
      # If successful, the return value is true, else, it is false.%%
      # If there is no corresponding record, a new record is created.%%
      def putshl(key, value, width = 0)
      key = _argstr(key)
      value = _argstr(value)
      width = _argnum(width)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x13, key.length, value.length, width].pack("CCNNN")
      sbuf += key + value
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Store a record without response from the server.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>value</i>' specifies the value.%%
      # If successful, the return value is true, else, it is false.%%
      # If a record with the same key exists in the database, it is overwritten.%%
      def putnr(key, value)
      key = _argstr(key)
      value = _argstr(value)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x18, key.length, value.length].pack("CCNN")
      sbuf += key + value
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      return true
      end
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

    /*
      # Remove a record.%%
      # `<i>key</i>' specifies the key.%%
      # If successful, the return value is true, else, it is false.%%
      def out(key)
      key = _argstr(key)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x20, key.length].pack("CCN")
      sbuf += key
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = ENOREC
      return false
      end
      return true
      end
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

    /*
      # Retrieve a record.%%
      # `<i>key</i>' specifies the key.%%
      # If successful, the return value is the value of the corresponding record.  `nil' is returned if no record corresponds.%%
      def get(key)
      key = _argstr(key)
      sbuf = [0xC8, 0x30, key.length].pack("CCN")
      sbuf += key
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = ENOREC
      return nil
      end
      vsiz = _recvint32
      if vsiz < 0
      @ecode = ERECV
      return nil
      end
      vbuf = _recv(vsiz)
      if !vbuf
      @ecode = ERECV
      return nil
      end
      return _retstr(vbuf)
      end
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

    /*
      # Retrieve records.%%
      # `<i>recs</i>' specifies a hash containing the retrieval keys.  As a result of this method, keys existing in the database have the corresponding values and keys not existing in the database are removed.%%
      # If successful, the return value is the number of retrieved records or -1 on failure.%%
      def mget(recs)
      raise ArgumentError if !recs.is_a?(Hash)
      if !@sock
      @ecode = EINVALID
      return -1
      end
      rnum = 0
      sbuf = ""
      recs.each_pair do |key, value|
      key = _argstr(key)
      sbuf += [key.length].pack("N") + key
      rnum += 1
      end
      sbuf = [0xC8, 0x31, rnum].pack("CCN") + sbuf
      if !_send(sbuf)
      @ecode = ESEND
      return -1
      end
      code = _recvcode
      rnum = _recvint32
      if code == -1
      @ecode = ERECV
      return -1
      end
      if code != 0
      @ecode = ENOREC
      return -1
      end
      if rnum < 0
      @ecode = ERECV
      return -1
      end
      recs.clear
      for i in 1..rnum
      ksiz = _recvint32()
      vsiz = _recvint32()
      if ksiz < 0 || vsiz < 0
      @ecode = ERECV
      return -1
      end
      kbuf = _recv(ksiz)
      vbuf = _recv(vsiz)
      if !kbuf || !vbuf
      @ecode = ERECV
      return -1
      end
      recs[kbuf] = _retstr(vbuf)
      end
      return rnum
      end
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

    /*
      # Get the size of the value of a record.%%
      # `<i>key</i>' specifies the key.%%
      # If successful, the return value is the size of the value of the corresponding record, else, it is -1.%%
      def vsiz(key)
      key = _argstr(key)
      if !@sock
      @ecode = EINVALID
      return -1
      end
      sbuf = [0xC8, 0x38, key.length].pack("CCN")
      sbuf += key
      if !_send(sbuf)
      @ecode = ESEND
      return -1
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return -1
      end
      if code != 0
      @ecode = ENOREC
      return -1
      end
      return _recvint32
      end
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

    /*
      # Initialize the iterator.%%
      # If successful, the return value is true, else, it is false.%%
      # The iterator is used in order to access the key of every record stored in a database.%%
      def iterinit()
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x50].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Get the next key of the iterator.%%
      # If successful, the return value is the next key, else, it is `nil'.  `nil' is returned when no record is to be get out of the iterator.%%
      # It is possible to access every record by iteration of calling this method.  It is allowed to update or remove records whose keys are fetched while the iteration.  However, it is not assured if updating the database is occurred while the iteration.  Besides, the order of this traversal access method is arbitrary, so it is not assured that the order of storing matches the one of the traversal access.%%
      def iternext()
      if !@sock
      @ecode = EINVALID
      return nil
      end
      sbuf = [0xC8, 0x51].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = ENOREC
      return nil
      end
      vsiz = _recvint32
      if vsiz < 0
      @ecode = ERECV
      return nil
      end
      vbuf = _recv(vsiz)
      if !vbuf
      @ecode = ERECV
      return nil
      end
      return _retstr(vbuf)
      end
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

    /*
      # Get forward matching keys.%%
      # `<i>prefix</i>' specifies the prefix of the corresponding keys.%%
      # `<i>max</i>' specifies the maximum number of keys to be fetched.  If it is not defined or negative, no limit is specified.%%
      # The return value is an array of the keys of the corresponding records.  This method does never fail and return an empty array even if no record corresponds.%%
      # Note that this method may be very slow because every key in the database is scanned.%%
      def fwmkeys(prefix, max = -1)
      prefix = _argstr(prefix)
      max = _argnum(max)
      if !@sock
      @ecode = EINVALID
      return Array.new
      end
      sbuf = [0xC8, 0x58, prefix.length, max].pack("CCNN")
      sbuf += prefix
      if !_send(sbuf)
      @ecode = ESEND
      return Array.new
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return Array.new
      end
      if code != 0
      @ecode = ENOREC
      return Array.new
      end
      knum = _recvint32
      if knum < 0
      @ecode = ERECV
      return Array.new
      end
      keys = Array.new
      for i in 1..knum
      ksiz = _recvint32()
      if ksiz < 0
      @ecode = ERECV
      return Array.new
      end
      kbuf = _recv(ksiz)
      if !kbuf
      @ecode = ERECV
      return Array.new
      end
      keys.push(_retstr(kbuf))
      end
      return keys
      end
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

    /*
      # Add an integer to a record.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>num</i>' specifies the additional value.  If it is not defined, 0 is specified.%%
      # If successful, the return value is the summation value, else, it is `nil'.%%
      # If the corresponding record exists, the value is treated as an integer and is added to.  If no record corresponds, a new record of the additional value is stored.  Because records are stored in binary format, they should be processed with the `unpack' function with the `i' operator after retrieval.%%
      def addint(key, num = 0)
      key = _argstr(key)
      num = _argnum(num)
      if !@sock
      @ecode = EINVALID
      return nil
      end
      sbuf = [0xC8, 0x60, key.length, num].pack("CCNN")
      sbuf += key
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = EKEEP
      return nil
      end
      return _recvint32
      end
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

    /*
      # Add a real number to a record.%%
      # `<i>key</i>' specifies the key.%%
      # `<i>num</i>' specifies the additional value.  If it is not defined, 0 is specified.%%
      # If successful, the return value is the summation value, else, it is `nil'.%%
      # If the corresponding record exists, the value is treated as a real number and is added to.  If no record corresponds, a new record of the additional value is stored.  Because records are stored in binary format, they should be processed with the `unpack' function with the `d' operator after retrieval.%%
      def adddouble(key, num)
      key = _argstr(key)
      num = _argnum(num)
      if !@sock
      @ecode = EINVALID
      return nil
      end
      integ = num.truncate
      fract = ((num - integ) * 1000000000000).truncate
      sbuf = [0xC8, 0x61, key.length].pack("CCN")
      sbuf += _packquad(integ) + _packquad(fract) + key
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = EKEEP
      return nil
      end
      integ = _recvint64()
      fract = _recvint64()
      return integ + fract / 1000000000000.0
      end
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

    /*
      # Call a function of the script language extension.%%
      # `<i>name</i>' specifies the function name.%%
      # `<i>key</i>' specifies the key.  If it is not defined, an empty string is specified.%%
      # `<i>value</i>' specifies the value.  If it is not defined, an empty string is specified.%%
      # `<i>opts</i>' specifies options by bitwise-or: `TokyoTyrant::RDB::XOLCKREC' for record locking, `TokyoTyrant::RDB::XOLCKGLB' for global locking.  If it is not defined, no option is specified.%%
      # If successful, the return value is the value of the response or `nil' on failure.%%
      def ext(name, key = "", value = "", opts = 0)
      name = _argstr(name)
      key = _argstr(key)
      value = _argstr(value)
      opts = _argnum(opts)
      if !@sock
      @ecode = EINVALID
      return nil
      end
      sbuf = [0xC8, 0x68, name.length, opts, key.length, value.length].pack("CCNNNN")
      sbuf += name + key + value
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = EMISC
      return nil
      end
      vsiz = _recvint32
      if vsiz < 0
      @ecode = ERECV
      return nil
      end
      vbuf = _recv(vsiz)
      if !vbuf
      @ecode = ERECV
      return nil
      end
      return _retstr(vbuf)
      end
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

    /*
      # Synchronize updated contents with the file and the device.%%
      # If successful, the return value is true, else, it is false.%%
      def sync()
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x70].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Remove all records.%%
      # If successful, the return value is true, else, it is false.%%
      def vanish()
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x71].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Copy the database file.%%
      # `<i>path</i>' specifies the path of the destination file.  If it begins with `@', the trailing substring is executed as a command line.%%
      # If successful, the return value is true, else, it is false.  False is returned if the executed command returns non-zero code.%%
      # The database file is assured to be kept synchronized and not modified while the copying or executing operation is in progress.  So, this method is useful to create a backup file of the database file.%%
      def copy(path)
      path = _argstr(path)
      if !@sock
      @ecode = EINVALID
      return false
      end
      sbuf = [0xC8, 0x72, path.length].pack("CCN")
      sbuf += path
      if !_send(sbuf)
      @ecode = ESEND
      return false
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return false
      end
      if code != 0
      @ecode = EMISC
      return false
      end
      return true
      end
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

    /*
      # Get the number of records.%%
      # The return value is the number of records or 0 if the object does not connect to any database server.%%
      def rnum()
      if !@sock
      @ecode = EINVALID
      return 0
      end
      sbuf = [0xC8, 0x80].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return 0
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return 0
      end
      if code != 0
      @ecode = EMISC
      return 0
      end
      return _recvint64
      end
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

    /*
      # Get the size of the database.%%
      # The return value is the size of the database or 0 if the object does not connect to any database server.%%
      def size()
      if !@sock
      @ecode = EINVALID
      return 0
      end
      sbuf = [0xC8, 0x81].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return 0
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return 0
      end
      if code != 0
      @ecode = EMISC
      return 0
      end
      return _recvint64
      end
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

    /*
      # Get the status string of the database server.%%
      # The return value is the status message of the database or `nil' if the object does not connect to any database server.  The message format is TSV.  The first field of each line means the parameter name and the second field means the value.%%
      def stat()
      if !@sock
      @ecode = EINVALID
      return nil
      end
      sbuf = [0xC8, 0x88].pack("CC")
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = ENOREC
      return nil
      end
      ssiz = _recvint32
      if ssiz < 0
      @ecode = ERECV
      return nil
      end
      sbuf = _recv(ssiz)
      if !sbuf
      @ecode = ERECV
      return nil
      end
      return _retstr(sbuf)
      end
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

    /*
      # Call a versatile function for miscellaneous operations.%%
      # `<i>name</i>' specifies the name of the function.  All databases support "putlist", "outlist", and "getlist".  "putlist" is to store records.  It receives keys and values one after the other, and returns an empty list.  "outlist" is to remove records.  It receives keys, and returns an empty array.  "getlist" is to retrieve records.  It receives keys, and returns keys and values of corresponding records one after the other.  Table database supports "setindex", "search", and "genuid".%%
      # `<i>args</i>' specifies an array containing arguments.  If it is not defined, no argument is specified.%%
      # `<i>opts</i>' specifies options by bitwise-or: `TokyoTyrant::RDB::MONOULOG' for omission of the update log.  If it is not defined, no option is specified.%%
      # If successful, the return value is an array of the result.  `nil' is returned on failure.%%
      def misc(name, args = [], opts = 0)
      name = _argstr(name)
      args = Array.new if !args.is_a?(Array)
      opts = _argnum(opts)
      if !@sock
      @ecode = EINVALID
      return nil
      end
      sbuf = [0xC8, 0x90, name.length, opts, args.size].pack("CCNNN")
      sbuf += name
      args.each do |arg|
      arg = _argstr(arg)
      sbuf += [arg.length].pack("N") + arg
      end
      if !_send(sbuf)
      @ecode = ESEND
      return nil
      end
      code = _recvcode
      rnum = _recvint32
      if code == -1
      @ecode = ERECV
      return nil
      end
      if code != 0
      @ecode = EMISC
      return nil
      end
      res = Array.new
      for i in 1..rnum
      esiz = _recvint32
      if esiz < 0
      @ecode = ERECV
      return nil
      end
      ebuf = _recv(esiz)
      if !ebuf
      @ecode = ERECV
      return nil
      end
      res.push(_retstr(ebuf))
      end
      return res
      end
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
    //public
    /*
      # Hash-compatible method.%%
      # Alias of `put'.%%
      def store(key, value)
      return put(key, value)
      end
    */
    public function store($key, $value) {
        return $this->put($key, $value);
    }

    /*
      # Hash-compatible method.%%
      # Alias of `out'.%%
      def delete(key)
      return out(key)
      end
    */
    public function delete($key) {
        return $this->out($key);
    }

    /*
      # Hash-compatible method.%%
      # Alias of `get'.%%
      def fetch(key)
      return out(key)
      end
    */
    public function fetch($key) {
        return out($key);
    }

    /*
      # Hash-compatible method.%%
      # Check existence of a key.%%
      def has_key?(key)
      return vsiz(key) >= 0
      end
    */
    public function has_key($key){
        $vsiz = vsiz($key);
        if ($vsiz >= 0) {
            return true;
        } else {
            return false;
        }
    }

    /*
      # Hash-compatible method.%%
      # Check existence of a value.%%
      def has_value?(value)
      return nil if !iterinit
      while tkey = iternext
      tvalue = get(tkey)
      break if !tvalue
      return true if value == tvalue
      end
      return false
      end
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

    /*
      # Hash-compatible method.%%
      # Alias of `vanish'.%%
      def clear
      return vanish
      end
    */
    public function clear() {
        return $this->vanish();
    }

    /*
      # Hash-compatible method.%%
      # Alias of `rnum'.%%
      def length
      return rnum
      end
    */
    public function length(){
        return $this->rnum();
    }

    /*
      # Hash-compatible method.%%
      # Alias of `rnum > 0'.%%
      def empty?
      return rnum > 0
      end
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
      # Alias of `put'.%%
      def []=(key, value)
      return put(key, value)
      end
    */

    /*
      # Hash-compatible method.%%
      # Alias of `get'.%%
      def [](key)
      return get(key)
      end
    */

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

    /*
      # Hash-compatible method.%%
      # Get an array of all keys.%%
      def keys
      tkeys = Array.new
      return tkeys if !iterinit
      while key = iternext
      tkeys.push(key)
      end
      return tkeys
      end
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

    /*
      # Hash-compatible method.%%
      # Get an array of all keys.%%
      def values
      tvals = Array.new
      return tvals if !iterinit
      while key = iternext
      value = get(key)
      break if !value
      tvals.push(value)
      end
      return tvals
      end
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
    /*
      # Get a string argument.%%
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

    /*
      # Get a numeric argument.%%
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

    /*
      # Get a normalized string to be returned
      def _retstr(str)
      if str.respond_to?(:force_encoding)
      if @enc
      str.force_encoding(@enc)
      #        elsif Encoding.default_internal
      #          str.force_encoding(Encoding.default_internal)
      else
      str.force_encoding("UTF-8")
      end
      end
      return str
      end
    */
    protected function _retstr($str) {
        if ($this->enc) {

        } else {

        }
        return $str;
    }

    /*
      # Send a series of data.%%
      def _send(buf)
      begin
      @sock.send(buf, 0)
      rescue Exception
      return false
      end
      return true
      end
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

    /*
      # Receive a series of data.%%
      def _recv(len)
      return "" if len < 1
      begin
      str = @sock.recv(len, 0)
      len -= str.length
      while len > 0
      tstr = @sock.recv(len, 0)
      len -= tstr.length
      str += tstr
      end
      return str
      rescue Exception
      return nil
      end
      end
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

    /*
      # Receive a byte code.%%
      def _recvcode()
      rbuf = _recv(1)
      return -1 if !rbuf
      return rbuf.unpack("C")[0]
      end
      n    */
    protected function _recvcode() {
        $rbuf = $this->_recv(1);

        if (!$rbuf) {
            return -1;
        } else {
            $rbuf = unpack('c', $rbuf);
            return $rbuf[1];
        }
    }

    /*
      # Receive an int32 number.%%
      def _recvint32()
      rbuf = _recv(4)
      return -1 if !rbuf
      num = rbuf.unpack("N")[0]
      return [num].pack("l").unpack("l")[0]
      end
    */
    protected function _recvint32() {
        $result = '';
        $res = $this->_recv(4);
        $res = unpack('N', $res);
        return $res[1];
    }

    /*
      # Receive an int64 number.%%
      def _recvint64()
      rbuf = _recv(8)
      return -1 if !rbuf
      high, low = rbuf.unpack("NN")
      num = (high << 32) + low
      return [num].pack("q").unpack("q")[0]
      end
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