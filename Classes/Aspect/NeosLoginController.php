<?php
declare(strict_types=1);

namespace Alan\NeosLogin\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Alan\NeosLogin\Service\AlanCaptchaService;
use AlanCaptcha\Php\AlanApi;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class NeosLoginController {

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

    /**
     * @Flow\Around("method(Neos\Neos\Controller\LoginController->authenticateAction())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function authenticateAction(JoinPointInterface $joinPoint)
    {
        if ($this->settings['active'] !== true) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $proxy = $joinPoint->getProxy(); /* @var \Neos\Neos\Controller\LoginController $proxy */

        $allArguments = array_merge($proxy->getControllerContext()->getRequest()->getArguments(), $proxy->getControllerContext()->getRequest()->getInternalArguments());

        $captchaSuccess = false;
        $captchaErrorCode = 1737820795;

        $credentialsValid = $this->alanCaptchaService->validateCredentials($this->settings['apiKey'], $this->settings['siteKey']);

        if ($credentialsValid && isset($allArguments['alan-solution']) ) {
            $alanApi = new AlanApi();

            try {
                $captchaSuccess = $alanApi->widgetValidate($this->settings['apiKey'], $allArguments["alan-solution"]);
            } catch (\InvalidArgumentException $e) {
                $captchaSuccess = false;
                $captchaErrorCode = $e->getCode();
            } catch (\JsonException $e) {
                $captchaSuccess = false;
                $captchaErrorCode = $e->getCode();
            }
        }

        if ($captchaSuccess || !$credentialsValid) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        } else {
            $proxy->addFlashMessage('AlanCaptcha validation failed', 'AlanCaptcha validation failed', \Neos\Error\Messages\Message::SEVERITY_ERROR, [], $captchaErrorCode);

            $reflectionMethod = new \ReflectionMethod($proxy, 'redirect');
            $reflectionMethod->setAccessible(true);
            return $reflectionMethod->invoke($proxy, 'index');
        }
    }

}
