<?php
class PHPGangsta_GoogleAuthenticator {
    public function createSecret($secretLength = 16) {
        $validChars = $this->_getBase32LookupTable();
        unset($validChars[32]);
        $secret = '';
        for ($i = 0; $i < $secretLength; $i++) {
            $secret .= $validChars[random_int(0, 31)];
        }
        return $secret;
    }

    public function getQRCodeGoogleUrl($name, $secret) {
        $urlencoded = urlencode("otpauth://totp/" . $name . "?secret=" . $secret);
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . $urlencoded;
    }

    public function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($calculatedCode == $code) {
                return true;
            }
        }
        return false;
    }

    protected function getCode($secret, $timeSlice) {
        $secretkey = base32_decode($secret);
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretkey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack("N", $hashpart)[1] & 0x7FFFFFFF;
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    protected function _getBase32LookupTable() {
        return str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=');
    }
}

function base32_decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $n = 0;
    $j = 0;
    $binary = '';
    for ($i = 0; $i < strlen($b32); $i++) {
        $n = $n << 5;
        $n = $n + strpos($alphabet, $b32[$i]);
        $j = $j + 5;
        if ($j >= 8) {
            $j = $j - 8;
            $binary .= chr(($n & (0xFF << $j)) >> $j);
        }
    }
    return $binary;
}
?>
