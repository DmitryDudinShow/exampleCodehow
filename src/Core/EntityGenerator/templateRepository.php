<?php
/**
 * Шаблон генерации Репозитория
 * @var \Glavfinans\Core\EntityGenerator\EntityManager $entityManager
 */
?>
<?= "<?php\n"; ?>

namespace Glavfinans\Core\Entity\<?= $entityManager->getEntityName(); ?>;

use Cycle\ORM\Select\Repository;

/**
 * Репозиторий для <?= $entityManager->getEntityName() . "\n" ?>
 */
class <?= $entityManager->getEntityName() . "Repository extends Repository implements I" . $entityManager->getEntityName() . "Repository" . "\n" ?>
{

}
