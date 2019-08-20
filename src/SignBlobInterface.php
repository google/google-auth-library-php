<?php
/*
 * Copyright 2019 Google LLC
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

namespace Google\Auth;

/**
 * Describes a class which supports signing arbitrary strings.
 */
interface SignBlobInterface extends FetchAuthTokenInterface
{
    /**
     * Sign a string using the method which is best for a given credentials type.
     *
     * @param string $stringToSign The string to sign.
     * @param bool $forceOpenSsl Require use of OpenSSL for local signing. Does
     *        not apply to signing done using external services. **Defaults to**
     *        `false`.
     * @param callable $httpHandler A callback which delivers a PSR-7 request.
     * @param array $httpOptions Configuration options provided to the
     *        underlying HTTP client.
     * @return string The resulting signature. Value should be base64-encoded.
     */
    public function signBlob(
        $stringToSign,
        $forceOpenSsl = false,
        callable $httpHandler = null,
        array $httpOptions = []
    );

    /**
     * Returns the current Client Name.
     *
     * @param callable $httpHandler A callback which delivers a PSR-7 request.
     * @param array $httpOptions Configuration options provided to the
     *        underlying HTTP client.
     * @return string
     */
    public function getClientName(callable $httpHandler = null, array $httpOptions = []);
}
