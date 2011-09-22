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
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ResettingController extends ContainerAware
{
    /**
     * Request reset user password: show form
     */
    public function requestAction()
    {
        $baseLayout = $this->container->get('userSettings')->baseLayout;
        $useBreadcrumb = $this->container->get('userSettings')->useBreadcrumb;

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array(
            'baseLayout' => $baseLayout,
            'useBreadcrumb' => $useBreadcrumb                        
        ));
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction()
    {
        $username = $this->container->get('request')->request->get('username');
        $flashName = $this->container->get('userSettings')->flashName;
        $baseLayout = $this->container->get('userSettings')->baseLayout;
        $useBreadcrumb = $this->container->get('userSettings')->useBreadcrumb;

        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

        if (null === $user){
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array('invalid_username' => $username));
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            $this->setFlash($flashName, 'The password for this user has already been requested within the last 24 hours.');
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array('baseLayout' => $baseLayout, 'useBreadcrumb' => $useBreadcrumb));
        }

        $user->generateConfirmationToken();
        $applicationTitle = $this->container->get('userSettings')->applicationTitle;
        $adminEmail = $this->container->get('userSettings')->adminEmail;
        
        $this->container->get('session')->set('fos_user_send_resetting_email/email', $user->getEmail());
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
        $email = $session->get('fos_user_send_resetting_email/email');
        $session->remove('fos_user_send_resetting_email/email');
        $user = $this->container->get('fos_user.user_manager')->findUserByEmail($email);
        if (empty($user)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:checkEmail.html.'.$this->getEngine(), array(
            'user' => $user,
        ));
    }

    /**
     * Reset user password
     */
    public function resetAction($token)
    {
        $baseLayout = $this->container->get('userSettings')->baseLayout;
        $useBreadcrumb = $this->container->get('userSettings')->useBreadcrumb;
        $flashName = $this->container->get('userSettings')->flashName;
 
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user){
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

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:reset.html.'.$this->getEngine(), array(
            'token' => $token,
            'resetForm' => $form->createView(),
            'theme' => $this->container->getParameter('fos_user.template.theme'),
            'baseLayout' => $baseLayout,
            'useBreadcrumb' => $useBreadcrumb            
        ));
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
