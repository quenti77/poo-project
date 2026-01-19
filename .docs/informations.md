## ServiceProvider

<!-- TOC -->
  * [ServiceProvider](#serviceprovider)
  * [spread operator](#spread-operator)
    * [Comme paramètre de fonction / méthode](#comme-paramètre-de-fonction--méthode)
    * [Comme élément avec les tableaux](#comme-élément-avec-les-tableaux)
    * [Comme arguments de fonction et méthode](#comme-arguments-de-fonction-et-méthode)
    * [Pour créer une fonction anonyme de 1 seul argument.](#pour-créer-une-fonction-anonyme-de-1-seul-argument)
<!-- TOC -->

Dans le Framework actuel, le service provider permet d'externaliser la configuration de l'application.
Par exemple, la liste des événements de l'application se trouve dans **app/Providers/EventServiceProvider.php**.

## spread operator

L'opérateur `...` peut servir à plusieurs choses en fonction du context dans le code.

### Comme paramètre de fonction / méthode

Si on indique un paramètre avec cet opérateur, alors cela signifie que le nombre de paramètres est variables.
Cela retourne un tableau de tous les arguments fourni. Ce dernier peut être typé.

```php
function addition(int ...$allNumbers): int {}

addition(1);
addition(1, 2);
addition(3, 4, 5, 9);
```

Si on prend `addition(3, 4, 5, 9);` alors `$allNumbers` il vaut `[3, 4, 5, 9]`.

### Comme élément avec les tableaux

On peut s'en servir pour prendre chaque élément d'un tableau et l'utiliser comme argument. Par exemple :

```php
$tab1 = [1, 2, 3];
$tab2 = [4, 5, ...$tab1];
```

Alors `$tab2` il vaut `[4, 5, 1, 2, 3]`. Mais si on avait mis ailleurs (au début par exemple), alors on aurait eu :

```php
$tab1 = [1, 2, 3];
$tab2 = [...$tab1, 4, 5]; // [1, 2, 3, 4, 5]
```

C'est utilisable avec des tableaux associatifs et dans ces cas-là cela correspond à faire un `array_merge` :

```php
$users = ['john' => 'Doe', 'marc' => 'Petrovich'];
$finalUsers = ['john' => 'Developer', ...$users];
```

Dans cet exemple, la clef et valeur `john => Developer` sera écrasé par la clef et valeur `john => Doe`.

### Comme arguments de fonction et méthode

On peut fournir une liste d'arguments avec un tableau :

```php
function multiplication(int $a, int $b): int {}

$tab = [4, 6];
multiplication(...$tab); // 24
```

En gros `$a` correspondra au premier élément du tableau et `$b` aura le second élément. Attention à le faire en fin
d'argument de la fonction ou méthode.

```php
function multiplication(int $a, int $b, int $c): int {}

$tab = [4, 6];
multiplication(2, ...$tab); // 48
multiplication(...$tab, 2); // error
```

### Pour créer une fonction anonyme de 1 seul argument.

Si on veut créer une fonction anonyme qui fait un appel à notre fonction ou méthode. Cela permet de simplifier le code.

```php
array_filter([0, '', 'salut'], static fn ($item) => empty($item))
// On voit que l'on pourrait faire directement empty
array_filter([0, '', 'salut'], empty(...))
```
