<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocorico\CoreBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Cocorico\CoreBundle\Entity\ListingCharacteristic;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;

class ListingCharacteristicAdmin extends AbstractAdmin
{
    protected $translationDomain = 'SonataAdminBundle';
    protected $baseRoutePattern = 'listing-characteristic';
    protected $locales;

    // setup the default sort column and order
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by' => 'position'
    );

    public function setLocales($locales)
    {
        $this->locales = $locales;
    }

    /** @inheritdoc */
    protected function configureFormFields(FormMapper $formMapper)
    {
        /** @var ListingCharacteristic $subject */
//        $subject = $this->getSubject();

        //Translations fields
        $titles = $descriptions = array();
        foreach ($this->locales as $i => $locale) {
            $titles[$locale] = array(
                'label' => 'Name',
                'constraints' => array(new NotBlank())
            );
            $descriptions[$locale] = array(
                'label' => 'Description',
                'constraints' => array(new NotBlank())
            );
        }

        $formMapper
            ->with('admin.listing_characteristic.title')
            ->add(
                'translations',
                TranslationsType::class,
                array(
                    'locales' => $this->locales,
                    'required_locales' => $this->locales,
                    'fields' => array(
                        'name' => array(
                            'field_type' => TextType::class,
                            'locale_options' => $titles,
                        ),
                        'description' => array(
                            'field_type' => TextareaType::class,
                            'locale_options' => $descriptions,
                        )
                    ),
                    /** @Ignore */
                    'label' => 'Descriptions'
                )
            )
            ->add(
                'position',
                null,
                array(
                    'label' => 'admin.listing_characteristic.position.label'
                )
            )
            ->add(
                'listingCharacteristicType',
                ModelListType::class,
                array(
                    'label' => 'admin.listing_characteristic.type.label',
                    'constraints' => array(new NotBlank())
                )
            )
            ->add(
                'listingCharacteristicGroup',
                ModelListType::class,
                array(
                    'label' => 'admin.listing_characteristic.group.label',
                    'constraints' => array(new NotBlank())
                )
            )
            ->end();
    }

    /** @inheritdoc */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add(
                'translations.name',
                null,
                array('label' => 'admin.listing_characteristic.name.label')
            )
            ->add(
                'listingCharacteristicType',
                null,
                array('label' => 'admin.listing_characteristic.type.label')
            )
            ->add(
                'listingCharacteristicGroup',
                null,
                array('label' => 'admin.listing_characteristic.group.label')
            );
    }

    /** @inheritdoc */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add(
                'name',
                null,
                array(
                    'label' => 'admin.listing_characteristic.name.label',
                )
            )
            ->addIdentifier(
                'listingCharacteristicType',
                null,
                array('label' => 'admin.listing_characteristic.type.label')
            )
            ->addIdentifier(
                'listingCharacteristicGroup',
                null,
                array('label' => 'admin.listing_characteristic.group.label')
            )
            ->add(
                'position',
                null,
                array('label' => 'admin.listing_characteristic.position.label')
            );


        $listMapper->add(
            '_action',
            'actions',
            array(
                'actions' => array(
                    //'show' => array(),
                    'edit' => array(),
                )
            )
        );
    }

    public function getExportFields()
    {
        return array(
            'Id' => 'id',
            'Name' => 'name',
            'Type of Characteristic' => 'listingCharacteristicType',
            'Group' => 'listingCharacteristicGroup',
            'Position' => 'position'
        );
    }

    public function getDataSourceIterator()
    {
        $datagrid = $this->getDatagrid();
        $datagrid->buildPager();

        $dataSourceIt = $this->getModelManager()->getDataSourceIterator($datagrid, $this->getExportFields());
        $dataSourceIt->setDateTimeFormat('d M Y'); //change this to suit your needs

        return $dataSourceIt;
    }

    public function getBatchActions()
    {
        $actions = parent::getBatchActions();
        unset($actions["delete"]);

        return $actions;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        //$collection->remove('create');
        //$collection->remove('delete');
    }
}
