<?php

namespace Cocorico\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class BookingUserAddressAdmin extends AbstractAdmin
{
    protected $translationDomain = 'cocorico_user';
    protected $baseRoutePattern = 'booking-user-address';
    protected $locales;

    public function setLocales($locales)
    {
        $this->locales = $locales;
    }

    /** @inheritdoc */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add(
                'address',
                TextareaType::class,
                array(
                    'label' => 'form.address.address',
                    'required' => false,
                )
            )
            ->add(
                'city',
                null,
                array(
                    'label' => 'form.address.city',
                    'required' => false,
                )
            )
            ->add(
                'zip',
                null,
                array(
                    'label' => 'form.address.zip',
                    'required' => false,
                )
            )
            ->add(
                'country',
                CountryType::class,
                array(
                    'label' => 'form.address.country',
                    'required' => false,
                    'preferred_choices' => array("GB", "FR", "ES", "DE", "IT", "CH", "US", "RU"),
                )
            )
            ->end();
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('create');
        $collection->remove('delete');
    }
}
