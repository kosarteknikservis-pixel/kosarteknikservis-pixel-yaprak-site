<?php
/**
 * Lightweight MMDB Reader for PHP (No dependencies)
 * Optimized for GeoLite2-City.mmdb (28-bit records)
 */

class MMDBReader {
    private $fp;
    private $metadata;
    private $nodeCount;
    private $recordSize;
    private $searchTreeSize;
    private $ipVersion;
    private $dataSectionStart;

    public function __construct($dbFile) {
        $this->fp = fopen($dbFile, 'rb');
        if (!$this->fp) throw new Exception("Could not open database file");
        $this->loadMetadata();
    }

    private function loadMetadata() {
        $fileSize = filesize(stream_get_meta_data($this->fp)['uri']);
        // Metadata marker calculation
        fseek($this->fp, $fileSize - 128 * 1024);
        $buffer = fread($this->fp, 128 * 1024);
        $marker = "\xAB\xCD\xEFMaxMind.com";
        $pos = strrpos($buffer, $marker);
        if ($pos === false) throw new Exception("Metadata marker not found");
        
        $metadataPos = ($fileSize - 128 * 1024) + $pos + strlen($marker);
        fseek($this->fp, $metadataPos);
        $this->metadata = $this->decodeNode();
        
        $this->nodeCount = $this->metadata['node_count'];
        $this->recordSize = $this->metadata['record_size'];
        $this->ipVersion = $this->metadata['ip_version'];
        $this->searchTreeSize = $this->nodeCount * ($this->recordSize * 2 / 8);
        
        // Data section usually starts right after the search tree. 
        // Heuristic: Check for a 16-byte null separator
        fseek($this->fp, $this->searchTreeSize);
        $check = fread($this->fp, 16);
        if ($check === str_repeat("\x00", 16)) {
            $this->dataSectionStart = $this->searchTreeSize + 16;
        } else {
            $this->dataSectionStart = $this->searchTreeSize;
        }
    }

    public function get($ip) {
        $packed = inet_pton($ip);
        if (!$packed) return null;
        $raw = unpack('C*', $packed);
        $bytes = array_values($raw);
        $bitCount = count($bytes) * 8;
        
        $node = 0;
        // IPv4-in-IPv6 skip (96 bits of 0)
        if ($this->ipVersion == 6 && $bitCount == 32) {
            for ($i = 0; $i < 96; $i++) {
                $node = $this->readNode($node, 0);
            }
        }

        for ($i = 0; $i < $bitCount; $i++) {
            if ($node >= $this->nodeCount) break;
            $bit = ($bytes[floor($i / 8)] >> (7 - ($i % 8))) & 1;
            $node = $this->readNode($node, $bit);
        }

        if ($node >= $this->nodeCount) {
             return $this->resolveData($node);
        }
        return null; // Not found (internal node)
    }

    private function readNode($nodeNumber, $index) {
        $nodeByteSize = ($this->recordSize * 2) / 8;
        $offset = $nodeNumber * $nodeByteSize;
        fseek($this->fp, $offset);
        $bytes = fread($this->fp, $nodeByteSize);
        if (strlen($bytes) != $nodeByteSize) return 0; // Prevent invalid read

        if ($this->recordSize == 24) {
            if ($index == 0) return (ord($bytes[0]) << 16) | (ord($bytes[1]) << 8) | ord($bytes[2]);
            return (ord($bytes[3]) << 16) | (ord($bytes[4]) << 8) | ord($bytes[5]);
        } elseif ($this->recordSize == 28) {
            $middle = ord($bytes[3]);
            if ($index == 0) {
                // M-L-L-L byte order (M high nibble + L bytes)
                return (($middle & 0xF0) << 20) | (ord($bytes[0]) << 16) | (ord($bytes[1]) << 8) | ord($bytes[2]);
            } else {
                return (($middle & 0x0F) << 24) | (ord($bytes[4]) << 16) | (ord($bytes[5]) << 8) | ord($bytes[6]);
            }
        } elseif ($this->recordSize == 32) {
            if ($index == 0) return (ord($bytes[0]) << 24) | (ord($bytes[1]) << 16) | (ord($bytes[2]) << 8) | ord($bytes[3]);
            return (ord($bytes[4]) << 24) | (ord($bytes[5]) << 16) | (ord($bytes[6]) << 8) | ord($bytes[7]);
        }
        return 0;
    }

    private function resolveData($node) {
        $pointer = $node - $this->nodeCount;
        fseek($this->fp, $this->dataSectionStart + $pointer);
        return $this->decodeNode();
    }

    private function decodeNode() {
        $ctrlByte = fread($this->fp, 1);
        if ($ctrlByte === false || $ctrlByte === "") return null;
        $ctrl = ord($ctrlByte);
        $type = $ctrl >> 5;
        if ($type == 0) {
            $extByte = fread($this->fp, 1);
            $type = ord($extByte) + 7;
        }
        
        $size = $ctrl & 0x1F;
        if ($size >= 29) {
            $bytesToRead = $size - 28;
            $sBuf = fread($this->fp, $bytesToRead);
            $size = 0;
            for ($i = 0; $i < strlen($sBuf); $i++) $size = ($size << 8) | ord($sBuf[$i]);
            $size += [0, 29, 285, 65821][strlen($sBuf)];
        }

        switch ($type) {
            case 1: // Pointer
                $pSize = ($ctrl >> 3) & 0x03;
                $pBuf = fread($this->fp, $pSize + 1);
                $pVal = 0;
                for($i=0; $i<strlen($pBuf); $i++) $pVal = ($pVal << 8) | ord($pBuf[$i]);
                
                $pointer = 0;
                if ($pSize == 0) $pointer = (($ctrl & 0x07) << 8) | $pVal;
                elseif ($pSize == 1) $pointer = ((($ctrl & 0x07) << 16) | $pVal) + 2048;
                elseif ($pSize == 2) $pointer = ((($ctrl & 0x07) << 24) | $pVal) + 526336;
                else $pointer = $pVal;
                
                $oldPos = ftell($this->fp);
                fseek($this->fp, $this->dataSectionStart + $pointer);
                $res = $this->decodeNode();
                fseek($this->fp, $oldPos);
                return $res;
            case 2: // UTF8
                return ($size > 0) ? fread($this->fp, $size) : "";
            case 3: // Double
                return unpack('d', strrev(fread($this->fp, 8)))[1];
            case 5: case 6: case 9: case 10: // Integers
                $val = 0;
                if ($size > 0) {
                    $iBuf = fread($this->fp, $size);
                    for ($i = 0; $i < strlen($iBuf); $i++) $val = ($val << 8) | ord($iBuf[$i]);
                }
                return $val;
            case 7: // Map
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    $k = $this->decodeNode();
                    $v = $this->decodeNode();
                    if ($k !== null) $map[$k] = $v;
                }
                return $map;
            case 11: // Array
                $arr = [];
                for ($i = 0; $i < $size; $i++) $arr[] = $this->decodeNode();
                return $arr;
            case 4: // Bytes
                return ($size > 0) ? fread($this->fp, $size) : "";
            case 8: // Boolean
                return $size != 0;
            default: return null;
        }
    }

    public function __destruct() {
        if ($this->fp) fclose($this->fp);
    }
}
