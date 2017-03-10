<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;

abstract class AbstractCustomerTaxExtensionTest extends AbstractTaxExtensionTest
{
    /**
     * @var CustomerTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @param bool $expectsManager
     * @param bool $expectsRepository
     */
    protected function prepareDoctrineHelper($expectsManager = false, $expectsRepository = false)
    {
        $this->entityRepository = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($expectsRepository ? $this->once() : $this->never())
            ->method('getEntityRepository')
            ->with('OroTaxBundle:CustomerTaxCode')
            ->willReturn($this->entityRepository);
    }

    public function testBuildForm()
    {
        $customerTaxExtension = $this->getExtension();

        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'taxCode',
                CustomerTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$customerTaxExtension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$customerTaxExtension, 'onPostSubmit'], 10);

        $customerTaxExtension->buildForm($builder, []);
    }

    public function testOnPostSetDataExistingEntity()
    {
        $this->prepareDoctrineHelper(false, true);

        $customer = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($customer);

        $taxCode = $this->createTaxCode();

        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod())
            ->with($customer)
            ->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

        $this->getExtension()->onPostSetData($event);
    }

    /**
     * @param int|null $id
     * @return CustomerTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->getEntity('Oro\Bundle\TaxBundle\Entity\CustomerTaxCode', ['id' => $id]);
    }

    /**
     * Return name of method which find TaxCode entity
     *
     * @return string
     */
    abstract protected function getRepositoryFindMethod();

    /**
     * Return testable collection of CustomerTaxCode
     *
     * @param CustomerTaxCode $customerTaxCode
     * @return Collection
     */
    abstract protected function getTestableCollection(CustomerTaxCode $customerTaxCode);
}
