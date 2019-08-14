<?php
/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Pitney Bowes International
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
/* Creates a temporary file containing a Pb catalog xml file. Because of possible PHP timeouts the creation of the
 * xml file is done in concrete steps that can be resumed in case of a timeout.
 */

class Shipperhq_Pbint_Model_Catalog_File
{


    private $file;
    private $filename;
    private $lastDiff;
    private $productIds;
    private $lastFileName;

    protected $_debug;

    public function __construct($lastDiff = false)
    {
        $this->lastDiff = $lastDiff;
        $this->productIds = array();
    }

    private function _getTempDir()
    {
        $appRoot = Mage::getRoot();
        $mageRoot = dirname($appRoot);
        $configOptions = Mage::getModel('core/config_options');
        $tmpDir = $mageRoot . '/var/pbint/tmp/';
        $configOptions->createDirIfNotExists($tmpDir);
        chmod($tmpDir, 0777);
        return $tmpDir;
    }


    private function _getDataFileName($dataFeedName, $part = null)
    {
        $partName = '';
        if ($part)
            $partName = '_part' . $part;
        $fileName = Shipperhq_Pbint_Model_Credentials::getCatalogSenderID() . "_" . $dataFeedName . "_update_" . Shipperhq_Pbint_Model_Credentials::getPBID() . '_' . date('Ymd_His') . '_' . mt_rand(100000, 999999);
        if ($part == 1)
            $this->lastFileName = $fileName;
        else
            $fileName = $this->lastFileName;
        return $fileName . $partName . '.csv';
    }

    private function _createNewCommoditiyFile($part = null)
    {
        $fileName = $this->_getDataFileName('catalog', $part);
        if(is_resource($this->file)) {
            fflush($this->file);

            fclose($this->file);

        }

        $this->filename = $this->_getTempDir() . $fileName;
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Product catalog upload - creating new commodity file', $fileName);
        }
        try {
            $this->file = fopen($this->filename, "w+");
            chmod($this->filename, 0777);
        }
        catch(Exception $e) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Product catalog upload exception during file opening', $e->getMessage());
            }
        }

        //add header row
        fputcsv($this->file, array('MERCHANT_COMMODITY_REF_ID', 'COMMODITY_NAME_TITLE', 'SHORT_DESCRIPTION',
            'LONG_DESCRIPTION', 'RETAILER_ID', 'COMMODITY_URL', 'RETAILER_UNIQUE_ID', 'PCH_CATEGORY_ID',
            'RH_CATEGORY_ID', 'STANDARD_PRICE', 'WEIGHT_UNIT', 'DISTANCE_UNIT', 'COO', 'IMAGE_URL', 'PARENT_SKU',
            'CHILD_SKU', 'PARCELS_PER_SKU', 'UPC', 'UPC_CHECK_DIGIT', 'GTIN', 'MPN', 'ISBN', 'BRAND', 'MANUFACTURER',
            'MODEL_NUMBER', 'MANUFACTURER_STOCK_NUMBER', 'COMMODITY_CONDITION', 'COMMODITY_HEIGHT',
            'COMMODITY_WIDTH', 'COMMODITY_LENGTH', 'PACKAGE_WEIGHT', 'PACKAGE_HEIGHT', 'PACKAGE_WIDTH',
            'PACKAGE_LENGTH', 'HAZMAT', 'ORMD', 'CHEMICAL_INDICATOR', 'PESTICIDE_INDICATOR', 'AEROSOL_INDICATOR',
            'RPPC_INDICATOR', 'BATTERY_TYPE', 'NON_SPILLABLE_BATTERY', 'FUEL_RESTRICTION', 'SHIP_ALONE',
            'RH_CATEGORY_ID_PATH', 'RH_CATEGORY_NAME_PATH'));
        //,'RH_CATEGORY_URL_PATH','GPC','COMMODITY_WEIGHT','HS_CODE','CURRENCY'

        fflush($this->file);
    }

    private function _createNewCategoryFile($part = null)
    {
        if(is_resource($this->file)) {
            fflush($this->file);
            fclose($this->file);

        }

        $fileName = $this->_getDataFileName('category-tree', $part);


        $this->filename = $this->_getTempDir() . $fileName;
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Product catalog upload - creating new category file', $fileName);
        }
        try {
            $this->file = fopen($this->filename, "w+");
            chmod($this->filename, 0777);
        }
        catch(Exception $e) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Product catalog upload exception during file opening', $e->getMessage());
            }
        }
        //add header row
        fputcsv($this->file, array('CATEGORY_ID', 'PARENT_CATEGORY_ID', 'NAME',
            'ID_PATH', 'URL'));

        fflush($this->file);
    }


    private function _getSelectedCategory($categories, $catId)
    {
        foreach ($categories as $cat) {
            if ($cat->getId() == $catId)
                return $cat;
        }
        return false;
    }

    /**
     * Extracts the categories and products and exports them into xml file.
     */
    public function createNew()
    {
        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();

        $maxRecordsCount = Shipperhq_Pbint_Model_Credentials::getMaxRecordsCount();
        if (!$maxRecordsCount) {
            $maxRecordsCount = 10000;
        }

        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'ShipperHQ Pitney product catalog upload', 'Maxium number of records: ' .$maxRecordsCount);
        }
        $prodCount = 0;
        $catCount = 0;
        $fileRecordCount = 0;
        //get stores which has disabled clearpath
        $stores = Mage::app()->getStores();
        $defaultStoreUrl = Mage::getBaseUrl();
        $secDefaultStoreUrl = str_replace("http", "https", $defaultStoreUrl);
        $disabledStores = array();
        $addedCategories = array();
        $part = 1;
        $atLeastOneStoreEnabled = false;
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'ShipperHQ Pitney product catalog upload', 'Number of stores in Magento: ' .count($stores));
        }
        foreach ($stores as $store) {

            $isActive = Mage::getStoreConfig('shipperhqpitney/shqpbint/active', $store);
            $baseURL = $store->getBaseUrl();

            if (!$isActive) {
                $disabledStores[] = $store;
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Disabled store ', $store->getId() . ' ' . $store->getCode());
                }
            } else {
                $atLeastOneStoreEnabled = true;
                $rootId = Mage::app()->getStore($store->getId())->getRootCategoryId();
                $rootCat = Mage::getModel('catalog/category')->load($rootId);
                /* @var $rootCat Mage_Catalog_Model_Category */
                $rootCat->setData('store', $store);
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Default store url ', $defaultStoreUrl . 'store ' . $store->getCode() . ' store base url'
                        . $baseURL . " root cat url" . $rootCat->getUrl() . " cat name" . $rootCat->getName());
                }

                $catUrl = str_replace($defaultStoreUrl, $baseURL, $rootCat->getUrl());
                if ($catUrl == $rootCat->getUrl())
                    $catUrl = str_replace($secDefaultStoreUrl, $baseURL, $rootCat->getUrl());
                $cat = new Shipperhq_Pbint_Model_Catalog_Category($rootCat, $catUrl);
                if (!$this->file || $fileRecordCount > $maxRecordsCount) {
                    $this->_createNewCategoryFile($part);
                    $fileRecordCount = 0;
                    $part++;
                }
                $cat->writeToFile($this->file);
                fflush($this->file);
                $catCount++;
                $fileRecordCount++;
                $categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addUrlRewriteToResult()
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter('path', array('like' => "1/$rootId/%"));
                $addedCategories[] = $rootCat;
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Product catalog upload ', 'Found ' .count($categories) .' for processing');
                }
                foreach ($categories as $category) {
                    if (!$this->file || $fileRecordCount > $maxRecordsCount) {
                        $this->_createNewCategoryFile($part);
                        $fileRecordCount = 0;
                        $part++;
                    }
                    /* @var $category Mage_Catalog_Model_Category */
                    $category->setStoreId($store->getId());
                    $category->setData('store', $store);
                    $addedCategories[] = $category;

                    $catUrl = str_replace($defaultStoreUrl, $baseURL, $category->getUrl());
                    if ($catUrl == $category->getUrl())
                        $catUrl = str_replace($secDefaultStoreUrl, $baseURL, $category->getUrl());
                    $cat = new Shipperhq_Pbint_Model_Catalog_Category($category, $catUrl);
                    $cat->writeToFile($this->file);
                    fflush($this->file);
                    $catCount++;
                    $fileRecordCount++;
                }
            }
        }
        if(!$atLeastOneStoreEnabled) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Pbint',
                    'Product catalog upload issue ', ' ShipperHQ Pitney is not set to active for any of your stores,
                     Please enable in at least one store before attempting to upload catalog');
            }
        }
        if($this->file) {
            fclose($this->file);
        }
        else {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Pbint',
                    'Product catalog upload issue ', 'No upload file object was created');
            }
        }
        $this->_stripPartFromFileName($part);
        //fwrite($this->file,"</CategoryList>\n<CommodityList>\n");
        $fileRecordCount = 0;
        $part = 1;
        $this->_createNewCommoditiyFile($part);
        $part++;
        $addedProducts = array();
        if(count($addedCategories) == 0) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Pbint',
                    'Product catalog upload ', 'During processing, no product categories were found. Please review full log files for details');
            }
        }

        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('country_of_manufacture')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('product_url')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('shipperhq_pbint_upload')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('weight');

        $productCollection->setPageSize(100);

        $pages = $productCollection->getLastPageNumber();
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Product catalog upload ', 'There are ' .$pages.' pages x 100 products to process');
        }
        $currentPage = 1;

        do {
            $productCollection->setCurPage($currentPage);
            $productCollection->load();

            foreach ($productCollection as $product) {
                if ($product->getTypeId() == 'virtual') {
                    continue;
                }

                if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'bundle') {
                    //as we'll use the child products only
                    continue;
                }

                $cateIds = $product->getCategoryIds();
                $cateId = 0;
                foreach ($cateIds as $cId) {
                    $cateId = $cId; //get lower level of category
                }

                $prodCat = $this->_getSelectedCategory($addedCategories, $cateId);
                if (!$prodCat) {
                    $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                        ->getParentIdsByChild($product->getId());
                    if(empty($parentIds)) {
                        $parentIds = Mage::getResourceSingleton('bundle/selection')
                            ->getParentIdsByChild($product->getId());
                        if(empty($parentIds)) {
                            if ($this->_debug) {
                                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                                    'Product catalog upload ', 'unable to find an exported category for SKU ' .$product->getSku() .' and no assigned parents on this product');
                            }
                            continue;
                        }
                    }
                    $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                    foreach ($parent->getCategoryIds() as $cId) {
                        $cateId = $cId; //get lower level of category
                    }
                    $prodCat = $this->_getSelectedCategory($addedCategories, $cateId);
                    if(!$prodCat) {
                        if ($this->_debug) {
                            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                                'Product catalog upload ', 'unable to find an exported category for SKU ' .$product->getSku() .' - category ID is ' .$cateId);
                        }
                        continue; //can't find the category
                    }
                }
                $baseURL = Mage::app()->getStore($category->getStoreId())->getBaseUrl();
                $productUrlFormat = $baseURL . "catalog/product/view/id/%d/";

                $cIds = explode('/', $prodCat->getPath());
                $cIds = array_slice($cIds, 1); //remove root category
                $prodCat->setData('id_path', implode(':', $cIds));
                $prodCat->setData('name_path', $this->_getCatNamePath($addedCategories, $cIds));

                $pbProduct = new Shipperhq_Pbint_Model_Catalog_Product($product, sprintf($productUrlFormat, $product->getId()));
                if ($fileRecordCount > $maxRecordsCount) {
                    $this->_createNewCommoditiyFile();
                    $fileRecordCount = 0;
                    $part++;
                }
                $this->writeProduct($pbProduct, $cateId, null, $prodCat);
                $prodCount++;
                $fileRecordCount++;
                $addedProducts[$product->getSku()] = "added";

            }

            $currentPage++;
            //clear collection and free memory
            $productCollection->clear();
        } while ($currentPage <= $pages);

        if($this->file) {
            fflush($this->file);
        }
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Product upload - Completed file creation', 'Processed ' .$prodCount .' products');
        }

        $this->_stripPartFromFileName($part);
        return true;
    }

    private function _getCatNamePath($categories, $cateIds)
    {

        $names = array();
        foreach ($cateIds as $id) {
            foreach ($categories as $cat) {
                if ($cat->getId() == $id) {
                    array_push($names, $cat->getName());
                    break;
                }
            }
        }
        return implode('|', $names);
    }

    /**
     * Updates the shipperhq_pbint_upload time in all uploaded products
     */
    public function updateLastProductUpload()
    {

        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'shipperhq_pbint_upload');
        if (!$attribute->getAttributeId()) {
            $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
            $setup->addAttribute('catalog_product', 'shipperhq_pbint_upload', array(
                'label' => 'Last Pb upload timestampt',
                'type' => 'varchar',
                'input' => 'text',
                'visible' => false,
                'required' => false,
                'position' => 1,
            ));
        }

        $productIds = array_unique($this->productIds);
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Uploaded products', count($productIds));
        }
        $updated = 0;
        foreach ($productIds as $prodId) {
            $product = Mage::getModel("catalog/product")->load($prodId);
            $product->setPbPbgspUpload(time());
            try {
                $product->save();
                $updated++;
            } catch (Exception $e) {
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                        'There was a problem saving the product with sku', $product->getSku() . " Error Message \n" . $e->getMessage());
                }
            }

        }
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'shipperhq_pbint_upload', $updated . ' products updated');
        }
    }

    /**
     * Uploads the xml file to clearpath SFTP server
     */

    private function _getNotificationDir()
    {
        $tmpDir = $this->_getTempDir();
        $configOptions = Mage::getModel('core/config_options');
        $notificationDir = $tmpDir . 'notifications';
        $configOptions->createDirIfNotExists($notificationDir);
        chmod($notificationDir, 0777);
        return $notificationDir;
    }

    public function processStatusNotifications()
    {
        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();
        try {
            $adminEmail = Shipperhq_Pbint_Model_Credentials::getAdminEmail();
            if (!isset($adminEmail) || $adminEmail == '')
                return;
            $notificationDir = $this->_getNotificationDir();
            $this->_downloadStatusNotifications($notificationDir);
            $notificationFiles = array_diff(scandir($notificationDir), array('..', '.'));
            if (count($notificationFiles) > 0) {
                $mail = new Zend_Mail();
                $mail->setFrom('no-reply@pb.com', 'Pitney Bowes');
                $mail->addTo($adminEmail)
                    ->setSubject('Catalog Export Error')
                    ->setBodyText('Catalog Export Error. Please see attached files.');
                $fileCount = 0;
                foreach ($notificationFiles as $notificationFile) {
                    if ($this->_endsWith($notificationFile, '.err') || $this->_endsWith($notificationFile, '.log')) {
                        $file = $notificationDir . '/' . $notificationFile;
                        $at = new Zend_Mime_Part(file_get_contents($file));
                        $at->filename = basename($file);
                        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                        $at->encoding = Zend_Mime::ENCODING_8BIT;

                        $mail->addAttachment($at);
                        $fileCount++;
                    }

                }
                if ($fileCount > 0) {
                    $mail->send();
                    if ($this->_debug) {
                        Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                            'ProcessStatusNotifications', 'Email sent with error files.');
                    }
                } else {
                    if ($this->_debug) {
                        Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                            'ProcessStatusNotifications', 'No error files found.');
                    }
                }
                //keep these files until next upload and delete files from old upload
                $this->_cleanNotificationFiles();
            }
        } catch (Exception $e) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'ProcessStatusNotifications', 'Error in processStatusNotifications:' . $e->getMessage());
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Full trace of exception: ', $e->getTraceAsString());
            }
        }
    }

    private function _cleanNotificationFiles()
    {
        //keep resent files until next upload and delete files from old upload
        $lastExportedFiles = $this->_getLastExportedFileNames();
        if (!$lastExportedFiles)
            return;
        $notificationDir = $this->_getNotificationDir();
        $notificationFiles = array_diff(scandir($notificationDir), array('..', '.'));

        foreach ($notificationFiles as $notificationFile) {
            $path_parts = pathinfo($notificationFile);
            $localFileNameWithoutExt = $path_parts['filename'];
            $isOldFile = true;
            foreach ($lastExportedFiles as $lastExportedFile) {
                $lastExportedFileNameWithoutExt = str_replace('.gpg', '', str_replace('.csv', '', $lastExportedFile));
                if ($lastExportedFileNameWithoutExt == $localFileNameWithoutExt) {
                    $isOldFile = false;
                    break;
                }
            }
            if ($isOldFile) {
                //this is from 2nd last upload, remove it from disk
                unlink($notificationDir . '/' . $notificationFile);
            }
        }

    }

    private function _downloadStatusNotifications($notificationDir)
    {

        $credentials = $this->_getSftpCredentials();
        try {
            $sftpDumpFile = new Varien_Io_Sftp();
            $sftpDumpFile->open(
                $credentials
            );
            $rootDir = Shipperhq_Pbint_Model_Credentials::getSftpCatalogDirectory();
            if (!$this->_endsWith($rootDir, '/'))
                $rootDir = $rootDir . '/';
            $processedDir = $rootDir . 'outbound';
            $sftpDumpFile->cd($processedDir);
            $files = $sftpDumpFile->ls();

            $exportedFiles = $this->_getLastExportedFileNames();
            if (!$exportedFiles)
                return;
            foreach ($files as $file) {
                foreach ($exportedFiles as $exportedFile) {
                    $fileNameWithoutExtension = str_replace(".gpg", "", str_replace(".csv", "", $exportedFile));
                    if ($this->_startsWith($file['text'], $fileNameWithoutExtension)) {
                        $dest = $notificationDir . '/' . $file['text'];
                        $sftpDumpFile->read($file['text'], $dest);
                    }
                }

            }
            $sftpDumpFile->close();
        } catch (Exception $e) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'Pb Module could not connect to sftp server: ',
                    $credentials['host'], $e->getMessage());
            }
            return;
        }

    }

    private function _encryptExportedFiles($exportedFiles)
    {
        $encryptedFiles = array();
        $publicKey = Shipperhq_Pbint_Model_Credentials::getPublicKey();
        if (!isset($publicKey) || $publicKey == '') {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint',
                    'EncryptExportedFiles', 'Public key is not set cannot encrypt catalog files.');
            }
            return $exportedFiles;
        }
        try {
            $gnupg = new gnupg();
            $keyInfo = $gnupg->import($publicKey);
            $gnupg->addencryptkey($keyInfo['fingerprint']);
            $tmpDir = $this->_getTempDir();
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'EncryptExportedFiles', 'Encrypting the files.');
            }
            foreach ($exportedFiles as $exportedFile) {
                $fileName = $tmpDir . $exportedFile;
                if (is_dir($fileName))
                    continue;
                $encryptedFileName = $exportedFile . '.gpg';
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'EncryptExportedFiles', 'Encrypted file' . $encryptedFileName);
                }
                file_put_contents($tmpDir . $encryptedFileName, $gnupg->encrypt(file_get_contents($fileName)));
                $encryptedFiles[] = $encryptedFileName;
            }
            return $encryptedFiles;
        } catch (Exception $e) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint', 'Error in encryption', $e->getTraceAsString());
            }
            return $encryptedFiles;
        }


    }

    public function upload()
    {
        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();

        $tmpDir = $this->_getTempDir();
        $exportedFiles = array_diff(scandir($tmpDir), array('..', '.'));
        if (count($this->productIds) == 0) {
            // No new products to send, don't send anything.
            $this->_removeExportedFiles($exportedFiles);
            return;
        }
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload', 'Pb catalog file upload started for ' .count($this->productIds) .' products');
        }
        try {

            if (Shipperhq_Pbint_Model_Credentials::isEncryptionEnabled()) {
                $exportedFiles = $this->_encryptExportedFiles($exportedFiles);
            } else {
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload',
                        'Encryption is not enabled.' . Shipperhq_Pbint_Model_Credentials::isEncryptionEnabled());
                }
            }
            $sftpDumpFile = new Varien_Io_Sftp();
            $credentials = $this->_getSftpCredentials();

            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload', $credentials);
            }
            $sftpDumpFile->open(
                $credentials
            );
            //Upload to SFTP
            $rootDir = Shipperhq_Pbint_Model_Credentials::getSftpCatalogDirectory();
            if (!$this->_endsWith($rootDir, '/'))
                $rootDir = $rootDir . '/';
            $tmpSFTPDir = $rootDir . 'tmp';
            $inboundDir = $rootDir . 'inbound';
            $uploadedFiles = array();
            foreach ($exportedFiles as $exportedFile) {
                $fileName = $tmpDir . $exportedFile;
                if (is_dir($fileName))
                    continue;

                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload', 'CD to ' . $tmpSFTPDir);
                }
                $sftpDumpFile->cd($tmpSFTPDir);
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload', 'Uploading: ' . $fileName);
                }
                $writeOutcome = $sftpDumpFile->write($exportedFile, file_get_contents($fileName));

                if($writeOutcome) {
                    if ($this->_debug) {
                        Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload', 'File upload outcome was ' . $writeOutcome
                            . " - now moving " . $tmpSFTPDir . "/$exportedFile" . " to " . $inboundDir . "/$exportedFile");
                    }
                    $sftpDumpFile->mv($tmpSFTPDir . "/$exportedFile", $inboundDir . "/$exportedFile");
                    $uploadedFiles[] = $exportedFile;
                }
                else {
                    if ($this->_debug) {
                        Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint', 'upload', 'File upload outcome was ' . $writeOutcome
                            . ' Unable to write file' . $tmpSFTPDir . "/$exportedFile");
                    }
                }
            }
            $sftpDumpFile->close();

        } catch (Exception $e) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postWarning('Shipperhq_Pbint', 'Could not connect to Pitne Bowes sftp server: ',
                    $credentials['host'] . $e->getMessage());
            }
            $message = 'Could not connect to Pitney Bowes sftp server:' .$e->getMessage();
            return $message;
        }

        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'upload',
                'Pb catalog file upload ended');
        }

        $this->_removeExportedFiles($exportedFiles);
        $this->updateLastProductUpload();
        $this->_logExportedFileInDB($uploadedFiles);
        return true;
    }

    private function _getExportedFilesVariable()
    {
        $collection = Mage::getModel("shipperhq_pbint/variable")->getCollection();
        $exportedFilesVariable = null;
        foreach ($collection as $variable) {

            if ($variable->getName() == "exportedFiles") {
                $exportedFilesVariable = $variable;
                break;
            }

        }
        return $exportedFilesVariable;
    }

    private function _getLastExportedFileNames()
    {
        $exportedFilesVariable = $this->_getExportedFilesVariable();
        if (!isset($exportedFilesVariable))
            return false;
        $exportedFiles = explode('|', $exportedFilesVariable->getValue());
        return $exportedFiles;
    }

    private function _logExportedFileInDB($exportedFiles)
    {
        $strExportedFiles = implode('|', $exportedFiles);
        $exportedFilesVariable = $this->_getExportedFilesVariable();
        if (!isset($exportedFilesVariable)) {
            $exportedFilesVariable = Mage::getModel("shipperhq_pbint/variable");
            $exportedFilesVariable->setName("exportedFiles");
        }

        $exportedFilesVariable->setValue($strExportedFiles);
        $exportedFilesVariable->save();
    }

    private function _startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }

    private function _endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    private function _removeExportedFiles($exportedFiles)
    {
        $tmpDir = $this->_getTempDir();
        foreach ($exportedFiles as $exportedFile) {
            $fileName = $tmpDir . $exportedFile;
            if (is_dir($fileName))
                continue;
            unlink($fileName);
            $fileName = str_replace(".gpg", "", $fileName); //remove unencrypted file
            if (is_file($fileName))
                unlink($fileName);
        }
    }


    /**
     * @param Shipperhq_Pbint_Model_Catalog_Product $product
     * @param string $categoryCode
     */
    private function writeProduct($product, $categoryCode, $parentSku, $category)
    {
        if ($product->shouldUpload($this->lastDiff)) {
            array_push($this->productIds, $product->getMageProduct()->getId());
            $product->writeToFile($this->file, $categoryCode, $parentSku, $category);
            fflush($this->file);
        }
    }

    public static function stripHtml($text)
    {
        return preg_replace("/<\s*\/\s*\w\s*.*?>|<\s*br\s*>/", '', preg_replace("/<\s*\w.*?>/", '', $text));
    }

    /**
     * Loads products without categories and logs them in log file
     */
    public function logProdWithoutCategories()
    {
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(0)
            ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
            ->addAttributeToFilter('category_id', array('null' => true))
            ->addAttributeToSelect('*');


        $productCollection->getSelect()->group('product_id')->distinct(true);

        $productCollection->load();
        $skus = '';
        foreach ($productCollection as $product) {
            $skus = $skus . $product->getSku() . ",";

        }
        if (Mage::helper('shipperhq_pbint')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint', 'Products without categories:',
                $skus);
        }
    }

    /**
     * @return array
     */
    private function _getSftpCredentials()
    {
        $credentials = array(
            'host' => Shipperhq_Pbint_Model_Credentials::getSftpHostname(),
            "port" => Shipperhq_Pbint_Model_Credentials::getSftpPort(),
            'username' => Shipperhq_Pbint_Model_Credentials::getSftpUsername(),
            'password' => Shipperhq_Pbint_Model_Credentials::getSftpPassword(),
            'timeout' => '10'
        );
        return $credentials;
    }

    /**
     * @param $part
     */
    private function _stripPartFromFileName($part)
    {
        if ($part > 0) {
            //there is only one part remove part1 from filename
            rename($this->filename, str_replace('_part1', '', $this->filename));
        }
    }
}

?>
