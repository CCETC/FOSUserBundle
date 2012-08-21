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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the user profile
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ProfileController extends ContainerAware
{
    /**
     * Show the user
     */
    public function showAction()
    {
        $baseLayout = $this->container->getParameter('fos_user.options.base_layout');
        $usePageHeader = $this->container->getParameter('fos_user.options.use_page_header');
        $flashName = $this->container->getParameter('fos_user.options.flash_name');

        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        
        $templateParameters = array(
            'user' => $user,
            'baseLayout' => $baseLayout,
            'usePageHeader' => $usePageHeader,
        );

        if(class_exists('Sonata\AdminBundle\SonataAdminBundle')) {
            $adminPool = $this->container->get('sonata.admin.pool');
            $templateParameters['admin_pool'] = $adminPool;
        }
        
        return $this->container->get('templating')->renderResponse('FOSUserBundle:Profile:show.html.'.$this->container->getParameter('fos_user.template.engine'), $templateParameters);
    }

    /**
     * Edit the user
     */
    public function editAction()
    {
        $baseLayout = $this->container->getParameter('fos_user.options.base_layout');
        $usePageHeader = $this->container->getParameter('fos_user.options.use_page_header');
        $flashName = $this->container->getParameter('fos_user.options.flash_name');

        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->container->get('fos_user.profile.form');
        $formHandler = $this->container->get('fos_user.profile.form.handler');

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash($flashName, 'Your information has been updated.');

            return new RedirectResponse($this->container->get('router')->generate('fos_user_profile_show'));
        }

        $templateParameters = array(
                'profileForm' => $form->createView(),
                'theme' => $this->container->getParameter('fos_user.template.theme'),
                'baseLayout' => $baseLayout,
                'usePageHeader' => $usePageHeader,
                'user' => $user
        );
        
        if(class_exists('Sonata\AdminBundle\SonataAdminBundle')) {
            $adminPool = $this->container->get('sonata.admin.pool');
            $templateParameters['admin_pool'] = $adminPool;
        }        
        
        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Profile:edit.html.'.$this->container->getParameter('fos_user.template.engine'), $templateParameters
        );
    }

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }
}
