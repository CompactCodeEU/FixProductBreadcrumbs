<?php
/**
 * Copyright (c) 2018.
 * Copyright Holder : CompactCode - CompactCode BvBa - Belgium
 * Copyright : Unless granted permission from CompactCode BvBa  you can not distrubute , reuse  , edit , resell or sell this.
 */

namespace CompactCode\FixProductBreadcrumbs\Plugin\Product;

use Magento\Catalog\Controller\Product\View as MagentoView;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Registry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\View\Result\PageFactory;


class View
{

    /**
     * @var Product
     */
    private $product;
    /**
     * @var StoreManager
     */
    private $storeManager;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * View constructor.
     * @param StoreManager $storeManager
     * @param Registry $registry
     * @param Collection $collection
     */
    public function __construct(
        StoreManager $storeManager,
        Registry $registry,
        Collection $collection,
        PageFactory $resultPageFactory)
    {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->collection = $collection;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function afterExecute(MagentoView $subject, $result)
    {
        $page = $this->resultPageFactory->create();
        $breadcrumbsBlock = $page->getLayout()->getBlock('breadcrumbs');
        $breadcrumbsBlock->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->storeManager->getStore()->getBaseUrl()
            ]
        );
        if(!isset($breadcrumbsBlock)){
            return $result;
        }
        try {
            $product = $this->getProduct();
        } catch (LocalizedException $e) {
            return $result;
        }

        if(null == $product->getCategory() || null == $product->getCategory()->getPath()){
            $breadcrumbsBlock->addCrumb(
                'cms_page',
                [
                    'label' => $product->getName(),
                    'title' => $product->getName(),
                ]
            );
            return $result;
        }

        $categories = $product->getCategory()->getPath();
        $categoriesids = explode('/', $categories);

        $categoriesCollection = null;
        try {
            $categoriesCollection = $this->collection
                ->addFieldToFilter('entity_id', array('in' => $categoriesids))
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('include_in_menu')
                ->addAttributeToSelect('is_active')
                ->addAttributeToSelect('is_anchor');
        } catch (LocalizedException $e) {
            return $result;
        }

        foreach ($categoriesCollection->getItems() as $category) {
            if ($category->getIsActive() && $category->isInRootCategoryList()) {
                $categoryId = $category->getId();
                $path = [];
                $path = [
                    'label' => $category->getName(),
                    'link' => $category->getUrl() ? $category->getUrl() : ''
                ];
                $breadcrumbsBlock->addCrumb('category' . $categoryId, $path);
            }
        }

        $breadcrumbsBlock->addCrumb(
            'cms_page',
            [
                'label' => $product->getName(),
                'title' => $product->getName(),
            ]
        );

        return $result;
    }

    /**
     * @return Product
     * @throws LocalizedException
     */
    private function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->registry->registry('product');

            if (!$this->product->getId()) {
                throw new LocalizedException(__('Failed to initialize product'));
            }
        }

        return $this->product;
    }
}
