<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\DeepL\Models;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\DeepL\Authentication\DeepLApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\DeepL\Provider\DeepLProvider;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * DeepL is not a generative model. This implementation maps "generate text" to DeepL's /translate endpoint
 * so it can be used via the same SDK interface as other providers.
 */
class DeepLTextGenerationModel extends AbstractApiBasedModel implements TextGenerationModelInterface
{
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        $requestAuthentication = parent::getRequestAuthentication();
        if (!$requestAuthentication instanceof ApiKeyRequestAuthentication) {
            return $requestAuthentication;
        }

        return new DeepLApiKeyRequestAuthentication($requestAuthentication->getApiKey());
    }

    final public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $standardParams = $this->prepareGenerateTextParams($prompt);
        $params         = $this->buildDeepLApiPayload($prompt, $standardParams);

        $auth   = $this->getRequestAuthentication();
        $apiKey = trim($auth instanceof ApiKeyRequestAuthentication ? $auth->getApiKey() : '');
        if ($apiKey === '') {
            throw new InvalidArgumentException('DeepL text generation requires an API key.');
        }

        $request = new Request(
            HttpMethodEnum::POST(),
            DeepLProvider::apiBaseUrlForKey($apiKey) . '/translate',
            ['Content-Type' => 'application/json'],
            $params,
            $this->getRequestOptions()
        );

        $request  = $auth->authenticateRequest($request);
        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);

        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * @param list<Message> $prompt
     * @return array<string, mixed>
     */
    protected function prepareGenerateTextParams(array $prompt): array
    {
        return [
            'contents' => $this->prepareContentsParam($prompt),
        ];
    }

    /**
     * @param list<Message> $messages
     * @return list<array<string, mixed>>
     */
    protected function prepareContentsParam(array $messages): array
    {
        return array_map(
            static function (Message $message): array {
                $parts = array_values(
                    array_filter(
                        array_map(
                            static fn (MessagePart $part): array => ['text' => $part->getText()],
                            $message->getParts()
                        )
                    )
                );

                return [
                    'role'  => $message->getRole() === MessageRoleEnum::model() ? 'model' : 'user',
                    'parts' => $parts,
                ];
            },
            $messages
        );
    }

    /**
     * @param list<Message> $prompt
     * @param array<string, mixed> $standardParams
     * @return array<string, mixed>
     */
    private function buildDeepLApiPayload(array $prompt, array $standardParams): array
    {
        $promptContent = $this->promptToPlainText($prompt);
        $promptData    = is_string($promptContent) ? json_decode($promptContent, true) : null;
        if (!is_array($promptData)) {
            $promptData = [];
        }

        $payload = [
            'text'        => $promptData['text'] ?? [],
            'target_lang' => $promptData['target_lang'] ?? 'EN',
        ];

        if (isset($promptData['source_lang']) && is_string($promptData['source_lang']) && $promptData['source_lang'] !== '') {
            $payload['source_lang'] = $promptData['source_lang'];
        }
        if (isset($promptData['formality']) && is_string($promptData['formality'])) {
            $payload['formality'] = $promptData['formality'];
        }
        if (isset($promptData['context']) && is_string($promptData['context'])) {
            $payload['context'] = $promptData['context'];
        }
        if (isset($promptData['glossary_id']) && is_string($promptData['glossary_id']) && $promptData['glossary_id'] !== '') {
            $payload['glossary_id'] = $promptData['glossary_id'];
        }
        if (isset($promptData['tag_handling']) && is_string($promptData['tag_handling'])) {
            $payload['tag_handling'] = $promptData['tag_handling'];
        }
        if (isset($promptData['non_splitting_tags']) && is_array($promptData['non_splitting_tags'])) {
            $payload['non_splitting_tags'] = $promptData['non_splitting_tags'];
        }
        if (isset($promptData['outline']) && is_string($promptData['outline'])) {
            $payload['outline'] = $promptData['outline'];
        }
        if (isset($promptData['preserve_formatting']) && is_bool($promptData['preserve_formatting'])) {
            $payload['preserve_formatting'] = $promptData['preserve_formatting'] ? '1' : '0';
        }
        if (isset($promptData['split_sentences']) && is_string($promptData['split_sentences'])) {
            $payload['split_sentences'] = $promptData['split_sentences'];
        }

        return $payload;
    }

    /**
     * @param list<Message> $prompt
     */
    private function promptToPlainText(array $prompt): string
    {
        $parts = [];

        foreach ($prompt as $message) {
            if (!$message instanceof Message) {
                continue;
            }

            foreach ($message->getParts() as $part) {
                if (!$part instanceof MessagePart) {
                    continue;
                }
                if ($part->getType()->isText()) {
                    $t = $part->getText();
                    if (is_string($t) && $t !== '') {
                        $parts[] = $t;
                    }
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @phpstan-type DeepLTranslationData array{text?: string, detected_source_language?: string}
     * @phpstan-type DeepLResponseData array{translations?: list<DeepLTranslationData>}
     */
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        /** @var DeepLResponseData $responseData */
        $responseData = $response->getData();

        if (!isset($responseData['translations']) || !is_array($responseData['translations']) || $responseData['translations'] === []) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'translations');
        }

        $translatedStrings = [];
        foreach ($responseData['translations'] as $index => $translationData) {
            $translatedStrings[$index] = $translationData['text'] ?? '';
        }

        $message = new Message(MessageRoleEnum::model(), [new MessagePart(json_encode($translatedStrings, JSON_FORCE_OBJECT) ?: '{}')]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        // DeepL does not provide token usage information.
        $tokenUsage = new TokenUsage(0, 0, 0);

        // Keep any additional data (minus translations) as provider-specific metadata.
        $additionalData = $responseData;
        unset($additionalData['translations']);

        return new GenerativeAiResult(
            '',
            [$candidate],
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }
}

