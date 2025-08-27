# BileMo API

API REST pour catalogue de téléphones mobiles

## Installation

```bash
composer install
```



```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php bin/console lexik:jwt:generate-keypair
```

## Démarrage

```bash
symfony serve
```

## Utilisation

### Authentification
```http
POST /api/auth/login
{
  "email": "admin@techstore.com",
  "password": "password123"
}
```

### Endpoints
- `GET /api/products` - Liste téléphones
- `GET /api/products/{id}` - Détail téléphone
- `GET /api/users` - Liste utilisateurs
- `POST /api/users` - Créer utilisateur
- `DELETE /api/users/{id}` - Supprimer utilisateur

## Documentation
- Swagger : `/api/doc`
- GitHub Pages : https://username.github.io/bilemo-api
