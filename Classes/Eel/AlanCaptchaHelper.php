<?php

namespace Alan\NeosLogin\Eel;

use Alan\NeosLogin\Service\AlanCaptchaService;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class AlanCaptchaHelper implements ProtectedContextAwareInterface
{

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Alan.NeosLogin");
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var AlanCaptchaService
     */
    protected $alanCaptchaService;

    public function checkAlanLoginCredentialsValid(): bool
    {
        if (isset($this->settings['apiKey'], $this->settings['siteKey'])) {
            return $this->alanCaptchaService->validateCredentials($this->settings['apiKey'], $this->settings['siteKey']);
        }
        return false;
    }


    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
