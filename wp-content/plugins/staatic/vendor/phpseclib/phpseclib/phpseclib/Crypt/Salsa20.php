<?php

namespace Staatic\Vendor\phpseclib3\Crypt;

use LengthException;
use Staatic\Vendor\phpseclib3\Common\Functions\Strings;
use Staatic\Vendor\phpseclib3\Crypt\Common\StreamCipher;
use Staatic\Vendor\phpseclib3\Exception\BadDecryptionException;
use Staatic\Vendor\phpseclib3\Exception\InsufficientSetupException;
class Salsa20 extends StreamCipher
{
    protected $p1 = \false;
    protected $p2 = \false;
    protected $key_length = 32;
    const ENCRYPT = 0;
    const DECRYPT = 1;
    protected $enbuffer;
    protected $debuffer;
    protected $counter = 0;
    protected $usingGeneratedPoly1305Key = \false;
    public function usesNonce()
    {
        return \true;
    }
    public function setKey($key)
    {
        switch (strlen($key)) {
            case 16:
            case 32:
                break;
            default:
                throw new LengthException('Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes 16 or 32 are supported');
        }
        parent::setKey($key);
    }
    public function setNonce($nonce)
    {
        if (strlen($nonce) != 8) {
            throw new LengthException('Nonce of size ' . strlen($key) . ' not supported by this algorithm. Only an 64-bit nonce is supported');
        }
        $this->nonce = $nonce;
        $this->changed = \true;
        $this->setEngine();
    }
    public function setCounter($counter)
    {
        $this->counter = $counter;
        $this->setEngine();
    }
    protected function createPoly1305Key()
    {
        if ($this->nonce === \false) {
            throw new InsufficientSetupException('No nonce has been defined');
        }
        if ($this->key === \false) {
            throw new InsufficientSetupException('No key has been defined');
        }
        $c = clone $this;
        $c->setCounter(0);
        $c->usePoly1305 = \false;
        $block = $c->encrypt(str_repeat("\x00", 256));
        $this->setPoly1305Key(substr($block, 0, 32));
        if ($this->counter == 0) {
            $this->counter++;
        }
    }
    protected function setup()
    {
        if (!$this->changed) {
            return;
        }
        $this->enbuffer = $this->debuffer = ['ciphertext' => '', 'counter' => $this->counter];
        $this->changed = $this->nonIVChanged = \false;
        if ($this->nonce === \false) {
            throw new InsufficientSetupException('No nonce has been defined');
        }
        if ($this->key === \false) {
            throw new InsufficientSetupException('No key has been defined');
        }
        if ($this->usePoly1305 && !isset($this->poly1305Key)) {
            $this->usingGeneratedPoly1305Key = \true;
            $this->createPoly1305Key();
        }
        $key = $this->key;
        if (strlen($key) == 16) {
            $constant = 'expand 16-byte k';
            $key .= $key;
        } else {
            $constant = 'expand 32-byte k';
        }
        $this->p1 = substr($constant, 0, 4) . substr($key, 0, 16) . substr($constant, 4, 4) . $this->nonce . "\x00\x00\x00\x00";
        $this->p2 = substr($constant, 8, 4) . substr($key, 16, 16) . substr($constant, 12, 4);
    }
    protected function setupKey()
    {
    }
    public function encrypt($plaintext)
    {
        $ciphertext = $this->crypt($plaintext, self::ENCRYPT);
        if (isset($this->poly1305Key)) {
            $this->newtag = $this->poly1305($ciphertext);
        }
        return $ciphertext;
    }
    public function decrypt($ciphertext)
    {
        if (isset($this->poly1305Key)) {
            if ($this->oldtag === \false) {
                throw new InsufficientSetupException('Authentication Tag has not been set');
            }
            $newtag = $this->poly1305($ciphertext);
            if ($this->oldtag != substr($newtag, 0, strlen($this->oldtag))) {
                $this->oldtag = \false;
                throw new BadDecryptionException('Derived authentication tag and supplied authentication tag do not match');
            }
            $this->oldtag = \false;
        }
        return $this->crypt($ciphertext, self::DECRYPT);
    }
    protected function encryptBlock($in)
    {
    }
    protected function decryptBlock($in)
    {
    }
    private function crypt($text, $mode)
    {
        $this->setup();
        if (!$this->continuousBuffer) {
            if ($this->engine == self::ENGINE_OPENSSL) {
                $iv = pack('V', $this->counter) . $this->p2;
                return openssl_encrypt($text, $this->cipher_name_openssl, $this->key, \OPENSSL_RAW_DATA, $iv);
            }
            $i = $this->counter;
            $blocks = str_split($text, 64);
            foreach ($blocks as &$block) {
                $block ^= static::salsa20($this->p1 . pack('V', $i++) . $this->p2);
            }
            return implode('', $blocks);
        }
        if ($mode == self::ENCRYPT) {
            $buffer =& $this->enbuffer;
        } else {
            $buffer =& $this->debuffer;
        }
        if (!strlen($buffer['ciphertext'])) {
            $ciphertext = '';
        } else {
            $ciphertext = $text ^ Strings::shift($buffer['ciphertext'], strlen($text));
            $text = substr($text, strlen($ciphertext));
            if (!strlen($text)) {
                return $ciphertext;
            }
        }
        $overflow = strlen($text) % 64;
        if ($overflow) {
            $text2 = Strings::pop($text, $overflow);
            if ($this->engine == self::ENGINE_OPENSSL) {
                $iv = pack('V', $buffer['counter']) . $this->p2;
                $buffer['counter'] += (strlen($text) >> 6) + 1;
                $encrypted = openssl_encrypt($text . str_repeat("\x00", 64), $this->cipher_name_openssl, $this->key, \OPENSSL_RAW_DATA, $iv);
                $temp = Strings::pop($encrypted, 64);
            } else {
                $blocks = str_split($text, 64);
                if (strlen($text)) {
                    foreach ($blocks as &$block) {
                        $block ^= static::salsa20($this->p1 . pack('V', $buffer['counter']++) . $this->p2);
                    }
                }
                $encrypted = implode('', $blocks);
                $temp = static::salsa20($this->p1 . pack('V', $buffer['counter']++) . $this->p2);
            }
            $ciphertext .= $encrypted . ($text2 ^ $temp);
            $buffer['ciphertext'] = substr($temp, $overflow);
        } elseif (!strlen($buffer['ciphertext'])) {
            if ($this->engine == self::ENGINE_OPENSSL) {
                $iv = pack('V', $buffer['counter']) . $this->p2;
                $buffer['counter'] += strlen($text) >> 6;
                $ciphertext .= openssl_encrypt($text, $this->cipher_name_openssl, $this->key, \OPENSSL_RAW_DATA, $iv);
            } else {
                $blocks = str_split($text, 64);
                foreach ($blocks as &$block) {
                    $block ^= static::salsa20($this->p1 . pack('V', $buffer['counter']++) . $this->p2);
                }
                $ciphertext .= implode('', $blocks);
            }
        }
        return $ciphertext;
    }
    protected static function leftRotate($x, $n)
    {
        if (\PHP_INT_SIZE == 8) {
            $r1 = $x << $n;
            $r1 &= 0xffffffff;
            $r2 = ($x & 0xffffffff) >> 32 - $n;
        } else {
            $x = (int) $x;
            $r1 = $x << $n;
            $r2 = $x >> 32 - $n;
            $r2 &= (1 << $n) - 1;
        }
        return $r1 | $r2;
    }
    protected static function quarterRound(&$a, &$b, &$c, &$d)
    {
        $b ^= self::leftRotate($a + $d, 7);
        $c ^= self::leftRotate($b + $a, 9);
        $d ^= self::leftRotate($c + $b, 13);
        $a ^= self::leftRotate($d + $c, 18);
    }
    protected static function doubleRound(&$x0, &$x1, &$x2, &$x3, &$x4, &$x5, &$x6, &$x7, &$x8, &$x9, &$x10, &$x11, &$x12, &$x13, &$x14, &$x15)
    {
        static::quarterRound($x0, $x4, $x8, $x12);
        static::quarterRound($x5, $x9, $x13, $x1);
        static::quarterRound($x10, $x14, $x2, $x6);
        static::quarterRound($x15, $x3, $x7, $x11);
        static::quarterRound($x0, $x1, $x2, $x3);
        static::quarterRound($x5, $x6, $x7, $x4);
        static::quarterRound($x10, $x11, $x8, $x9);
        static::quarterRound($x15, $x12, $x13, $x14);
    }
    protected static function salsa20($x)
    {
        $z = $x = unpack('V*', $x);
        for ($i = 0; $i < 10; $i++) {
            static::doubleRound($z[1], $z[2], $z[3], $z[4], $z[5], $z[6], $z[7], $z[8], $z[9], $z[10], $z[11], $z[12], $z[13], $z[14], $z[15], $z[16]);
        }
        for ($i = 1; $i <= 16; $i++) {
            $x[$i] += $z[$i];
        }
        return pack('V*', ...$x);
    }
    protected function poly1305($ciphertext)
    {
        if (!$this->usingGeneratedPoly1305Key) {
            return parent::poly1305($this->aad . $ciphertext);
        } else {
            return parent::poly1305(self::nullPad128($this->aad) . self::nullPad128($ciphertext) . pack('V', strlen($this->aad)) . "\x00\x00\x00\x00" . pack('V', strlen($ciphertext)) . "\x00\x00\x00\x00");
        }
    }
}