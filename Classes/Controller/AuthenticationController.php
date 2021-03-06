<?php

namespace Networkteam\Neos\FrontendLogin\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Error\Messages as Error;

/**
 * Controller for displaying a login/logout form and authenticating/logging out "frontend users"
 */
class AuthenticationController extends AbstractAuthenticationController
{
    /**
     * @var Translator
     * @Flow\Inject
     */
    protected $translator;

    /**
     * @Flow\InjectConfiguration(package="Networkteam.Neos.FrontendLogin", path="translation.packageKey")
     * @var string
     */
    protected $translationPackageKey;

    /**
     * @Flow\InjectConfiguration(package="Networkteam.Neos.FrontendLogin", path="translation.sourceName")
     * @var string
     */
    protected $translationSourceName;

    /**
     * @Flow\InjectConfiguration(package="Networkteam.Neos.FrontendLogin", path="redirectOnLoginLogoutExceptionUri")
     * @var string
     */
    protected $redirectOnLoginLogoutExceptionUri;

    /**
     * @var HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @Flow\SkipCsrfProtection
     */
    public function logoutAction()
    {
        parent::logoutAction();

        try {
            $redirectAfterLogoutUri = $this->hashService->validateAndStripHmac(
                $this->request->getArgument('redirectAfterLogoutUri')
            );
        } catch (\Exception $e) {
            $redirectAfterLogoutUri = $this->redirectOnLoginLogoutExceptionUri;
        }

        $this->redirectToUri($redirectAfterLogoutUri);
    }

    /**
     * @param ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
     * @return void
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null)
    {
        try {
            $redirectAfterLoginUri = $this->hashService->validateAndStripHmac(
                $this->request->getArgument('redirectAfterLoginUri')
            );
        } catch (\Exception $e) {
            $redirectAfterLoginUri = $this->redirectOnLoginLogoutExceptionUri;
        }

        $this->redirectToUri($redirectAfterLoginUri);
    }

    /**
     * Create translated FlashMessage and add it to flashMessageContainer
     *
     * @param AuthenticationRequiredException $exception
     * @return void
     */
    protected function onAuthenticationFailure(AuthenticationRequiredException $exception = null)
    {
        $this->flashMessageContainer->flush(Error\Message::SEVERITY_ERROR);

        $title = $this->getTranslationById('authentication.failure.title');
        $message = $this->getTranslationById('authentication.failure.message');
        $this->addFlashMessage($message, $title, Error\Message::SEVERITY_ERROR, [], $exception === null ? 1496914553 : $exception->getCode());
    }

    /**
     * Get translation by label id for configured source name and package key
     *
     * @param string $labelId Key to use for finding translation
     * @return string Translated message or NULL on failure
     */
    protected function getTranslationById($labelId)
    {
        return $this->translator->translateById($labelId, [], null, null, $this->translationSourceName, $this->translationPackageKey);
    }

    /**
     * Disable the technical error flash message
     *
     * @return boolean
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }
}
