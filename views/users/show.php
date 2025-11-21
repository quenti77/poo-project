<?php
/**
 * @var Router $router
 * @var User $user
 */

use App\Entities\User;
use Tuto\Routing\Router;
?>
<div>
    <h1>Information utilisateur #<?= $user->getId() ?></h1>

    <p>Utilisateur dans le groupe <?= $user->getGroup()->getName() ?></p>
    <p>Groupe crée le <?= $user->getGroup()->getCreatedAt()->format('d/m/Y H:i:s') ?></p>

    <a href="<?= $router->generate('users.index') ?>">Retour</a>
</div>
