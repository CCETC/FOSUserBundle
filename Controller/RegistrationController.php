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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the registration
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RegistrationController extends ContainerAware
{

    protected function sendApprovalNeededEmail($user)
    {
        $httpHost = $this->container->get('request')->getHttpHost();
        $baseUrl = $this->container->get('request')->getBaseUrl();
        $baseLink = $httpHost . $baseUrl;

        $adminTitle = $this->container->get('adminSettings')->adminTitle;

        $message = \Swift_Message::newInstance()
                ->setSubject($adminTitle.' - Approval needed')
                ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                ->setTo($this->container->get('adminSettings')->adminEmail)
                ->setContentType('text/html')
                ->setBody('<html>
                      ' . $user . ' has created an account on the GJGNY Data Tool.<br/>
                      Before this user can use the tool, you must approve their account.<br/><br/>
                      You can view all unapproved users by going to this address:<br/>
                      <a href="http://' . $baseLink . '/admin/gjgny/datatool/user/list?enabled=false">http://' . $baseLink . '/admin/gjgny/datatool/user/list?enabled=false</a>
                      </html>'
                )
        ;
        $this->container->get('mailer')->send($message);
    }

    public function registerAction()
    {
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('fos_user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $httpHost = $this->container->get('request')->getHttpHost();
        $baseUrl = $this->container->get('request')->getBaseUrl();
        $baseLink = $httpHost . $baseUrl;

        $process = $formHandler->process($confirmationEnabled);
        if($process)
        {
            $user = $form->getData();

            if($confirmationEnabled)
            {
                $this->setFlash('sonata_flash_success', 'Before you can log in an admin must verify your account.  Your admin has been asked to approve your account, but if need be you can contact your admin at ' . $this->container->get('adminSettings')->adminEmail . ' and request to be approved.');
                $route = 'fos_user_security_login';

                if(isset($this->container->get('adminSettings')->adminEmail))
                {
                    $this->sendApprovalNeededEmail($user);                
                }
                
            }
            else
            {
                $this->setFlash('sonata_flash_success', 'Your account has been created.  You are now logged in');
                $this->authenticateUser($user);
                $route = 'home';
            }


            $url = $this->container->get('router')->generate($route);

            return new RedirectResponse($url);
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:register.html.' . $this->getEngine(), array(
            'registrationForm' => $form->createView(),
            'theme' => $this->container->getParameter('fos_user.template.theme'),
        ));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $email = $this->container->get('session')->get('fos_user_send_confirmation_email/email');
        $this->container->get('session')->remove('fos_user_send_confirmation_email/email');
        $user = $this->container->get('fos_user.user_manager')->findUserByEmail($email);

        if(null === $user)
        {
            throw new NotFoundHttpException(sprintf('The user with email "%s" does not exist', $email));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:checkEmail.html.' . $this->getEngine(), array(
            'user' => $user,
        ));
    }

    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if(null === $user)
        {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $this->authenticateUser($user);

        return new RedirectResponse($this->container->get('router')->generate('fos_user_registration_confirmed'));
    }

    /**
     * Tell the user his account is now confirmed
     */
    public function confirmedAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if(!is_object($user) || !$user instanceof UserInterface)
        {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:confirmed.html.' . $this->getEngine(), array(
            'user' => $user,
        ));
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param Boolean $reAuthenticate
     */
    protected function authenticateUser(UserInterface $user)
    {
        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
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
