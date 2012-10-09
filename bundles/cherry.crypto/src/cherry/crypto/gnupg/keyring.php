<?php

namespace Cherry\Crypto\GnuPG;

/**
 * @brief GnuPG KeyRing Manager.
 *
 * Assists with importing, exporting and searching keys.
 */
class KeyRing {

    private $home = null;
    private $gpg = null;

    /**
     * @brief Constructor.
     *
     * GnuPG home directory is set to ~/.gnupg if not provided with the call.
     *
     * @param string $gnupghome The home directory of GnuPG.
     */
    public function __construct($gnupghome=null) {
        if (!$gnupghome) $gnupghome = getenv('HOME')._DS_.'.gnupg';
        putenv('GNUPGHOME='.$gnupghome);
        $this->home = $gnupghome;
        $this->gpg = new \gnupg();
    }
    
    /**
     * @brief Import a plain text key.
     *
     * This method imports a plaintext key.
     *
     * @param string $key The key to import.
     * @throws GnuPGException
     */
    public function importKey($key) {
        $ret = $this->gpg->import($key);
        if (!$ret)
            throw new GnuPGException("Key import failed: ".$this->gpg->getError());
        return true;
    }
    
    /**
     * @brief Exports a public key from the keyring.
     *
     * @param string $fingerprint The fingerprint of the key to export.
     * @return string The plain text key.
     */
    public function exportKey($fingerprint) {
        $ret = $this->gpg->export($fingerprint);
        if (!$ret)
            throw new GnuPGException("Key export failed: ".$this->gpg->getError());
        return $ret;
    }
    
    /**
     * @brief Return matching keys from the keyring.
     *
     * The results are casted to objects to allow for easier access of the various
     * properties.
     *
     * @param string $search The search string.
     * @return array The matching keys.
     */
    public function getKeys($search=null) {
        $keys = $this->gpg->keyInfo($search);
        $rkeys = array();
        foreach($keys as $key) {
            $key = (object)$key;
            foreach($key->uids as $k=>$v) {
                $key->uids[$k] = (object)$v;
            }
            foreach($key->subkeys as $k=>$v) {
                $key->subkeys[$k] = (object)$v;
            }
            $rkeys[] = $key;
        }
        return $rkeys;
    }
    
}

class GnuPGException extends \Exception {}
