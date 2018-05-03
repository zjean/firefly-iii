<?php
/**
 * BunqRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Bunq\Object\ServerPublicKey;
use Log;
use Requests;

/**
 * Class BunqRequest.
 */
abstract class BunqRequest
{
    /** @var string */
    protected $secret = '';
    /** @var ServerPublicKey */
    protected $serverPublicKey;
    /** @var string */
    private $privateKey = '';
    /** @var string */
    private $server;
    /**
     * @var array
     */
    private $upperCaseHeaders
        = [
            'x-bunq-client-response-id' => 'X-Bunq-Client-Response-Id',
            'x-bunq-client-request-id'  => 'X-Bunq-Client-Request-Id',
        ];
    /** @var string */
    private $version = 'v1';

    /**
     * BunqRequest constructor.
     */
    public function __construct()
    {
        $this->server  = (string)config('import.options.bunq.server');
        $this->version = (string)config('import.options.bunq.version');
        Log::debug(sprintf('Created new BunqRequest with server "%s" and version "%s"', $this->server, $this->version));
    }

    /**
     *
     */
    abstract public function call(): void;

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @return ServerPublicKey
     */
    public function getServerPublicKey(): ServerPublicKey
    {
        return $this->serverPublicKey;
    }

    /**
     * @param ServerPublicKey $serverPublicKey
     */
    public function setServerPublicKey(ServerPublicKey $serverPublicKey)
    {
        $this->serverPublicKey = $serverPublicKey;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param string $data
     *
     * @return string
     *
     * @throws FireflyException
     */
    protected function generateSignature(string $method, string $uri, array $headers, string $data): string
    {
        if (0 === strlen($this->privateKey)) {
            throw new FireflyException('No private key present.');
        }
        if ('get' === strtolower($method) || 'delete' === strtolower($method)) {
            $data = '';
        }
        $uri    = sprintf('/%s/%s', $this->version, $uri);
        $toSign = sprintf("%s %s\n", strtoupper($method), $uri);

        Log::debug(sprintf('Message to sign (without data): %s', $toSign));

        $headersToSign = ['Cache-Control', 'User-Agent'];
        ksort($headers);
        foreach ($headers as $name => $value) {
            if (in_array($name, $headersToSign) || 'X-Bunq-' === substr($name, 0, 7)) {
                $toSign .= sprintf("%s: %s\n", $name, $value);
            }
        }
        $toSign    .= "\n" . $data;
        $signature = '';


        openssl_sign($toSign, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        return $signature;
    }

    /**
     * @param string $key
     * @param array  $response
     *
     * @return array
     */
    protected function getArrayFromResponse(string $key, array $response): array
    {
        $result = [];
        if (isset($response['Response'])) {
            foreach ($response['Response'] as $entry) {
                $currentKey = key($entry);
                $data       = current($entry);
                if ($currentKey === $key) {
                    $result[] = $data;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        $userAgent = sprintf('FireflyIII v%s', config('firefly.version'));

        return [
            'X-Bunq-Client-Request-Id' => uniqid('FFIII', true),
            'Cache-Control'            => 'no-cache',
            'User-Agent'               => $userAgent,
            'X-Bunq-Language'          => 'en_US',
            'X-Bunq-Region'            => 'nl_NL',
            'X-Bunq-Geolocation'       => '0 0 0 0 NL',
        ];
    }

    /**
     * @param string $key
     * @param array  $response
     *
     * @return array
     */
    protected function getKeyFromResponse(string $key, array $response): array
    {
        if (isset($response['Response'])) {
            foreach ($response['Response'] as $entry) {
                $currentKey = key($entry);
                $data       = current($entry);
                if ($currentKey === $key) {
                    return $data;
                }
            }
        }

        return [];
    }

    /**
     * @param string $uri
     * @param array  $headers
     *
     * @return array
     *
     * @throws FireflyException
     */
    protected function sendSignedBunqDelete(string $uri, array $headers): array
    {
        if (0 === strlen($this->server)) {
            throw new FireflyException('No bunq server defined');
        }

        $fullUri                            = $this->makeUri($uri);
        $signature                          = $this->generateSignature('delete', $uri, $headers, '');
        $headers['X-Bunq-Client-Signature'] = $signature;

        Log::debug(sprintf('Going to send a signed bunq DELETE to %s', $fullUri));

        try {
            $response = Requests::delete($fullUri, $headers);
        } catch (Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = (int)$response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        Log::debug(sprintf('Response to DELETE %s is %s', $fullUri, $body));
        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new FireflyException(sprintf('Could not verify signature for request to "%s"', $uri));
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     *
     * @throws FireflyException
     */
    protected function sendSignedBunqGet(string $uri, array $data, array $headers): array
    {
        if (0 === strlen($this->server)) {
            throw new FireflyException('No bunq server defined');
        }

        $body                               = json_encode($data);
        $fullUri                            = $this->makeUri($uri);
        $signature                          = $this->generateSignature('get', $uri, $headers, $body);
        $headers['X-Bunq-Client-Signature'] = $signature;

        Log::debug(sprintf('Going to send a signed bunq GET to %s', $fullUri));

        try {
            $response = Requests::get($fullUri, $headers);
        } catch (Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }

        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = (int)$response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new FireflyException(sprintf('Could not verify signature for request to "%s"', $uri));
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     * @throws FireflyException
     */
    protected function sendSignedBunqPost(string $uri, array $data, array $headers): array
    {
        $body                               = json_encode($data);
        $fullUri                            = $this->makeUri($uri);
        $signature                          = $this->generateSignature('post', $uri, $headers, $body);
        $headers['X-Bunq-Client-Signature'] = $signature;

        Log::debug(sprintf('Going to send a signed bunq POST request to: %s', $fullUri), $headers);

        try {
            $response = Requests::post($fullUri, $headers, $body);
        } catch (Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }
        Log::debug('Seems to have NO exceptions in Response');
        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = (int)$response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        if (!$this->verifyServerSignature($body, $responseHeaders, $statusCode)) {
            throw new FireflyException(sprintf('Could not verify signature for request to "%s"', $uri));
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $headers
     *
     * @return array
     * @throws FireflyException
     */
    protected function sendUnsignedBunqDelete(string $uri, array $headers): array
    {
        $fullUri = $this->makeUri($uri);

        Log::debug(sprintf('Going to send a UNsigned bunq DELETE to %s', $fullUri));

        try {
            $response = Requests::delete($fullUri, $headers);
        } catch (Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }
        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = $response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        return $array;
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return array
     * @throws FireflyException
     */
    protected function sendUnsignedBunqPost(string $uri, array $data, array $headers): array
    {
        $body    = json_encode($data);
        $fullUri = $this->makeUri($uri);

        Log::debug(sprintf('Going to send an UNsigned bunq POST to: %s', $fullUri));

        try {
            $response = Requests::post($fullUri, $headers, $body);
        } catch (Exception $e) {
            return ['Error' => [0 => ['error_description' => $e->getMessage(), 'error_description_translated' => $e->getMessage()]]];
        }
        $body                        = $response->body;
        $array                       = json_decode($body, true);
        $responseHeaders             = $response->headers->getAll();
        $statusCode                  = $response->status_code;
        $array['ResponseHeaders']    = $responseHeaders;
        $array['ResponseStatusCode'] = $statusCode;

        if ($this->isErrorResponse($array)) {
            $this->throwResponseError($array);
        }

        return $array;
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function isErrorResponse(array $response): bool
    {

        $key = key($response);
        if ('Error' === $key) {
            Log::error('Response IS an error response!');

            return true;
        }
        Log::debug('Response is not an error response');

        return false;
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    private function joinHeaders(array $headers): string
    {
        $string = '';
        foreach ($headers as $header => $value) {
            $string .= $header . ': ' . trim($value) . "\n";
        }

        return $string;
    }

    /**
     * Make full API URI
     *
     * @param string $uri
     *
     * @return string
     */
    private function makeUri(string $uri): string
    {
        return 'https://' . $this->server . '/' . $this->version . '/' . $uri;
    }

    /**
     * @param array $response
     *
     * @throws FireflyException
     */
    private function throwResponseError(array $response)
    {
        $message = [];
        if (isset($response['Error'])) {
            foreach ($response['Error'] as $error) {
                $message[] = $error['error_description'];
            }
        }
        throw new FireflyException('Bunq ERROR ' . $response['ResponseStatusCode'] . ': ' . implode(', ', $message));
    }

    /**
     * @param string $body
     * @param array  $headers
     * @param int    $statusCode
     *
     * @return bool
     * @throws FireflyException
     */
    private function verifyServerSignature(string $body, array $headers, int $statusCode): bool
    {
        Log::debug('Going to verify signature for body+headers+status');
        $dataToVerify  = $statusCode . "\n";
        $verifyHeaders = [];

        // false when no public key is present
        if (null === $this->serverPublicKey) {
            Log::error('No public key present in class, so return FALSE.');

            return false;
        }
        foreach ($headers as $header => $value) {
            // skip non-bunq headers or signature
            if ('x-bunq-' !== substr($header, 0, 7) || 'x-bunq-server-signature' === $header) {
                continue;
            }
            // need to have upper case variant of header:
            if (!isset($this->upperCaseHeaders[$header])) {
                throw new FireflyException(sprintf('No upper case variant for header "%s"', $header));
            }
            $header                 = $this->upperCaseHeaders[$header];
            $verifyHeaders[$header] = $value[0];
        }
        // sort verification headers:
        ksort($verifyHeaders);

        // add them to data to sign:
        $dataToVerify .= $this->joinHeaders($verifyHeaders);
        $signature    = $headers['x-bunq-server-signature'][0];
        $dataToVerify .= "\n" . $body;
        $result       = openssl_verify($dataToVerify, base64_decode($signature), $this->serverPublicKey->getPublicKey(), OPENSSL_ALGO_SHA256);

        if (is_int($result) && $result < 1) {
            Log::error(sprintf('Result of verification is %d, return false.', $result));

            return false;
        }
        if (!is_int($result)) {
            Log::error(sprintf('Result of verification is a boolean (%d), return false.', $result));

            return false;
        }
        Log::info('Signature is a match, return true.');

        return true;
    }
}
