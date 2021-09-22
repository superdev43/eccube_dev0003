<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-07-20 10:45:26              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPayV2\Repository\Master;use Eccube\Repository\AbstractRepository;use Plugin\AmazonPayV2\Entity\Master\AmazonStatus;use Symfony\Bridge\Doctrine\RegistryInterface;class AmazonStatusRepository extends AbstractRepository{public function __construct(RegistryInterface $registry){parent::__construct($registry, AmazonStatus::class);}}