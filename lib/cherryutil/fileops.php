<?php
/**
 * @namespace cherryutil\fileops
 * @brief File operation helpers for cherryutil
 *
 */
namespace cherryutil\fileops;

/**
 * @class FileOpHelper
 * @brief Actions useful for installing files
 */
class FileOpHelper {

    private $verbose = false;
    private $replace = false;
    private $con;
    
    public function __construct() {
        $this->con = \cherry\cli\Console::getAdapter();
    }

    /**
     * @brief Enable verbose mode
     *
     * @param mixed $state If true, file operations will be displayed as they happen.
     */
    public function setVerbose($state) {
        $this->verbose = ($state == true);
    }
    
    /**
     * @brief Enable replace (overwrite) mode
     *
     * @param mixed $state If true, existing destination files will be overwritten.
     */
    public function setReplace($state) {
        $this->replace = $state;
    }
    
    /**
     * @brief Create a directory with logging
     *
     * @param mixed $dest The directory to create
     * @param mixed $mode The filesystem mode (default 0777)
     */
    public function mkdir($dest,$mode=0777) {

        if ($this->verbose)
            $this->con->update("Creating directory: %s\n", $dest);
        if (!@mkdir($dest,$mode,true))
            throw new FileopException(_("Could not create directory: ".$dest));
        
    }

    /**    
     * @brief Install a single file to dest
     *
     * @param mixed $source The source file to copy from
     * @param mixed $dest The destination file to copy to
     */
    public function install($source,$dest,$mode=null) {

        $sinfo = @stat($source);
        $dinfo = @stat($dest);

        // Check the destination path and create it if it does not exist        
        $destpath = substr($dest,0,strrpos($dest,DIRECTORY_SEPARATOR));
        if (!file_exists($destpath)) {
            $this->mkdir($destpath);
        }

        // Make sure the source exists.        
        if (!$sinfo) {
            fprintf(STDERR,"File not found when copying: %s\n", $source);
            return 1;
        }
        
        // If mode is set to null, we copy the mode from the source file
        if ($mode == null) // Copy mode from source if null
            $mode = $sinfo['mode'];
        
        // Finally, we copy the file while respecting the replace flag in case
        // the file exists.
        if ($dinfo && (!$this->replace)) {
            if ($this->verbose)
                $this->con->write("Target already exists: %s\n", $dest);
        } else {
            if ($this->verbose)
                $this->con->write("Installing %s\n", $source, $dest);
            if (!@copy($source,$dest))
                throw new FileopException(_("Could not write destination file: ".$dest));
            chmod($dest,$mode);
            return true;
        }
    }

    /**    
     * @brief Install a directory including subdirectories to dest.
     *
     * @param mixed $source The source directory to copy files from
     * @param mixed $dest The destination directory to copy files to
     */
    public function installdir($source,$dest) {
        
        $in = glob($source.'/*');
        foreach($in as $src) {
            $filepart = substr($src,strlen($source));
            if (is_dir($src)) {
                $this->installdir($src,$dest.$filepart);
            } else {
                $this->install($src,$dest.$filepart);
            }
        }
        
    }
    
}

class FileopException extends \Exception { }
