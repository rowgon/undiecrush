<?php

declare(strict_types=1);

namespace WordPress\DeepLAiProvider\Metadata;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\DeepLAiProvider\Authentication\DeepLApiKeyRequestAuthentication;
use WordPress\DeepLAiProvider\Provider\DeepLProvider;

/**
 * Single synthetic model; validates credentials via GET /v2/usage.
 */
class DeepLModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory
{
    public const MODEL_ID = 'deepl-translate';

    public function getRequestAuthentication(): \WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface
    {
        $auth = parent::getRequestAuthentication();
        if (!$auth instanceof ApiKeyRequestAuthentication) {
            return $auth;
        }

        return new DeepLApiKeyRequestAuthentication($auth->getApiKey());
    }

    protected function sendListModelsRequest(): array
    {
        $httpTransporter = $this->getHttpTransporter();
        $auth            = $this->getRequestAuthentication();
        if (!$auth instanceof ApiKeyRequestAuthentication) {
            throw ResponseException::fromMissingData('DeepL', 'apiKey');
        }

        $apiKey = $auth->getApiKey();
        $uri    = DeepLProvider::apiBaseUrlForKey($apiKey) . '/usage';

        $request = new Request(HttpMethodEnum::GET(), $uri, ['Content-Type' => 'application/json'], null, null);
        $request = $auth->authenticateRequest($request);

        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);

        $supportedOptions = [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ];

        $meta = new ModelMetadata(
            self::MODEL_ID,
            'DeepL Translate',
            [CapabilityEnum::textGeneration()],
            $supportedOptions
        );

        return [self::MODEL_ID => $meta];
    }
}
