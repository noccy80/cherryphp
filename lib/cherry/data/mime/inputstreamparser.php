<?php

namespace Cherry\Data\Mime;

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
 * while (($file = $isp->getAttachment())) {
 *     // Save the file to /tmp/upload
 *     $isp->saveAttachment($file,"/tmp/upload/".$file->name);
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
class InputStreamParser {
    private $istream = null;
    private $ostream = null;
    private $boundary = null;
    private $itemstart = null;
    private $itemlength = null;
    private $itemctype = null;
    private $itemcdisp = null;
    public function setInputStream($stream,$boundary) {
         $this->istream = $stream;
         $this->boundary = $boundary;
    }
    
    public function getAttachmentContentDisposition() {
        return $this->itemcdisp;
    }
    public function getAttachmentContentType() {
        return $this->itemctype;
    }
    public function getAttachmentFilename() {
        if (array_key_exists("filename",$this->itemcdisp))
            return $this->itemcdisp["filename"];
        return null;
    }
    
    /**
     * Scan for the next attachment and return its temp filename
     */
    public function getAttachment() {
        \debug("InputStreamParser: getAttachment()");
        if ($this->itemstart) {
            $spos = $this->itemstart + $this->itemlength;
            \debug("InputStreamParser: Starting at %d (0x%0x)", $spos, $spos);
            fseek($this->istream,$spos,\SEEK_SET);
            // Read past the headers
                
        }
        static $bs = 8192;
        $br = 0;
        $fdata = null;
        $nbwrite = 0;
        while(true) {
            // Only read data if we had none left the last iter
            if (!$fdata) {
                \debug("InputStreamParser: Reading %d bytes",$bs);
                $fdata = fread($this->istream,$bs);
            } else {
                \debug("InputStreamParser: Recycling %d bytes",$bs);
            }
            // And if we get none, we're out of luck
            if (!$fdata)
                return null;
            // Check for the boundary
            $bstr = "--{$this->boundary}";
            $bpos = strpos($fdata,$bstr);
            if ($bpos !== false) {
                \debug("InputStreamParser: Found boundary, inspecting...");
                // is this the end delimiter?
                if (substr($fdata,$bpos+strlen($bstr),2) == "--") {
                    // End of the line
                    \debug("InputStreamParser: End delimiter found.");
                    return false;
                } else {
                    // We got a header, so save the position for the next run...
                    $this->itemstart = $br + strlen($bstr);
                    // ...and go on to extracting the stream of data from it
                    // starting with the headers which should be followed by
                    // a newline.
                    list($hdr,$fdata) = explode("\r\n\r\n",$fdata,2);
                    $hdr = explode("\r\n",$hdr);
                    array_shift($hdr);
                    foreach($hdr as $row) {
                        if (!empty($row)) {
                            list($k,$v) = explode(":",$row,2);
                            switch(strtolower($k)) {
                                case 'content-disposition':
                                    \debug("InputStreamParser: Content-Disposition = {$v}");
                                    $cdisp = explode(";",$v);
                                    $cdtype = array_shift($cdisp);
                                    $itemcdisp = [$cdtype];
                                    foreach($cdisp as $cdispattr) {
                                        list ($ak,$av) = explode ("=",$cdispattr);
                                        $itemcdisp[trim($ak)] = trim($av,"\"' ");
                                    }
                                    $this->itemcdisp = $itemcdisp;
                                    break;
                                case 'content-type':
                                    \debug("InputStreamParser: Content-Type = {$v}");
                                    $this->itemctype = trim($v);
                                    break;
                                case 'content-length':
                                    \debug("InputStreamParser: Content-Length = {$v}");
                                    // Got length
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                    // TODO: Check if the content-length is in the header and use that.
                    $writeto = tempnam(null,"mimesect");
                    $this->ostream = fopen($writeto,"wb");
                    // Now make sure we didn't get another delimiter
                    // the same datablock
                    while(true) {
                        $pend = strpos($fdata,"{$bstr}");
                        if ($pend !== false) {
                            // Trim data, write to file
                            $nbwrite += strlen($fdata);
                            $wdata = substr($fdata,0,$pend-1);
                            $this->itemlength = $nbwrite;
                            fputs($this->ostream,$wdata);
                            $fdata = substr($fdata,$pend);
                            \debug("InputStreamParser: Found next delimiter.");
                            break;
                        } else {
                            $nbwrite += strlen($fdata);
                            $wdata = $fdata;
                            $fdata = null;
                            $this->itemlength = $nbwrite;
                            fputs($this->ostream,$wdata);
                            \debug("InputStreamParser: Reading %d bytes",$bs);
                            $fdata = fgets($this->istream,$bs);
                        }
                        if (!$fdata) break;
                    }
                    // Close the stream, we are done.
                    fclose($this->ostream);
                    $this->ostream = null;
                    return $writeto;
                }
            } else {
                if ($this->ostream) {
                    fputs($this->ostream,$fdata);
                    $fdata = null;
                } else {
                    var_dump($fdata);
                    throw new \Exception("Data buffered but no output stream");
                }
            }
            $br = $br + strlen($fdata);
        }
    }
    
    public function saveAs($object,$target) {
        rename($object,$target);
    }
}
