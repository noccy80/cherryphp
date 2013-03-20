<?php

namespace Cherry\Data\Mime;

//define("ISP_PARSER_BLOCK_SIZE",8388608); // Default: 8MB
define("ISP_PARSER_BLOCK_SIZE",4096); // 4KB

/**
 * This is an input stream parser capable of parsing and extracting content from
 * a mime multipart message. It has been designed with the goal of being able to
 * operate on large files as well as byte streams.
 *
 *
 * @code
 * // Set up the input stream and get the boundary from the request headers
 * $isp = new InputStreamParser();
 * $boundary = "..."; // Set the boundary header
 * // Bind the input stream
 * $isp->setInputStream(fopen("file.mime","rb"),$boundary);
 * // Go over all the attachments found in the stream
 * $isp->scan();
 * foreach($isp as $att) {
 *     printf("Attachment: '%s' (%s) %d bytes\n",
 *         $att->filename, $att->filetype, $att->datalen);
 *     // Do note that this is NOT WISE! You don't want to use the filename
 *     // out of the box! Sanitize it first.
 *     $fn = $chunk->filename;
 *     $isp->saveAttachment($att,"/tmp/{$fn}");
 * }
 * @endcode
 *
 * Example mime data:
 * 
 *     ------WebKitFormBoundary7szNe6wIJe9iitgg
 *     Content-Disposition: form-data; name="file"; filename="foo.txt"
 *     Content-Type: application/octet-stream
 * 
 *     ...filedata...
 *     ------WebKitFormBoundary7szNe6wIJe9iitgg--
 *     
 */
class InputStreamParser implements \IteratorAggregate {
    use \Cherry\Traits\TDebug;
    
    private $istream = null;
    private $boundary = null;
    private $chunks = [];
    
    public function __destruct() {
        if ($this->istream)
            @fclose($this->istream);
    }
    
    public function setInputStream($stream,$boundary) {
         $this->istream = $stream;
         $this->boundary = $boundary;
    }
    
    /**
     * Locate the next boundary in the file.
     */
    private function findNextBoundary($start=null) {
        assert(!empty($this->istream));
        assert(!empty($this->boundary));
        if ($start !==null) {
            $start += 1;
        } else {
            $start = 0;
        }
        $offset = $start;
        $this->debug("findNextBoundary() invoked. Starting at {$start}");
        fseek($this->istream,$start,\SEEK_SET);
        // Go over the data, ISP_PARSER_BLOCK_SIZE bytes at a time and look for
        // a valid boundary. When found, return the byte position on which the
        // boundary was found in the stream.
        while(($block = fread($this->istream, ISP_PARSER_BLOCK_SIZE))) {
            // Look for the boundary
            $match = strpos($block,"--".$this->boundary);
            if ($match === false) {
                // No match, add the length of the block to the offset and get
                // on to the next block
                $offset += strlen($block);
            } else {
                // Match found, calculate the actual byte offset...
                $bpos = $offset + $match;
                // ...and return it
                $this->debug("Found match at position {$bpos}");
                return $bpos;
            }
        }
        $this->debug("Boundary {$this->boundary} could not be found!");
        return false;
    }

    /**
     * Scan a header into an info chunk
     */
    private function scanHeader(InputStreamChunkInfo &$chunk) {
        $start = $chunk->chunkstart;
        $len = $chunk->chunklen;
        $this->debug("Scanning header at [{$start}-{$len}]");
        $skip = strlen($this->boundary) + 2; // Skip these bytes!
        fseek($this->istream,$start+$skip,\SEEK_SET);
        $header = fread($this->istream,2048);
        if (substr($header,0,2) == "--") {
            // This must be an end delimiter, so let's freak out!
            throw new \Exception("Trying to parse ending delimiter in InputStreamParser");
        }
        list($hdr,$void) = explode("\r\n\r\n",$header,2);
        $chunk->datastart = $chunk->chunkstart + strlen($hdr)+4 + $skip;
        $chunk->datalen = $chunk->chunkstart + $chunk->chunklen - $chunk->datastart;
        $hdr = explode("\r\n",$hdr);
        array_shift($hdr);
        $chunk->filename = null;
        $chunk->filetype = null;
        foreach($hdr as $row) {
            if (!empty($row)) {
                list($k,$v) = explode(":",$row,2);
                $v = trim($v);
                switch(strtolower($k)) {
                    case 'content-disposition':
                        $this->debug("Content-Disposition is '{$v}'");
                        $cdisp = explode(";",$v);
                        $cdtype = array_shift($cdisp);
                        $itemcdisp = [$cdtype];
                        foreach($cdisp as $cdispattr) {
                            list ($ak,$av) = explode ("=",$cdispattr);
                            $itemcdisp[trim($ak)] = trim($av,"\"' ");
                            if (array_key_exists("filename",$itemcdisp))
                                $chunk->filename = $itemcdisp["filename"];
                        }
                        $this->itemcdisp = $itemcdisp;
                        break;
                    case 'content-type':
                        $this->debug("Content-Type is '{$v}'");
                        $chunk->filetype = trim($v);
                        break;
                    case 'content-length':
                        $this->debug("Content-Length is '{$v}'");
                        // Got length
                        break;
                    default:
                        break;
                }
            }
        }        
        
    }

    /**
     * Scan the mime blob
     */
    public function scan() {
        $start = null;
        $blocks = [];
        $index = 0;
        // We break this in the loop, so go while true
        $this->debug("Scanning for attachments...");
        while(true) {
            $pos = $this->findNextBoundary($start);
            if ($pos === false) break;
            $blocks[$index] = new InputStreamChunkInfo($pos);
            if ($index>0) $blocks[$index-1]->chunklen = $pos - $start - 2;
            $start = $pos;
            $index += 1;
        }
        array_pop($blocks);
        $num = count($blocks);
        $this->debug("Parsing headers for {$num} chunks");
        for ($n = 0; $n < $num; $n++) {
            $this->scanHeader($blocks[$n]);
        }
        $out = [];
        foreach($blocks as $block) {
            if (!empty($block->filename))
                $out[] = $block;
        }
        $this->chunks = $out;
        return (count($blocks)>0);
    }
    
    /**
     * Save attachment defined in the chunk to target
     */
    public function saveAttachment(InputStreamChunkInfo $chunk, $target) {
        $this->debug("Writing '{$target}' from {$chunk->datastart} with length {$chunk->datalen}");
        fseek($this->istream, $chunk->datastart, \SEEK_SET);
        $bytes = $chunk->datalen;
        $fout = fopen($target,"wb+");
        while($bytes>0) {
            if ($bytes > ISP_PARSER_BLOCK_SIZE) {
                $read = ISP_PARSER_BLOCK_SIZE;
            } else {
                $read = $bytes;
            }
            $bytes -= $read;
            $data = fread($this->istream, $read);
            fwrite($fout,$data,$read);
        }
        fclose($fout);
    }
    
    public function getIterator() {
        return new \ArrayIterator($this->chunks);
    }
}

class InputStreamChunkInfo {
    public $chunkstart; // Start of chunk in file including header
    public $chunklen;   // Length of the chunk
    public $datastart;  // Start of the actual data in the chunk
    public $datalen;    // Length of data in the chunk
    public $filename;   // Name of file in the chunk
    public $filetype;   // MIME type of the file in the chunk
    public function __construct($chunkstart,$chunklen=-1) {
        $this->chunkstart = $chunkstart;
        $this->chunklen = $chunklen;
    }
}
