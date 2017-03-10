<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class FrontendCustomerUserRoleSelectType extends AbstractType
{
    const NAME = 'oro_customer_frontend_customer_user_role_select';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $roleClass;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param Registry $registry
     * @param AclHelper $aclHelper
     */
    public function __construct(SecurityFacade $securityFacade, Registry $registry, AclHelper $aclHelper)
    {
        $this->securityFacade = $securityFacade;
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @return ManagerRegistry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CustomerUserRoleSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $loggedUser = $this->securityFacade->getLoggedUser();
        if (!$loggedUser instanceof CustomerUser) {
            return;
        }

        $resolver->setNormalizer('loader', function () use ($loggedUser) {
            /** @var $repo CustomerUserRoleRepository */
            $repo = $this->registry->getManagerForClass($this->roleClass)
                ->getRepository($this->roleClass);
            $criteria = new Criteria();
            $qb = $repo->createQueryBuilder('customer');
            $this->aclHelper->applyAclToCriteria(
                $this->roleClass,
                $criteria,
                'ASSIGN',
                ['customer' => 'customer.customer', 'organization' => 'customer.organization']
            );
            $qb->addCriteria($criteria);
            $qb->orWhere(
                'customer.selfManaged = :isActive AND customer.public = :isActive AND customer.customer is NULL'
            );
            $qb->setParameter('isActive', true, \PDO::PARAM_BOOL);

            return new ORMQueryBuilderLoader($qb);
        });
    }

    /**
     * @param string $roleClass
     */
    public function setRoleClass($roleClass)
    {
        $this->roleClass = $roleClass;
    }
}
