<?php
declare(strict_types=1);

namespace Alan\NeosLogin\Middleware;

use AlanCaptcha\Php\AlanApi;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Alan\NeosLogin\Service\AlanCaptchaService;
use Neos\Error\Messages as Error;

class AlanLoginMiddleware implements MiddlewareInterface
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

    /**
     * @Flow\Inject
     * @var FlashMessageService
     */
    protected $flashMessageService;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actionRequest = $request->getAttribute(ServerRequestAttributes::ACTION_REQUEST) /** @var ActionRequest $actionRequest **/;
        $isLoginRequest = $this->isLoginRequest($actionRequest);

        if (!$isLoginRequest || $this->settings['active'] !== true) {
            return $handler->handle($request);
        }

        $allArguments = array_merge($actionRequest->getArguments(), $actionRequest->getInternalArguments());

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
            }
            catch (\Exception $e) {
                // in case of more rigorous error like network connection errors
                // we assume the widget to be valid as this is more an internal server error
                // then a captcha or user error and we dont want to prevent login in case of networking issues
                $captchaSuccess = true;
                $captchaErrorCode = 0;
            }
        }

        if ($captchaSuccess || !$credentialsValid) {
            return $handler->handle($request);
        } else {
            $actionRequest->setArgument('__authentication', null);
            $actionRequest->setControllerActionName('index');
            $this->flashMessageService->getFlashMessageContainerForRequest($actionRequest)->addMessage(new Error\Error('Alan Captcha failed', $captchaErrorCode, [], 'Alan Captcha failed'));

            return $handler->handle($request->withAttribute(ServerRequestAttributes::ACTION_REQUEST, $actionRequest)->withMethod('GET'));
        }
    }

    protected function isLoginRequest(ActionRequest $actionRequest): bool
    {
        return $actionRequest->getControllerPackageKey() == 'Neos.Neos' && $actionRequest->getControllerName() == 'Login' && $actionRequest->getControllerActionName() === 'authenticate';
    }

}
