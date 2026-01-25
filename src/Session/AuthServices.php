<?php

namespace BicBucStriim\Session;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;
use Aura\Auth\AuthFactory;
use Aura\Auth\Service\LoginService;
use Aura\Auth\Service\LogoutService;
use Aura\Auth\Service\ResumeService;

/**
 * Login/Logout/Resume Services for Aura Auth
 * @see https://auraphp.com/packages/2.x/Auth
 */
class AuthServices
{
    protected AuthFactory $authFactory;
    protected ?AdapterInterface $adapter = null;
    protected ?LoginService $loginService = null;
    protected ?LogoutService $logoutService = null;
    protected ?ResumeService $resumeService = null;

    public function getAuthFactory(): AuthFactory
    {
        return $this->authFactory;
    }

    public function setAuthFactory(AuthFactory $authFactory): void
    {
        $this->authFactory = $authFactory;
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function setAdapter(AdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    public function getLoginService(): ?LoginService
    {
        if (!isset($this->loginService)) {
            $loginService = $this->getAuthFactory()->newLoginService($this->getAdapter());
            $this->setLoginService($loginService);
        }
        return $this->loginService;
    }

    public function setLoginService(LoginService $loginService): void
    {
        $this->loginService = $loginService;
    }

    public function getLogoutService(): ?LogoutService
    {
        if (!isset($this->logoutService)) {
            $logoutService = $this->getAuthFactory()->newLogoutService($this->getAdapter());
            $this->setLogoutService($logoutService);
        }
        return $this->logoutService;
    }

    public function setLogoutService(LogoutService $logoutService): void
    {
        $this->logoutService = $logoutService;
    }

    public function getResumeService(): ?ResumeService
    {
        if (!isset($this->resumeService)) {
            $resumeService = $this->getAuthFactory()->newResumeService($this->getAdapter());
            $this->setResumeService($resumeService);
        }
        return $this->resumeService;
    }

    public function setResumeService(ResumeService $resumeService): void
    {
        $this->resumeService = $resumeService;
    }

    public function login(Auth $auth, array $input): void
    {
        $this->getLoginService()->login($auth, $input);
    }

    public function logout(Auth $auth): void
    {
        $this->getLogoutService()->logout($auth);
    }

    public function resume(Auth $auth): void
    {
        $this->getResumeService()->resume($auth);
    }
}
