<?php
/*
 * Copyright 2015 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Auth\Credentials;

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
use Google\Auth\ServiceAccountSignerTrait;
use Google\Auth\SignBlobInterface;

/**
 * Authenticates requests using Google's Service Account credentials via
 * JWT Access.
 *
 * This class allows authorizing requests for service accounts directly
 * from credentials from a json key file downloaded from the developer
 * console (via 'Generate new Json Key').  It is not part of any OAuth2
 * flow, rather it creates a JWT and sends that as a credential.
 */
class ServiceAccountJwtAccessCredentials extends CredentialsLoader implements SignBlobInterface
{
    use ServiceAccountSignerTrait;

    /**
     * The OAuth2 instance used to conduct authorization.
     *
     * @var OAuth2
     */
    protected $auth;

    /**
     * Create a new ServiceAccountJwtAccessCredentials.
     *
     * @param string|array $jsonKey JSON credential file path or JSON credentials
     *   as an associative array
     */
    public function __construct($jsonKey)
    {
        if (is_string($jsonKey)) {
            if (!file_exists($jsonKey)) {
                throw new \InvalidArgumentException('file does not exist');
            }
            $jsonKeyStream = file_get_contents($jsonKey);
            if (!$jsonKey = json_decode($jsonKeyStream, true)) {
                throw new \LogicException('invalid json for auth config');
            }
        }
        if (!array_key_exists('client_email', $jsonKey)) {
            throw new \InvalidArgumentException(
                'json key is missing the client_email field');
        }
        if (!array_key_exists('private_key', $jsonKey)) {
            throw new \InvalidArgumentException(
                'json key is missing the private_key field');
        }
        $this->auth = new OAuth2([
            'issuer' => $jsonKey['client_email'],
            'sub' => $jsonKey['client_email'],
            'signingAlgorithm' => 'RS256',
            'signingKey' => $jsonKey['private_key'],
        ]);
    }

    /**
     * Updates metadata with the authorization token.
     *
     * @param array $metadata metadata hashmap
     * @param string $authUri optional auth uri
     * @param callable $httpHandler A callback which delivers a PSR-7 request.
     * @param array $httpOptions Configuration options provided to the
     *        underlying HTTP client.
     *
     * @return array updated metadata hashmap
     */
    public function updateMetadata(
        $metadata,
        $authUri = null,
        callable $httpHandler = null,
        array $httpOptions = []
    ) {
        if (empty($authUri)) {
            return $metadata;
        }

        $this->auth->setAudience($authUri);

        return parent::updateMetadata(
            $metadata,
            $authUri,
            $httpHandler,
            $httpOptions
        );
    }

    /**
     * Implements FetchAuthTokenInterface#fetchAuthToken.
     *
     * @param callable $httpHandler A callback which delivers a PSR-7 request.
     *        Unused by this implementation.
     * @param array $httpOptions Configuration options provided to the
     *        underlying HTTP client. Unused by this implementation.
     *
     * @return array|void A set of auth related metadata, containing the
     * following keys:
     *   - access_token (string)
     */
    public function fetchAuthToken(callable $httpHandler = null, array $httpOptions = [])
    {
        $audience = $this->auth->getAudience();
        if (empty($audience)) {
            return null;
        }

        $access_token = $this->auth->toJwt();

        return array('access_token' => $access_token);
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->auth->getCacheKey();
    }

    /**
     * @return array
     */
    public function getLastReceivedToken()
    {
        return $this->auth->getLastReceivedToken();
    }

    /**
     * Get the client name from the keyfile.
     *
     * In this case, it returns the keyfile's client_email key.
     *
     * @param callable $httpHandler A callback which delivers a PSR-7 request.
     *        Unused by this implementation.
     * @param array $httpOptions Configuration options provided to the
     *        underlying HTTP client. Unused by this implementation.
     * @return string
     */
    public function getClientName(callable $httpHandler = null, array $httpOptions = [])
    {
        return $this->auth->getIssuer();
    }
}
