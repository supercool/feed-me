<?php

/**
 * PHP 7.4-compatible port of craftcms/url-validator 1.2.0 (https://github.com/craftcms/url-validator).
 *
 * Copyright (c) Pixel & Tonic, Inc.
 * Licensed under the MIT License. See LICENSE.md in this directory.
 */

namespace craft\feedme\helpers\urlvalidator;

use Exception;

/**
 * Thrown when a URL, or any IP address it resolves to, is disallowed.
 */
class UrlValidationException extends Exception
{
}
