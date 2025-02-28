<?php

namespace Alan\NeosLogin\Service;

use AlanCaptcha\Php\AlanApi;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class AlanCaptchaService
{

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    public function validateCredentials(string $apiKey, string $siteKey): bool
    {
        $alanApi = new AlanApi();

        $valid = true;
        $error = "Validation Error";

        try {
            $valid =  $alanApi->validateCredentials($apiKey, $siteKey, true);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if (!$valid) {
            $this->systemLogger->error("\"alan/neos-login\": AlanCaptcha credentials validation failed: " . $error);
        }

        return $valid;
    }
}
