<?php

class Anymarket_ApiExtension_Model_Observer
{
    /**
     * Perform calls to anymarket API
     *
     * @param $host
     * @return array
     */
    private function doCallAnymarket($host)
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                return array("error" => "1", "message" => $err);
            } else {
                return array("error" => "0", "message" => $response);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $observer
     */
    public function updateOrderAnyMarketObs($observer)
    {
        try {
            if ($observer->getEvent()->getOrder()) {
                $storeId = $observer->getEvent()->getOrder()->getStoreId();
                $incrementId = $observer->getEvent()->getOrder()->getIncrementId();

                $host = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_host_field', $storeId);
                $oi = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_oi_field', $storeId);

                if (empty($host) == false && empty($oi) == false) {
                    $host = $host . "/public/api/anymarketcallback/order/" . $oi . "/MAGENTO_1/" . $storeId . "/" . $incrementId;
                    $this->doCallAnymarket($host);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $observer
     */
    public function sendProdAnyMarket($observer)
    {
        try {
            if ($observer->getEvent()->getProduct()) {
                $productId = $observer->getEvent()->getProduct()->getId();
                $storeId = $observer->getEvent()->getProduct()->getStoreId();

                $sku = $this->getProductSku($productId, $storeId);

                $host = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_host_field', $storeId);
                $oi = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_oi_field', $storeId);

                if (empty($host) == false && empty($oi) == false) {
                    $host = $host . "/public/api/anymarketcallback/product/" . $oi . "/MAGENTO_1/" . $storeId . "/" . $sku;
                    $this->doCallAnymarket($host);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $observer
     * @return array
     */
    public function catalogInventorySave($observer)
    {
        try {
            $item = $observer->getEvent()->getItem();

            if (is_object($item)) {
                $storeId = intval($item->getData('store_id'));
            }

            if ($storeId > 0) {
                $sku = $this->getProductSku($item->getProductId(), $storeId);

                $host = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_host_field', $storeId);
                $oi = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_oi_field', $storeId);

                if (empty($host) == false && empty($oi) == false) {
                    $host = $host . "/public/api/anymarketcallback/stockPrice/" . $oi . "/" . $sku;
                    $this->doCallAnymarket($host);
                }
            } else {
                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                foreach ($product->getStoreIds() as $storeId) {
                    $host = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_host_field', $storeId);

                    if (empty($host) == false) {
                        $oi = Mage::getStoreConfig('anymarket_new_section/anymarket_new_access_group/anymarket_new_oi_field', $storeId);

                        if (empty($oi) == false) {
                            $host = $host . "/public/api/anymarketcallback/stockPrice/" . $oi . "/" . $product->getSku();
                            $this->doCallAnymarket($host);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Get product SKU without loading product model object
     *
     * @param $productId
     * @param $storeId
     * @return mixed
     */
    protected function getProductSku($productId, $storeId)
    {
        $productResource = Mage::getSingleton('catalog/product')->getResource();
        $sku = $productResource->getAttributeRawValue(
            $productId,
            'sku',
            Mage::app()->getStore($storeId)
        );
        return $sku;
    }

}
