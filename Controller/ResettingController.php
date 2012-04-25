<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ResettingController extends ContainerAware
{
    const SESSION_EMAIL = 'fos_user_send_resetting_email/email';

    /**
     * Request reset user password: show form
     */
    public function requestAction()
    {
        $baseLayout = $this->container->getParameter('fos_user.settings.base_layout');
        $usePageHeader = $this->container->getParameter('fos_user.settings.use_page_header');

        $templateParameters = array(
            'baseLayout' => $baseLayout,
            'usePageHeader' => $usePageHeader,
        );
        
        if(class_exists('Sonata\AdminBundle\SonataAdminBundle')) {
            $adminPool = $this->container->get('sonata.admin.pool');
            $templateParameters['admin_pool'] = $adminPool;
        }        

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), $templateParameters);
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction()
    {
        $username = $this->container->get('request')->request->get('username');
        $baseLayout = $this->container->getParameter('fos_user.settings.base_layout');
        $usePageHeader = $this->container->getParameter('fos_user.settings.use_page_header');
        $flashName = $this->container->getParameter('fos_user.settings.flash_name');

        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

	$templateParameters = array(
	    'usePageHeader' => $usePageHeader,
	    'baseLayout' => $baseLayout
	);
	
        if(class_exists('Sonata\AdminBundle\SonataAdminBundle')) {
            $adminPool = $this->container->get('sonata.admin.pool');
            $templateParameters['admin_pool'] = $adminPool;
        }        	
	
        if (null === $user){
 	    $templateParameters['invalid_username'] = $username;
	    return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), $templateParameters);
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            $this->setFlash($flashName, 'The password for this user has already been requested within the last 24 hours.');
            
	    return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), $templateParameters);
        }

        $user->generateConfirmationToken();
        $applicationTitle = $this->container->getParameter('fos_user.settings.application_title');
        $adminEmail = $this->container->getParameter('fos_user.settings.admin_email');
        
        $this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        $this->container->get('fos_user.mailer')->sendResettingEmailMessage($user, $applicationTitle, $adminEmail);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->container->get('fos_user.user_manager')->updateUser($user);

        $this->setFlash($flashName, 'An email has been sent to '.$user->getEmail().'. It contains an link you must click to reset your password.');
        return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:checkEmail.html.'.$this->getEngine(), array(
            'email' => $email,
        ));
    }

    /**
     * Reset user password
     */
    public function resetAction($token)
    {
        $baseLayout = $this->container->getParameter('fos_user.settings.base_layout');
        $usePageHeader = $this->container->getParameter('fos_user.settings.use_page_header');
        $flashName = $this->container->getParameter('fos_user.settings.flash_name');
        
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');
        $process = $formHandler->process($user);

        if ($process) {

            $this->setFlash($flashName, 'Your password has been reset.');

            return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
        }
        
        $adminPool = $this->container->get('sonata.admin.pool');

        $templateParameters = array(
            'token' => $token,
            'resetForm' => $form->createView(),
            'theme' => $this->container->getParameter('fos_user.template.theme'),
            'baseLayout' => $baseLayout,
            'usePageHeader' => $usePageHeader,
        );
        
        if($adminPool) $templateParameters['admin_pool'] = $adminPool;        

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:reset.html.'.$this->getEngine(), $templateParameters);
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     */
    protected function authenticateUser(UserInterface $user)
    {
        try {
            $this->container->get('fos_user.user_checker')->checkPostAuth($user);
        } catch (AccountStatusException $e) {
            // Don't authenticate locked, disabled or expired users
            return;
        }

        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

    /**
     * Generate the redirection url when the resetting is completed.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('fos_user_profile_show');
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(UserInterface $user)
    {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
    }
}
