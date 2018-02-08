<?php

/**
 * Crypt Extension
 *
 * Author Jerry Shaw <jerry-shaw@live.com>
 * Author 秋水之冰 <27206617@qq.com>
 *
 * Copyright 2017 Jerry Shaw
 * Copyright 2018 秋水之冰
 *
 * This file is part of NervSys.
 *
 * NervSys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NervSys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NervSys. If not, see <http://www.gnu.org/licenses/>.
 */

namespace ext;

class crypt
{
    //Keygen class
    public static $keygen = '\ext\keygen';

    //Crypt method
    public static $method = 'AES-256-CTR';

    //OpenSSL config file path
    public static $ssl_cnf = 'D:/Programs/WebServer/Programs/PHP/extras/ssl/openssl.cnf';

    /**
     * Get AES Crypt keys
     *
     * @param string $key
     *
     * @return array
     */
    private static function aes_keys(string $key): array
    {
        //Get iv length
        $iv_len = openssl_cipher_iv_length(self::$method);

        //Parse keys from key string
        $keys = forward_static_call([self::$keygen, 'extract'], $key);

        //Correct iv when length not match
        switch ($iv_len <=> strlen($keys['iv'])) {
            case -1:
                $keys['iv'] = substr($keys['iv'], 0, $iv_len);
                break;
            case 1:
                $keys['iv'] = str_pad($keys['iv'], $iv_len, $keys['iv']);
                break;
        }

        unset($key, $iv_len);
        return $keys;
    }

    /**
     * Get RSA Key-Pairs (Public Key & Private Key)
     *
     * @return array
     */
    public static function rsa_keys(): array
    {
        $keys = ['public' => '', 'private' => ''];
        $config = ['config' => self::$ssl_cnf];

        $openssl = openssl_pkey_new($config);
        if (false === $openssl) return $keys;

        $public = openssl_pkey_get_details($openssl);

        if (false !== $public) $keys['public'] = &$public['key'];
        if (openssl_pkey_export($openssl, $private, null, $config)) $keys['private'] = &$private;

        openssl_pkey_free($openssl);

        unset($config, $openssl, $public, $private);
        return $keys;
    }

    /**
     * Encrypt string with key
     *
     * @param string $string
     * @param string $key
     *
     * @return string
     */
    public static function encrypt(string $string, string $key): string
    {
        $keys = self::aes_keys($key);

        $string = (string)openssl_encrypt($string, self::$method, $keys['key'], OPENSSL_ZERO_PADDING, $keys['iv']);

        unset($key, $keys);
        return $string;
    }

    /**
     * Decrypt string with key
     *
     * @param string $string
     * @param string $key
     *
     * @return string
     */
    public static function decrypt(string $string, string $key): string
    {
        $keys = self::aes_keys($key);

        $string = (string)openssl_decrypt($string, self::$method, $keys['key'], OPENSSL_ZERO_PADDING, $keys['iv']);

        unset($key, $keys);
        return $string;
    }

    /**
     * Get RSA Key type
     *
     * @param string $key
     *
     * @return string
     */
    private static function rsa_type(string $key): string
    {
        $start = strlen('-----BEGIN ');
        $end = strpos($key, ' KEY-----', $start);

        if (false === $end) {
            debug(__CLASS__, 'RSA Key ERROR!');
            unset($key, $start, $end);
            return '';
        }

        $type = strtolower(substr($key, $start, $end - $start));

        if (!in_array($type, ['public', 'private'], true)) {
            debug(__CLASS__, 'RSA Key NOT support!');
            unset($key, $start, $end, $type);
            return '';
        }

        unset($key, $start, $end);
        return $type;
    }

    /**
     * RSA Encrypt with PKey
     *
     * @param string $string
     * @param string $key
     *
     * @return string
     */
    public static function rsa_encrypt(string $string, string $key): string
    {
        $type = self::rsa_type($key);
        if ('' === $type) return '';

        $encrypt = 'public' === $type ? openssl_public_encrypt($string, $string, $key) : openssl_private_encrypt($string, $string, $key);
        if (!$encrypt) return '';

        $string = (string)base64_encode($string);

        unset($key, $type, $encrypt);
        return $string;
    }

    /**
     * RSA Decrypt with PKey
     *
     * @param string $string
     * @param string $key
     *
     * @return string
     */
    public static function rsa_decrypt(string $string, string $key): string
    {
        $type = self::rsa_type($key);
        if ('' === $type) return '';

        $string = (string)base64_decode($string, true);

        $decrypt = 'private' === $type ? openssl_private_decrypt($string, $string, $key) : openssl_public_decrypt($string, $string, $key);
        if (!$decrypt) return '';

        unset($key, $type, $decrypt);
        return $string;
    }

    /**
     * Hash Password
     *
     * @param string $string
     * @param string $key
     *
     * @return string
     */
    public static function hash_pwd(string $string, string $key): string
    {
        if (32 > strlen($key)) $key = str_pad($key, 32, $key);

        $noises = str_split($key, 8);

        $string = 0 === ord(substr($key, 0, 1)) & 1 ? $noises[0] . ':' . $string . ':' . $noises[2] : $noises[1] . ':' . $string . ':' . $noises[3];
        $string = substr(hash('sha1', $string), 4, 32);

        unset($key, $noises);
        return $string;
    }

    /**
     * Check Password
     *
     * @param string $input
     * @param string $key
     * @param string $hash
     *
     * @return bool
     */
    public static function check_pwd(string $input, string $key, string $hash): bool
    {
        return self::hash_pwd($input, $key) === $hash;
    }

    /**
     * Sign signature
     *
     * @param string $string
     * @param string $rsa_key
     *
     * @return string
     */
    public static function sign(string $string, string $rsa_key = ''): string
    {
        //Prepare key
        $key = forward_static_call([self::$keygen, 'create']);
        $mix = forward_static_call([self::$keygen, 'obscure'], $key);

        //Encrypt signature
        $mix = '' === $rsa_key ? (string)base64_encode($mix) : self::rsa_encrypt($mix, $rsa_key);
        $sig = '' !== $mix ? $mix . '-' . self::encrypt($string, $key) : '';

        unset($string, $rsa_key, $key, $mix);
        return $sig;
    }

    /**
     * Verify signature
     *
     * @param string $string
     * @param string $rsa_key
     *
     * @return string
     */
    public static function verify(string $string, string $rsa_key = ''): string
    {
        //Prepare signature
        if (false === strpos($string, '-')) return '';
        list($mix, $enc) = explode('-', $string, 2);

        //Rebuild crypt keys
        $mix = '' === $rsa_key ? (string)base64_decode($mix, true) : self::rsa_decrypt($mix, $rsa_key);
        $key = forward_static_call([self::$keygen, 'rebuild'], $mix);

        //Decrypt signature
        $sig = self::decrypt($enc, $key);

        unset($string, $rsa_key, $mix, $enc, $key);
        return $sig;
    }
}