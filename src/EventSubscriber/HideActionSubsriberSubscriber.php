<?php

namespace App\EventSubscriber;

use App\Entity\Question;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;

class HideActionSubsriberSubscriber implements EventSubscriberInterface
{
    public function onBeforeCrudActionEvent(BeforeCrudActionEvent $event)
    {
//        dd($event);
        if (!$adminContext = $event->getAdminContext()) {
            return;
        }
        if (!$crudDto = $adminContext->getCrud()) {
            return;
        }
        if ($crudDto->getEntityFqcn() !== Question::class) {
            return;
        }

        //disable action entirely for delete, detail & edit pages
        $question = $adminContext->getEntity()->getInstance();
        if ($question instanceof Question && $question->getIsApproved()) {
            $crudDto->getActionsConfig()->disableActions([Action::DELETE]);
        }

        //returns the array of actual actions that will be enabled
        // for current page
        $actions = $crudDto->getActionsConfig()->getActions();
        if(!$deleteAction = $actions[Action::DELETE] ?? null){
            return;
        }
        $deleteAction->setDisplayCallable(static function(Question $question){
            return !$question->getIsApproved();

        });
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeCrudActionEvent::class => 'onBeforeCrudActionEvent',
        ];
    }
}
