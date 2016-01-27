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

    /**
     * @param string $encrypted
     * @param string $key
     * @return string
     */
    public function decrypt($encrypted, $key) {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));

        return rtrim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_128,
                hash('sha256', $key, true),
                substr($data, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC)),
                MCRYPT_MODE_CBC,
                $iv
            ),
            "\0"
        );
    }
}