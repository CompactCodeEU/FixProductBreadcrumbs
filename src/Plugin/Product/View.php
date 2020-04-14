<?php
/**
 * Copyright (c) 2018.
 * Copyright Holder : CompactCode - CompactCode BvBa - Belgium
 * Copyright : Unless granted permission from CompactCode BvBa  you can not distrubute , reuse  , edit , resell or sell this.
 */

/**
 * Created by PhpStorm.
 * User: Rob Conings
 * Date: 7/6/2018
 * Time: 11:44 AM
 */

namespace CompactCode\FixProductBreadcrumbs\Plugin\Product;

use Magento\Catalog\Controller\Product\View as MagentoView;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Registry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\View\Result\Page;
use Magento\Catalog\Model\CategoryRepository;

class View
{

    /**
     * @var Product
     */
    protected $product;
    /**
     * @var StoreManager
     */
    protected $storeManager;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Collection
     */
    protected $collection;
    /**
     * @var PageFactory
     */
    private $resultPage;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;


    /**
     * View constructor.
     * @param StoreManager $storeManager
     * @param Registry $registry
     * @param Collection $collection
     * @param PageFactory $resultPage
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        StoreManager $storeManager,
        Registry $registry,
        Collection $collection,
        PageFactory $resultPage,
        CategoryRepository $categoryRepository)
    {
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->collection = $collection;
        $this->resultPage = $resultPage;
        $this->categoryRepository = $categoryRepository;
    }

    public function afterExecute(MagentoView $subject, $result)
    {
        if(!$result instanceof Page){
            return $result;
        }

        $resultPage = $this->resultPage->create();
        $breadcrumbsBlock = $resultPage->getLayout()->getBlock('breadcrumbs');
        if(!$breadcrumbsBlock || !isset($breadcrumbsBlock)){
            return $result;

        }
        $breadcrumbsBlock->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->storeManager->getStore()->getBaseUrl()
            ]
        );

        try {
            $product = $this->getProduct();
        } catch (LocalizedException $e) {
            return $result;
        }
        
        $pageMainTitle = $resultPage->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle($product->getName());
        }

        try {
            $productPath = null;
            if (null == $product->getCategory() || null == $product->getCategory()->getPath()) {
                foreach ($product->getCategoryIds() as $categoryId) {
                    $category = $this->categoryRepository->get($categoryId);
                    if ($category->getIsActive() && $category->isInRootCategoryList()) {
                        $productPath = $category->getPath();
                        break;
                    }
                }
            } else {
                $productPath = $product->getCategory()->getPath();
            }
            if(null == $productPath){
                $breadcrumbsBlock->addCrumb(
                    'cms_page',
                    [
                        'label' => $product->getName(),
                        'title' => $product->getName(),
                    ]
                );
                return $result;
            }
            $categoriesIds = explode('/', $productPath);
            foreach ($categoriesIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                if ($category->getIsActive() && $category->isInRootCategoryList()) {
                    $path = [
                        'label' => $category->getName(),
                        'link' => $category->getUrl() ? $category->getUrl() : ''
                    ];
                    $breadcrumbsBlock->addCrumb('category' . $categoryId, $path);
                }
            }
        } catch (LocalizedException $e) {
            return $result;
        }

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
