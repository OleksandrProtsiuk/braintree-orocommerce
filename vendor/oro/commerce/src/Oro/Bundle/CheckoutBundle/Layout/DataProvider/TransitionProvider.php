<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;

class TransitionProvider
{
    /**
     * @var array
     */
    private $backTransitions = [];

    /**
     * @var array
     */
    private $continueTransitions = [];

    /**
     * @var WorkflowManager
     */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return null|TransitionData
     */
    public function getBackTransition(WorkflowItem $workflowItem)
    {
        $transitions = $this->getBackTransitions($workflowItem);

        if ($transitions) {
            return end($transitions);
        }

        return null;
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return array
     */
    public function getBackTransitions(WorkflowItem $workflowItem)
    {
        $cacheKey = $workflowItem->getId() . '_' . $workflowItem->getCurrentStep()->getId();
        if (!array_key_exists($cacheKey, $this->backTransitions)) {
            $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
            /** @var TransitionData[] $backTransitions */
            $backTransitions = [];
            foreach ($transitions as $transition) {
                $frontendOptions = $transition->getFrontendOptions();
                if (!empty($frontendOptions['is_checkout_back'])) {
                    $stepOrder = $transition->getStepTo()->getOrder();

                    $transitionData = $this->getTransitionData($transition, $workflowItem);
                    if ($transitionData) {
                        $backTransitions[$stepOrder] = $transitionData;
                    }
                }
            }
            ksort($backTransitions);

            $transitions = [];
            foreach ($backTransitions as $transitionData) {
                $transitions[$transitionData->getTransition()->getStepTo()->getName()] = $transitionData;
            }

            $this->backTransitions[$cacheKey] = $transitions;
        }

        return $this->backTransitions[$cacheKey];
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return null|TransitionData
     */
    public function getContinueTransition(WorkflowItem $workflowItem)
    {
        $cacheKey = $workflowItem->getId() . '_' . $workflowItem->getCurrentStep()->getId();
        if (!array_key_exists($cacheKey, $this->continueTransitions)) {
            $continueTransition = null;
            $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
            foreach ($transitions as $transition) {
                $frontendOptions = $transition->getFrontendOptions();
                if (!empty($frontendOptions['is_checkout_continue'])) {
                    $continueTransition = $this->getTransitionData($transition, $workflowItem);
                    if ($continueTransition) {
                        break;
                    } else {
                        continue;
                    }
                }
            }
            $this->continueTransitions[$cacheKey] = $continueTransition;
        }

        return $this->continueTransitions[$cacheKey];
    }

    public function clearCache()
    {
        $this->continueTransitions = $this->backTransitions = [];
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return TransitionData|null
     */
    private function getTransitionData(Transition $transition, WorkflowItem $workflowItem)
    {
        $errors = new ArrayCollection();
        $isAllowed = $this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return new TransitionData($transition, $isAllowed, $errors);
        }

        return null;
    }
}
