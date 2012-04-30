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
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends ContainerAware
{
    public function loginAction()
    {
        if ($this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->container->get('router')->generate('home'));
        }
        
        $baseLayout = $this->container->getParameter('fos_user.settings.base_layout');
        $usePageHeader = $this->container->getParameter('fos_user.settings.use_page_header');
        
        $ua = $_SERVER['HTTP_USER_AGENT'];
	if((!isset($_SESSION['ie6_message']) || $_SESSION['ie6_message'] == true) && preg_match('/\bmsie 6/i', $ua) && !preg_match('/\bopera/i', $ua)) {
          $usingIE6 = true;
        }
        else{
          $usingIE6 = false;
        }
      
        $request = $this->container->get('request');
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $session = $request->getSession();
        /* @var $session \Symfony\Component\HttpFoundation\Session */

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
            
            // customize the disable account error message
            if($error == 'User account is disabled.') {
                $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
                $approvalEnabled = $this->container->getParameter('fos_user.registration.approval.enabled');        

                if($confirmationEnabled)
                {
                    $error = "You must verify your e-mail before logging in.  Please follow the instructions in the e-mail you received when you registered.";
                }
                else if($approvalEnabled)
                {
                    $error = "Your account must first be approved by an administrator before you can login.";    
                }
            }
                 
                
        }
        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

        $csrfToken = $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate');

        $templateParameters = array(
            'last_username' => $lastUsername,
            'error'         => $error,
            'usingIE6'  => $usingIE6,
            'baseLayout' => $baseLayout,
            'usePageHeader' => $usePageHeader,
            'csrf_token' => $csrfToken,
        );
        
        if(class_exists('Sonata\AdminBundle\SonataAdminBundle')) {
            $adminPool = $this->container->get('sonata.admin.pool');
            $templateParameters['admin_pool'] = $adminPool;
        }
        
        return $this->container->get('templating')->renderResponse('FOSUserBundle:Security:login.html.'.$this->container->getParameter('fos_user.template.engine'), $templateParameters);
    }

    public function checkAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
