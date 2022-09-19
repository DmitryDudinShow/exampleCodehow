<?php
/**
 * Шаблон генерации Entity
 * @var \Glavfinans\Core\EntityGenerator\EntityManager $entityManager
 */
?>
<?= "<?php\n"; ?>

namespace Glavfinans\Core\Entity\<?= $entityManager->getEntityName(); ?>;

use Cycle\Annotated\Annotation as Cycle;

/**
 * Entity <?= $entityManager->getEntityName() . "\n" ?>
 *
 * @Cycle\Entity(
 *     role="Glavfinans\Core\Entity\<?= $entityManager->getEntityName(); ?>\<?= $entityManager->getEntityName(); ?>",
 *     table="<?= $entityManager->getTableName() ?>",
 *     repository="Glavfinans\Core\Entity\<?= $entityManager->getEntityName(); ?>\<?= $entityManager->getEntityName(); ?>Repository",
 *     mapper="Glavfinans\Core\CycleOrm\TimestampedMapper"
 *     )
 */
class <?= $entityManager->getEntityName() . "\n" ?>
{
<?php foreach ($entityManager->getSchemeTable() as $id => $property) { ?>
    /**
     * <?= $entityManager->getComment($id) . "\n" ?>
<?php if (null !== $entityManager->getMessageErrorType($id)){ ?>
     * <?= $entityManager->getMessageErrorType($id) . "\n"; } ?>
     *
     * <?= $entityManager->getCycleAnnotationProperty($id) . "\n"; ?>
     * @var <?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo "null|" ?><?= $entityManager->getTypeProperty($id) . "\n" ?>
     */
    private <?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo "?" ?><?= $entityManager->getTypeProperty($id) ?> $<?= $entityManager->getCamelCase($property['Field']) ?><?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo " = null" ?>;

<?php } ?>
    /** ------------------------------------- Getters start ------------------------------------- */

<?php foreach ($entityManager->getSchemeTable() as $id => $property) { ?>
    /**
     * <?= $entityManager->getCommentForMethods($id) . "\n" ?>
<?php if (null !== $entityManager->getMessageErrorType($id)){ ?>
     * <?= $entityManager->getMessageErrorType($id) . "\n"; } ?>
     *
     * @return <?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo "null|" ?><?= $entityManager->getTypeProperty($id) . "\n" ?>
     */
    public function get<?= $entityManager->getPascalCase($property['Field']) ?>(): <?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo "?" ?><?= $entityManager->getTypeProperty($id) . "\n" ?>
    {
        return $this-><?= $entityManager->getCamelCase($property['Field']) ?>;
    }

<?php } ?>
    /** ------------------------------------- Getters end ------------------------------------- */
    /** ------------------------------------- Setters start ------------------------------------- */

<?php foreach ($entityManager->getSchemeTable() as $id => $property) { ?>
    /**
     * <?= $entityManager->getCommentForMethods($id, true) . "\n" ?>
<?php if (null !== $entityManager->getMessageErrorType($id)){ ?>
     * <?= $entityManager->getMessageErrorType($id) . "\n"; } ?>
     *
     * @param <?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo "null|" ?><?= $entityManager->getTypeProperty($id) . "\n" ?>
     * @return void
     */
    public function set<?= $entityManager->getPascalCase($property['Field']) ?>(<?php if ($entityManager->isNullProperty($id) || $property['Field'] === 'id') echo "?" ?><?= $entityManager->getTypeProperty($id) ?> $<?= $entityManager->getCamelCase($property['Field']) ?>): void
    {
        $this-><?= $entityManager->getCamelCase($property['Field']) ?> = $<?= $entityManager->getCamelCase($property['Field']) ?>;
    }

<?php } ?>
    /** ------------------------------------- Setters end ------------------------------------- */
}
