<?php

declare(strict_types=1);

namespace WordPress\DeepLAiProvider\Authentication;

use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * DeepL Pro API authentication (Authorization: DeepL-Auth-Key).
 */
class DeepLApiKeyRequestAuthentication extends ApiKeyRequestAuthentication
{
    public function authenticateRequest(Request $request): Request
    {
        return $request->withHeader('Authorization', 'DeepL-Auth-Key ' . $this->getApiKey());
    }
}
