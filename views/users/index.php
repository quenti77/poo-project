<?php
/**
 * @var Router $router
 * @var int[] $users
 */
use Tuto\Routing\Router;
?>
<div>
    <?php foreach ($users as $user): ?>
        <a href="<?= $router->generate('users.show', ['userId' => $user->getId()]) ?>">
            Go to user #<?= $user->getId() ?>
        </a>
    <?php endforeach; ?>
</div>