<?php

namespace Paypercut\Http;

/**
 * A minimal JSON-over-HTTPS client for the telemetry paths.
 *
 * Deliberately separate from the module's inline API calls: those log request
 * and response bodies (which would write a minted token into the store's log
 * files) and share their timeout budget with the payment paths.
 */
class Client
{
    /**
     * POST a JSON body and never throw, whatever the transport does.
     *
     * @param string      $url
     * @param array       $headers   Raw header lines.
     * @param string|null $json_body Null means send no body at all.
     * @param int         $timeout
     * @param int         $connect_timeout
     *
     * @return array status (0 == the request never completed), headers, body, duration_ms
     */
    public function postJson($url, array $headers, $json_body, $timeout, $connect_timeout)
    {
        $started = microtime(true);

        $ch = curl_init();

        if ($ch === false) {
            return self::failure($started);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$connect_timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($json_body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
        } else {
            // The mint endpoint takes no body; an empty '[]' would be rejected.
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curl_error) {
            return self::failure($started);
        }

        return array(
            'status' => $status,
            'headers' => self::parseHeaders(substr($response, 0, $header_size)),
            'body' => (string)substr($response, $header_size),
            'duration_ms' => self::elapsed($started)
        );
    }

    /**
     * One header value, case-insensitively, or '' when absent.
     */
    public static function header(array $headers, $name)
    {
        $name = strtolower($name);

        return isset($headers[$name]) ? $headers[$name] : '';
    }

    private static function parseHeaders($raw)
    {
        $headers = array();

        foreach (preg_split('/\r?\n/', (string)$raw) as $line) {
            $parts = explode(':', $line, 2);

            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return $headers;
    }

    private static function failure($started)
    {
        return array(
            'status' => 0,
            'headers' => array(),
            'body' => '',
            'duration_ms' => self::elapsed($started)
        );
    }

    private static function elapsed($started)
    {
        return (int)round((microtime(true) - $started) * 1000);
    }
}
