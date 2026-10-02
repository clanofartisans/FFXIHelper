<?php

/**
 * Encodes/decodes the urlencoded+base64+json merits string stored on user_chars.merits.
 * Extracted from the old FFXIPH_Character::setMerits/getMeritsURLSafe.
 */
class HXI_MeritsCodec {

    /**
     * @return array<int,int> merit id => value
     */
    public static function decode(?string $meritsURLSafe): array {
        $merits = [];

        if ($meritsURLSafe === null || $meritsURLSafe === "") return $merits;

        $meritsDecoded = urldecode($meritsURLSafe);
        $meritsBase64Decoded = base64_decode($meritsDecoded);
        $meritsJSON = json_decode($meritsBase64Decoded, false);

        if (is_null($meritsJSON)) return $merits;

        // Old saved format: a 2-element array of [stats object, skills object]
        if (is_array($meritsJSON) && (is_object($meritsJSON[0] ?? null) || is_object($meritsJSON[1] ?? null))) {
            foreach ($meritsJSON[0] as $key => $value) {
                $merits[$key] = (int)$value;
            }
            foreach ($meritsJSON[1] as $key => $value) {
                $merits[$key] = (int)$value;
            }
        }
        else {
            foreach ($meritsJSON as $key => $value) {
                $merits[$key] = (int)$value;
            }
        }

        return $merits;
    }

    /**
     * @param array<int,int> $merits
     */
    public static function encode(array $merits): string {
        $meritsString = json_encode($merits);
        $meritsString = base64_encode($meritsString);
        return urlencode($meritsString);
    }
}

?>
