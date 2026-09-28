<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\DeepL\Authentication;

use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * DeepL Pro API authentication (Authorization: DeepL-Auth-Key).
 *
 * DeepL uses a custom Authorization scheme rather than Bearer tokens.
 */
class DeepLApiKeyRequestAuthentication extends ApiKeyRequestAuthentication
{
    public function authenticateRequest(Request $request): Request
    {
        return $request->withHeader('Authorization', 'DeepL-Auth-Key ' . $this->getApiKey());
    }
}

