<?php

declare(strict_types=1);

namespace WordPress\DeepLAiProvider\Provider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\DeepLAiProvider\Metadata\DeepLModelMetadataDirectory;
use WordPress\DeepLAiProvider\Models\DeepLTextGenerationModel;

class DeepLProvider extends AbstractApiProvider
{
    public static function apiBaseUrlForKey(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        // Free API keys from DeepL include the ":fx" suffix and require the free API host.
        if (str_contains($apiKey, ':fx')) {
            return 'https://api-free.deepl.com/v2';
        }

        return 'https://api.deepl.com/v2';
    }

    protected static function baseUrl(): string
    {
        return 'https://api.deepl.com/v2';
    }

    protected static function createModel(ModelMetadata $modelMetadata, ProviderMetadata $providerMetadata): ModelInterface
    {
        foreach ($modelMetadata->getSupportedCapabilities() as $capability) {
            if ($capability->isTextGeneration()) {
                return new DeepLTextGenerationModel($modelMetadata, $providerMetadata);
            }
        }

        throw new \WordPress\AiClient\Common\Exception\RuntimeException(
            'Unsupported DeepL model capabilities.'
        );
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        $args = [
            'deepl',
            'DeepL',
            ProviderTypeEnum::cloud(),
            'https://www.deepl.com/pro-api',
            RequestAuthenticationMethod::apiKey(),
        ];
        
        if (version_compare(AiClient::VERSION, '1.2.0', '>=')) {
            $args[] = __('Neural machine translation via DeepL API.', 'wpml-translation-check');
        }

        return new ProviderMetadata(...$args);
    }

    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new ListModelsApiBasedProviderAvailability(static::modelMetadataDirectory());
    }

    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new DeepLModelMetadataDirectory();
    }
}
