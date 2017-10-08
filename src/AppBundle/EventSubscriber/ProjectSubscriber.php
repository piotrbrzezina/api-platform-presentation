<?php
namespace AppBundle\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use AppBundle\Entity\Project;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ProjectSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['setProjectNumber', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function setProjectNumber(GetResponseForControllerResultEvent $event){
        $project = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$project instanceof Project || Request::METHOD_POST !== $method) {
            return;
        }

        $project->setNumber('numer '.rand(100,200));

    }
}