<?php


namespace AtomicDeploy\Client;

class Json {

    /**
     * Decodes JSON and throws and exception on failure.
     *
     * @param string $contents
     * @return array
     * @throws \Exception
     */
    public static function decodeJson($contents) {
        $contents = json_decode($contents, true);
        if($code = json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
        }
        return $contents;
    }

}
