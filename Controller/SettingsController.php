<?php

namespace FOS\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SettingsController extends Controller
{

    public function settingsAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        $form = $this->container->get('fos_user.settings.form');
        $formHandler = $this->container->get('fos_user.settings.form.handler');        
        
        $process = $formHandler->process($user);
        if ($process) {
            $this->getRequest()->getSession()->setFlash('sonata_flash_success', 'Your Settings have been updated');
            return $this->redirect($this->generateUrl('home'));
        }
        
        return $this->render('FOSUserBundle:Settings:settings.html.twig', array(
                    'base_template' => $this->container->get('sonata.admin.pool')->getTemplate('layout'),
                    'admin_pool' => $this->container->get('sonata.admin.pool'),
                    'settingsForm' => $form->createView(),
                    'noPageHeaderBorder' => true
                ));
    }
}
