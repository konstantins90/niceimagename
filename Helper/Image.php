<?php
namespace Dm\NiceImageName\Helper;
use Cocur\Slugify\Slugify;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
	private $url;
    private $directoryList;

	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\Url $url,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
    	$this->url = $url;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function slug($string, $type = '')
    {
        if(!$string) {
            return '';
        }

        if($type == 'attr') {
            return str_replace('-', '_', $this->url->formatUrlKey($string));
        }

        if($type == 'img') {
            $path_parts = pathinfo($string);
            $string = $path_parts['filename'];
            $extension = $path_parts['extension'];
            return $this->url->formatUrlKey($string) . "." . strtolower($extension);
        }

        return $this->url->formatUrlKey($string);
    }

    public function getMediaUrl() {
        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]);
    }

    public function createNewFile($product, $file)
    {
        if(!$this->getConfig('dm_niceimagename/general/enabled')) {
            return $file;
        }

        $newFilename = $this->getFilename($product);
        $newFilename = '/' . $newFilename[0] . '/' . $newFilename[1] . '/' . $newFilename;
        $path = $this->directoryList->getPath('pub') . '/media/catalog/product';

        $source = $path . $file;

        $pathinfo = pathinfo($file);

        if(!isset($pathinfo['extension']) || !$newFilename) {
            return $file;
        }

        $newFilenameExt = $newFilename . '.' . $pathinfo['extension'];
        $newFilenameWithPath = $path . $newFilenameExt;

        $i = 1;
        while (true) {
            if($i > 10) {
                break;
            }

            if(file_exists($newFilenameWithPath)) {
                $md5Source = md5(file_get_contents($source));
                $md5New = md5(file_get_contents($newFilenameWithPath));
                if ($md5Source == $md5New) {
                    break;
                }

                $newFilenameExt = $newFilename . '_' . $i . '.' . $pathinfo['extension'];
                $newFilenameWithPath = $path . $newFilenameExt;
                $i++;
            } {
                break;
            }
        }

        if($newFilenameWithPath && file_exists($source)) {
            if (!file_exists(dirname($newFilenameWithPath))) {
                mkdir(dirname($newFilenameWithPath), 0777, true);
            }
            copy($source, $newFilenameWithPath);

            return $newFilenameExt;
        }

        return $file;
    }

    private function getFilename($product)
    {
        $slugify = new Slugify();

        $replaceArray = [];
        preg_match_all('/{{(.*?)}}/', $this->getScheme(), $matches);

        foreach ($matches[1] as $code) {
            $replaceArray['{{' . $code . '}}'] = $product->getResource()->getAttribute($code)->getFrontend()->getValue($product);
        }

        return $slugify->slugify(str_replace(array_keys($replaceArray), array_values($replaceArray), $this->getScheme()));
    }

    private function getScheme()
    {
        if($scheme = $this->getConfig('dm_niceimagename/general/scheme')) {
            return $scheme;
        }

        return '{{name}}-{{sku}}';
    }

}
