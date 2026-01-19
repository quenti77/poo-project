## How to create a blog

### Migrations (tables)

Pour commencer, on va définir ce que l'on veut avoir comme données.

Table **posts** :

|          nom | type         | options              |
| -----------: | ------------ | -------------------- |
|           id | ulid         | primary              |
|    author_id | ulid         | foreign key(user.id) |
|        title | varchar(255) | -                    |
|         slug | varchar(255) | unique               |
|      content | text         | -                    |
| published_at | datetime     | nullable             |
|   created_at | datetime     | -                    |
|   updated_at | datetime     | -                    |

Table **comments** :

|        nom | type     | options                     |
| ---------: | -------- | --------------------------- |
|         id | ulid     | primary                     |
|    post_id | ulid     | foreign key(post.id)        |
|  author_id | ulid     | foreign key(user.id)        |
|    content | text     | -                           |
|     status | enum     | pending, approved, rejected |
| created_at | datetime | -                           |
| updated_at | datetime | -                           |


### Routing

Ensuite on a besoin de quelles routes ? (Toute basé sur `/{lang?}/`)

Routes pour les **articles** :

| method | path                    | name          | middlewares               |
| -----: | ----------------------- | ------------- | ------------------------- |
|    GET | /posts                  | posts.index   | -                         |
|    GET | /posts/{postId}         | posts.show    | -                         |
|    GET | /posts/create           | posts.create  | auth:user                 |
|   POST | /posts                  | posts.store   | auth:user                 |
|    GET | /posts/{postId}/edit    | posts.edit    | auth:user if author,admin |
|  PATCH | /posts/{postId}         | posts.update  | auth:user if author,admin |
| DELETE | /posts/{postId}         | posts.delete  | auth:user if author,admin |

Routes pour les **commentaires** :

| method | path                       | name            | middlewares               |
| -----: | -------------------------- | --------------- | ------------------------- |
|   POST | /comments                  | comments.store  | auth:user                 |
|    GET | /comments/{commentId}/edit | comments.edit   | auth:user if author,admin |
|  PATCH | /comments/{commentId}      | comments.update | auth:user if author,admin |
| DELETE | /comments/{commentId}      | comments.delete | auth:user if author,admin |

Routes pour les **admins** : (Toutes les routes auront le middleware `auth:admin`)

| method | path                                 | name                    | middlewares |
| -----: | ------------------------------------ | ----------------------- | ----------- |
|    GET | /admin/posts                         | admin.posts.index       | -           |
|    GET | /admin/posts/{postId}                | admin.posts.show        | -           |
|   POST | /admin/posts/{postId}/publish        | admin.posts.publish     | -           |
|  PATCH | /admin/posts/{postId}                | admin.posts.update      | -           |
| DELETE | /admin/posts/{postId}                | admin.posts.delete      | -           |
|    GET | /admin/comments                      | admin.comments.index    | -           |
|    GET | /admin/comments/{commentId}          | admin.comments.show     | -           |
|   POST | /admin/comments/{commentId}/validate | admin.comments.validate | -           |
|  PATCH | /admin/comments/{commentId}          | admin.comments.update   | -           |
| DELETE | /admin/comments/{commentId}          | admin.comments.delete   | -           |


On remarque que l'on aura besoin de plusieurs middlewares :
- Un middleware qui vérifie si on est connecté et le rang minimum à avoir
- Un middleware qui regarde si l'auteur est le même que la personne connecté ou que c'est un admin.


### Les vues

Création des vues et du layout :

```
📁 views/
├── 📁 layouts
│   ├── 📄 admin.twig
│   └── 📄 base.twig
├── 📁 comments
│   └── 📄 edit.twig
├── 📁 posts
│   ├── 📄 create.twig
│   ├── 📄 index.twig
│   └── 📄 edit.twig
└── 📁 admin
    ├── 📁 posts
    │   ├── 📄 index.twig
    │   └── 📄 edit.twig
    └── 📁 comments
        ├── 📄 index.twig
        └── 📄 edit.twig
```

Installation de [CodeBase Theme](https://demo.pixelcave.com/codebase/be_pages_dashboard.html)


