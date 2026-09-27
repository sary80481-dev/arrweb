<?php
// app/helpers/HttpClient.php — request HTTP (cURL) yang balikin JSON array

class HttpClient
{
    private const TIMEOUT = 15;

    public static function postForm(string $url, array $data, array $headers = []): array
    {
        return self::send($url, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ], $headers),
        ]);
    }

    public static function getJson(string $url, array $headers = []): array
    {
        return self::send($url, [
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ]);
    }

    private static function send(string $url, array $options): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return is_string($response) ? (json_decode($response, true) ?: []) : [];
    }
}
