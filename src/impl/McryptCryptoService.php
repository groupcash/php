<?php
namespace groupcash\php\impl;

use groupcash\php\CryptoService;

class McryptCryptoService implements CryptoService {

    /**
     * @param string $plain
     * @param string $key
     * @return string
     */
    public function encrypt($plain, $key) {
        $iv = mcrypt_create_iv(
            mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC),
            MCRYPT_DEV_URANDOM
        );

        return base64_encode(
            $iv .
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_128,
                hash('sha256', $key, true),
                $plain,
                MCRYPT_MODE_CBC,
                $iv
            )
        );
    }
}