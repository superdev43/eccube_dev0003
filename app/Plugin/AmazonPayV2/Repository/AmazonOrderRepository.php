<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-07-20 10:45:26              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPayV2\Repository;use Doctrine\ORM\EntityRepository;class AmazonOrderRepository extends EntityRepository{public $config;protected $app;public function setApplication($app){$this->app = $app;}public function setConfig(array $config){$this->config = $config;}public function getAmazonOrderByOrderDataForAdmin($Orders){goto OGd5L;OGd5L:$AmazonOrders = [];goto dlmls;VwOlP:MvnfP:goto zCXye;zCXye:return $AmazonOrders;goto x3ULc;dlmls:foreach ($Orders as $Order) {goto Zd2uH;wilKG:fREv6:goto ckzGi;PFPEr:if (empty($AmazonOrder)) {goto fREv6;}goto iI7uL;Zd2uH:$AmazonOrder = $this->findby(['Order' => $Order]);goto PFPEr;iI7uL:$AmazonOrders[] = $AmazonOrder[0];goto wilKG;ckzGi:QiGtg:goto C3mZc;C3mZc:}goto VwOlP;x3ULc:}}