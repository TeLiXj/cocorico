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
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Form\Type\ListingImageType;
use Cocorico\CoreBundle\Form\Type\PriceType;
use Cocorico\UserBundle\Repository\UserRepository;
use Doctrine\ORM\Query\Expr;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\Filter\NumberType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\Form\Type\DatePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ListingAdmin extends AbstractAdmin
{
    protected $translationDomain = 'SonataAdminBundle';
    protected $baseRoutePattern = 'listing';
    protected $locales;
    protected $includeVat;
    protected $bundles;

    // setup the default sort column and order
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt'
    );

    public function setLocales($locales)
    {
        $this->locales = $locales;
    }

    /**
     * @param bool $includeVat
     */
    public function setIncludeVat($includeVat)
    {
        $this->includeVat = $includeVat;
    }

    public function setBundlesEnabled($bundles)
    {
        $this->bundles = $bundles;
    }

    /** @inheritdoc */
    protected function configureFormFields(FormMapper $formMapper)
    {
        /** @var Listing $listing */
        $listing = $this->getSubject();

        $offererQuery = null;
        if ($listing) {
            /** @var UserRepository $userRepository */
            $userRepository = $this->modelManager->getEntityManager('CocoricoUserBundle:User')
                ->getRepository('CocoricoUserBundle:User');

            $offererQuery = $userRepository->getFindOneQueryBuilder($listing->getUser()->getId());
        }


        //Translations fields
        $titles = $descriptions = $rules = array();
        foreach ($this->locales as $i => $locale) {
            $titles[$locale] = array(
                'label' => 'Title'
            );
            $descriptions[$locale] = array(
                'label' => 'Description'
            );
            $rules[$locale] = array(
                'label' => 'Rules'
            );
        }

        $formMapper
            ->with('admin.listing.title')
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'choices' => array_flip(Listing::$statusValues),
                    'placeholder' => 'admin.listing.status.label',
                    'translation_domain' => 'cocorico_listing',
                    'label' => 'admin.listing.status.label',
                )
            )
            ->add(
                'adminNotation',
                ChoiceType::class,
                array(
                    'choices' => array_combine(
                        range(0, 10, 0.5),
                        array_map(
                            function ($num) {
                                return number_format($num, 1);
                            },
                            range(0, 10, 0.5)
                        )
                    ),
                    'placeholder' => 'admin.listing.admin_notation.label',
                    'label' => 'admin.listing.admin_notation.label',
                    'required' => false,
                )
            )
            ->add(
                'certified',
                null,
                array(
                    'label' => 'admin.listing.certified.label',
                )
            )
            ->add(
                'translations',
                TranslationsType::class,
                array(
                    'locales' => $this->locales,
                    'required_locales' => $this->locales,
                    'fields' => array(
                        'title' => array(
                            'field_type' => TextType::class,
                            'locale_options' => $titles,
                        ),
                        'description' => array(
                            'field_type' => TextareaType::class,
                            'locale_options' => $descriptions,
                        ),
                        'rules' => array(
                            'field_type' => TextareaType::class,
                            'locale_options' => $rules,
                            'required' => false,
                        ),
                        'slug' => array(
                            'display' => false
                        )
                    ),
                    'label' => 'Descriptions'
                )
            )
            ->add(
                'user',
                ModelType::class,
                array(
                    'query' => $offererQuery,
                    'disabled' => true,
                    'label' => 'admin.listing.user.label'
                )
            )
            ->add(
                'listingListingCategories',
                null,
                array(
                    'disabled' => true,
                    'label' => 'admin.listing.categories.label'
                )
            )
            ->add(
                'images',
                CollectionType::class,
                array(
                    'entry_type' => ListingImageType::class,
                    'by_reference' => false,
                    'required' => false,
                    'disabled' => true,
                    'prototype' => true,
                    'allow_add' => false,
                    'allow_delete' => false,

                    'label' => 'admin.listing.images.label'
                )
            )
            ->add(
                'price',
                PriceType::class,
                array(
                    'disabled' => true,
                    'label' => 'admin.listing.price.label',
                    'include_vat' => $this->includeVat
                )
            );

        if (array_key_exists("CocoricoListingDepositBundle", $this->bundles)) {
            $formMapper
                ->add(
                    'amountDeposit',
                    PriceType::class,
                    array(
                        'disabled' => true,
                        'label' => 'listing_edit.form.deposit',
                        'required' => false,
                    ),
                    array(
                        'translation_domain' => 'cocorico_listing_deposit',
                    )
                );
        }

        $formMapper
            ->add(
                'cancellationPolicy',
                ChoiceType::class,
                array(
                    'choices' => array_flip(Listing::$cancellationPolicyValues),
                    'placeholder' => 'admin.listing.cancellation_policy.label',
                    'disabled' => true,
                    'label' => 'admin.listing.cancellation_policy.label',
                    'translation_domain' => 'cocorico_listing',
                )
            )
            ->add(
                'location.completeAddress',
                TextType::class,
                array(
                    'disabled' => true,
                    'label' => 'admin.listing.location.label'
                )
            )
            ->add(
                'createdAt',
                null,
                array(
                    'disabled' => true,
                    'label' => 'admin.listing.created_at.label'
                )
            )
            ->add(
                'updatedAt',
                null,
                array(
                    'disabled' => true,
                    'label' => 'admin.listing.updated_at.label'
                )
            )
//            ->end()
//            ->with('Characteristics')
//            ->add(
//                'listingListingCharacteristics',
//                null,
//                array(
//                    'expanded' => true,
//                    'label' => 'admin.listing.characteristics.label'
//                )
//            )
            ->end();


        if (array_key_exists("CocoricoCarrierBundle", $this->bundles)) {
            $formMapper
                ->with('admin.booking.delivery')
                ->add(
                    'pallets',
                    'number',
                    array(
                        'label' => 'listing.form.pallets',
                        'required' => true
                    ),
                    array(
                        'translation_domain' => 'cocorico_carrier_listing',
                    )
                )
                ->end();
        }
    }

    /** @inheritdoc */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add(
                'fullName',
                'doctrine_orm_callback',
                array(
                    'callback' => array($this, 'getFullNameFilter'),
                    'field_type' => TextType::class,
                    'operator_type' => HiddenType::class,
                    'operator_options' => array(),
                    'label' => 'admin.listing.offerer.label'
                )
            )
            ->add(
                'user.email',
                null,
                array('label' => 'admin.listing.user_email.label')
            )
            ->add(
                'user.phone',
                null,
                array('label' => 'admin.listing.user_phone.label')
            )
            ->add(
                'listingListingCategories.category',
                null,
                array('label' => 'admin.listing.categories.label')
            )
            ->add(
                'status',
                'doctrine_orm_string',
                array(),
                ChoiceType::class,
                array(
                    'choices' => array_flip(Listing::$statusValues),
                    'translation_domain' => 'cocorico_listing',
                    'label' => 'admin.listing.status.label',
                )
            )
            ->add(
                'createdAt',
                'doctrine_orm_callback',
                array(
                    'label' => 'admin.listing.created_at.label',
                    'callback' => function ($queryBuilder, $alias, $field, $value) {
                        /** @var \DateTime $date */
                        $date = $value['value'];
                        if (!$date) {
                            return false;
                        }

                        $queryBuilder
                            ->andWhere("DATE_FORMAT($alias.createdAt,'%Y-%m-%d') = :createdAt")
                            ->setParameter('createdAt', $date->format('Y-m-d'));

                        return true;
                    },
                    'field_type' => DatePickerType::class,
                    'field_options' => array('format' => 'dd/MM/yyyy'),
                ),
                null
            )
            ->add(
                'updatedAt',
                'doctrine_orm_callback',
                array(
                    'label' => 'admin.listing.updated_at.label',
                    'callback' => function ($queryBuilder, $alias, $field, $value) {
                        /** @var \DateTime $date */
                        $date = $value['value'];
                        if (!$date) {
                            return false;
                        }

                        $queryBuilder
                            ->andWhere("DATE_FORMAT($alias.updatedAt,'%Y-%m-%d') = :updatedAt")
                            ->setParameter('updatedAt', $date->format('Y-m-d'));

                        return true;
                    },
                    'field_type' => DatePickerType::class,
                    'field_options' => array('format' => 'dd/MM/yyyy'),
                ),
                null
            )
            ->add(
                'priceMin',
                'doctrine_orm_callback',
                array(
                    'callback' => array($this, 'getPriceMinFilter'),
                    'field_type' => TextType::class,
                    'operator_type' => ChoiceType::class,
                    'operator_options' => array(
                        'choices' => array(
                            NumberType::TYPE_GREATER_EQUAL => '>=',
                        ),
                    ),
                    'label' => 'admin.listing.price_min.label'
                )
            )
            ->add(
                'priceMax',
                'doctrine_orm_callback',
                array(
                    'callback' => array($this, 'getPriceMaxFilter'),
                    'field_type' => TextType::class,
                    'operator_type' => ChoiceType::class,
                    'operator_options' => array(
                        'choices' => array(
                            NumberType::TYPE_LESS_EQUAL => '<='
                        )
                    ),
                    'label' => 'admin.listing.price_max.label'
                )
            )
            ->add(
                'location.coordinate.city',
                null,
                array('label' => 'admin.listing.city.label')
            )
            ->add(
                'location.coordinate.country',
                null,
                array('label' => 'admin.listing.country.label')
            );
    }

    public function getPriceMinFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['type']) {
            $value['type'] = NumberType::TYPE_GREATER_EQUAL;
        }

        return $this->getPriceFilter($queryBuilder, $alias, $field, $value);
    }

    public function getPriceMaxFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['type']) {
            $value['type'] = NumberType::TYPE_LESS_EQUAL;
        }

        return $this->getPriceFilter($queryBuilder, $alias, $field, $value);
    }

    public function getPriceFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return false;
        }

        $value['value'] = $value['value'] * 100;

        if ($value['type'] === NumberType::TYPE_GREATER_EQUAL) {
            $queryBuilder
                ->andWhere($alias . '.price >= :valueMin')
                ->setParameter('valueMin', $value['value']);

            return true;
        }

        if ($value['type'] === NumberType::TYPE_LESS_EQUAL) {
            $queryBuilder
                ->andWhere($alias . '.price <= :valueMax')
                ->setParameter('valueMax', $value['value']);

            return true;
        }

        return false;
    }

    /** @inheritdoc */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add(
                'statusText',
                null,
                array(
                    'label' => 'admin.listing.status.label',
                    'template' => 'admin/list_field_value_translated.html.twig',
                    'data_trans' => 'cocorico_listing'
                )
            )
            ->add(
                'user',
                null,
                array('label' => 'admin.listing.user.label')
            )
            ->add(
                'user.email',
                null,
                array('label' => 'admin.listing.user_email.label')
            )
            ->add(
                'user.phone',
                null,
                array('label' => 'admin.listing.user_phone.label')
            )
            ->add(
                'title',
                null,
                array('label' => 'admin.listing.title.label')
            )
            ->add(
                'priceDecimal',
                null,
                array(
                    'label' => 'admin.listing.price.label', //Price (€)',
                )
            )
            ->add(
                'averageRating',
                null,
                array('label' => 'admin.listing.average_rating.label')
            );

        $listMapper
            ->add(
                'updatedAt',
                'date',
                array(
                    'label' => 'admin.listing.updated_at.label',
                )
            );

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $listMapper
                ->add(
                    'impersonating',
                    'string',
                    array(
                        'template' => 'admin/impersonating.html.twig',
                    )
                );
        }

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

    public function getFullNameFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return false;
        }

        $exp = new Expr();
        $queryBuilder
            ->andWhere(
                $exp->orX(
                    $exp->like('s_user.firstName', $exp->literal('%' . $value['value'] . '%')),
                    $exp->like('s_user.lastName', $exp->literal('%' . $value['value'] . '%')),
                    $exp->like(
                        $exp->concat(
                            's_user.firstName',
                            $exp->concat($exp->literal(' '), 's_user.lastName')
                        ),
                        $exp->literal('%' . $value['value'] . '%')
                    )
                )
            );

        return true;
    }

    public function getBatchActions()
    {
        $actions = parent::getBatchActions();
        unset($actions["delete"]);

        return $actions;
    }

    public function getExportFields()
    {
        return array(
            'Id' => 'id',
            'Status' => 'statusText',
            'Offerer' => 'user.fullname',
            'Email' => 'user.Email',
            'Phone' => 'user.Phone',
            'Listing Title' => 'title',
            'Price' => 'priceDecimal',
            'Average Rating' => 'averageRating',
            'Updated At' => 'updatedAt'
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

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('create');
        $collection->remove('delete');
    }


}
