<?php

declare(strict_types=1);

namespace WordPress\DeepLAiProvider\Models;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
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
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\DeepLAiProvider\Authentication\DeepLApiKeyRequestAuthentication;
use WordPress\DeepLAiProvider\Provider\DeepLProvider;

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

        // Prepare standard-format params (same structure as Google).
        $standardParams = $this->prepareGenerateTextParams($prompt);

        // Convert standard format to DeepL-specific API payload.
        $params = $this->buildDeepLApiPayload($prompt, $standardParams);

        $auth = $this->getRequestAuthentication();
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

        // Add authentication credentials to the request.
        $request = $auth->authenticateRequest($request);

        // Send and process the request.
        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);

        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Prepares the given prompt and the model configuration into standard AI client parameters.
     * Uses the same format as GoogleTextGenerationModel: { contents: [{ role, parts }] }.
     *
     * @param list<Message> $prompt The prompt to generate text for.
     * @return array<string, mixed>
     */
    protected function prepareGenerateTextParams(array $prompt): array
    {
        return [
            'contents' => $this->prepareContentsParam($prompt),
        ];
    }

    /**
     * Prepares the contents parameter from the prompt array.
     * Matches GoogleTextGenerationModel::prepareContentsParam format.
     *
     * @param list<Message> $messages
     * @return list<array<string, mixed>>
     */
    protected function prepareContentsParam(array $messages): array
    {
        return array_map(
            function (Message $message): array {
                $parts = array_values(
                    array_filter(
                        array_map(
                            fn (MessagePart $part): array => ['text' => $part->getText()],
                            $message->getParts()
                        )
                    )
                );

                return [
                    'role' => $message->getRole() === MessageRoleEnum::model() ? 'model' : 'user',
                    'parts' => $parts,
                ];
            },
            $messages
        );
    }

    /**
     * Converts the standard-format params and prompt into DeepL-specific API payload.
     *
     * @param list<Message> $prompt
     * @param array<string, mixed> $standardParams
     * @return array<string, mixed>
     */
    private function buildDeepLApiPayload(array $prompt, array $standardParams): array
    {
        $prompt=is_string($prompt) ? json_decode($prompt, true) : $prompt;

        $prompt_content = $this->promptToPlainText($prompt);
        $prompt_content=is_string($prompt_content) ? json_decode($prompt_content, true) : $prompt_content;

        $payload = [
            'text'        => $prompt_content['text'],
            'target_lang' => 'hi',
        ];

        if (isset($prompt_content['target_lang']) && is_string($prompt_content['target_lang'])) {
            $payload['target_lang'] = $prompt_content['target_lang'];
        }

        if (isset($prompt_content['source_lang']) && is_string($prompt_content['source_lang']) && $prompt_content['source_lang'] !== '') {
            $payload['source_lang'] = $prompt_content['source_lang'];
        }

        if (isset($prompt_content['formality']) && is_string($prompt_content['formality'])) {
            $payload['formality'] = $prompt_content['formality'];
        }

        
        if (isset($prompt_content['context']) && is_string($prompt_content['context'])) {
            $payload['context'] = $prompt_content['context'];
        }

        if (isset($prompt_content['glossary_id']) && is_string($prompt_content['glossary_id']) && $prompt_content['glossary_id'] !== '') {
            $payload['glossary_id'] = $prompt_content['glossary_id'];
        }

        if (isset($prompt_content['tag_handling']) && is_string($prompt_content['tag_handling'])) {
            $payload['tag_handling'] = $prompt_content['tag_handling'];
        }

        if (isset($prompt_content['non_splitting_tags']) && is_array($prompt_content['non_splitting_tags'])) {
            $payload['non_splitting_tags'] = $prompt_content['non_splitting_tags'];
        }

        if (isset($prompt_content['outline']) && is_string($prompt_content['outline'])) {
            $payload['outline'] = $prompt_content['outline'];
        }

        if (isset($prompt_content['preserve_formatting']) && is_bool($prompt_content['preserve_formatting'])) {
            $payload['preserve_formatting'] = $prompt_content['preserve_formatting'] ? '1' : '0';
        }

        if (isset($prompt_content['split_sentences']) && is_string($prompt_content['split_sentences'])) {
            $payload['split_sentences'] = $prompt_content['split_sentences'];
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

        
        $translate_strings=array();
        $translation_data=array();
        
        foreach ( $responseData['translations'] as $index => $translation_data ) {
            $translate_strings[$index]=$translation_data['text'];
        }
        
        $translation_data['text']=json_encode($translate_strings, JSON_FORCE_OBJECT);
        
        $message = $this->parseResponseCandidateMessage($translation_data, $index);
        
        $candidates = new Candidate($message, FinishReasonEnum::stop());

        // DeepL does not provide token usage information.
        $tokenUsage = new TokenUsage(0, 0, 0);

        // Use translation data as provider-specific response metadata.
        $additionalData = $responseData;
        unset($additionalData['translations']);

        return new GenerativeAiResult(
            '',
            [$candidates],
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }

    /**
     * Parses a single translation from the DeepL API response into a Candidate object.
     *
     * @param DeepLTranslationData $candidateData The translation data from the API response.
     * @param int $index The index of the translation in the translations array.
     * @return Candidate The parsed candidate.
     */
    protected function parseResponseCandidateToCandidate(array $candidateData, int $index): Candidate
    {
        if (!isset($candidateData['text']) || !is_string($candidateData['text'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                "translations[{$index}].text",
                'The value must be a string.'
            );
        }

        $message = $this->parseResponseCandidateMessage($candidateData, $index);

        return new Candidate($message, FinishReasonEnum::stop());
    }

    /**
     * Parses the message from a translation result.
     *
     * @param DeepLTranslationData $messageData The translation data from the API response.
     * @param int $index The index in the translations array.
     * @return Message The parsed message.
     */
    protected function parseResponseCandidateMessage(array $messageData, int $index): Message
    {
        if (!isset($messageData['text'])) {
            throw ResponseException::fromMissingData(
                $this->providerMetadata()->getName(),
                "translations[{$index}].text"
            );
        }

        $part = $this->parseResponseCandidateMessagePart($messageData);

        return new Message(MessageRoleEnum::model(), [$part]);
    }

    /**
     * Parses a message part from a translation result.
     *
     * @param DeepLTranslationData $partData The translation data from the API response.
     * @return MessagePart The parsed message part.
     */
    protected function parseResponseCandidateMessagePart(array $partData): MessagePart
    {
        if (isset($partData['text']) && is_string($partData['text'])) {
            return new MessagePart($partData['text']);
        }

        throw new InvalidArgumentException('Translation data missing expected text field.');
    }
}
