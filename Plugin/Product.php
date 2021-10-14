<?php
namespace Dm\NiceImageName\Plugin;

use Magento\Framework\App\Filesystem\DirectoryList;
use \Psr\Log\LoggerInterface;

use Cocur\Slugify\Slugify;
use Closure;

class Product
{
    private $_filesystem;
    private $_collectionFactory;
    private $logger;
    private $imageHelper;

    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        \Dm\NiceImageName\Helper\Image $imageHelper
    ) {
        $this->_filesystem = $filesystem;
        $this->_collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->imageHelper = $imageHelper;
    }

    public function aroundGetMediaGalleryImages(\Magento\Catalog\Model\Product $subject, callable $proceed)
    {
        $directory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$subject->hasData('media_gallery_images')) {
            $subject->setData('media_gallery_images', $this->_collectionFactory->create());
        }
        if (!$subject->getData('media_gallery_images')->count() && is_array($subject->getMediaGallery('images'))) {
            $images = $subject->getData('media_gallery_images');
            foreach ($subject->getMediaGallery('images') as $image) {
                if (!empty($image['disabled'])
                    || !empty($image['removed'])
                    || empty($image['value_id'])
                    || $images->getItemById($image['value_id']) != null
                ) {
                    continue;
                }
                $image['file'] = $this->imageHelper->createNewFile($subject, $image['file']);
                $image['url'] = $subject->getMediaConfig()->getMediaUrl($image['file']);
                $image['id'] = $image['value_id'];
                $image['path'] = $directory->getAbsolutePath($subject->getMediaConfig()->getMediaPath($image['file']));
                $images->addItem(new \Magento\Framework\DataObject($image));
            }
            $subject->setData('media_gallery_images', $images);
        }

        return $subject->getData('media_gallery_images');
    }
}