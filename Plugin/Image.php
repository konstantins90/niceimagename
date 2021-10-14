<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dm\NiceImageName\Plugin;

use Cocur\Slugify\Slugify;

class Image extends \Magento\Catalog\Helper\Image
{
	private $logger;
    private $imageHelper;

	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\ImageFactory $productImageFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Magento\Catalog\Model\View\Asset\PlaceholderFactory $placeholderFactory = null,
        \Dm\NiceImageName\Helper\Image $imageHelper
    ) {
        $this->imageHelper = $imageHelper;
    	parent::__construct($context, $productImageFactory, $assetRepo, $viewConfig, $placeholderFactory);
    }

    protected function initBaseFile()
    {
        $model = $this->_getModel();
        $product = $this->getProduct();
        if ($this->getImageFile()) {
            $file = $this->getImageFile();
        }
        else {
            $file = $product->getData($model->getDestinationSubdir());
        }

        $file = $this->imageHelper->createNewFile($product, $file);

        $model->setBaseFile($file);
        return $this;

        // return parent::initBaseFile();
    }
}