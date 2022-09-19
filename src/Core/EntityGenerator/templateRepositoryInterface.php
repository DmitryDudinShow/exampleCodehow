<?php
/**
 * Шаблон генерации Интерфейса репозитория
 * @var \Glavfinans\Core\EntityGenerator\EntityManager $entityManager
 */
?>
<?= "<?php\n"; ?>

namespace Glavfinans\Core\Entity\<?= $entityManager->getEntityName(); ?>;

use Cycle\ORM\RepositoryInterface;

/**
* Интерфейс репозитория для <?= $entityManager->getEntityName() . "\n" ?>
*/
interface I<?= $entityManager->getEntityName() . "Repository extends RepositoryInterface" . "\n" ?>
{

}
