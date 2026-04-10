# 🛒 HandShop — E-commerce associatif avec Stripe

Boutique en ligne pour un club sportif. Catalogue de produits (maillots, équipements, goodies), panier, paiement sécurisé via Stripe Checkout, et gestion des commandes avec webhooks asynchrones.

> Conçu comme un module indépendant, intégrable à terme dans le site [Senpereko Eskubaloia](https://senpereko-eskubaloia.com).

## Points techniques clés

| Domaine | Détail |
|---|---|
| **Back-end** | PHP 8.3 · Symfony 7 · API Platform |
| **Front-end** | React · Vite · Tailwind CSS |
| **Paiement** | Stripe Checkout · Webhooks · signature verification |
| **Asynchrone** | Symfony Messenger (traitement webhook + emails) |
| **Base de données** | PostgreSQL 16 (Neon en prod) |
| **Infrastructure** | Docker Compose · GitHub Actions CI/CD |
| **Déploiement** | Render (API) + Vercel (front) |

## Architecture

```
front/  → Catalogue, panier, checkout (React)  → localhost:5173
api/    → API REST + Stripe webhooks (Symfony)  → localhost:8080
```

## Entités

| Entité | Description |
|--------|-------------|
| `Product` | Nom, description, prix, image, stock, catégorie |
| `ProductVariant` | Taille, couleur, stock par variante |
| `Cart` / `CartItem` | Panier (session ou user), items avec quantités |
| `Order` / `OrderItem` | Commande validée, statut, référence Stripe |
| `Payment` | Stripe session ID, statut, montant, timestamps |

## API endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/products` | Liste des produits (paginée, filtrable) |
| `GET` | `/api/products/{id}` | Détail d'un produit + variantes |
| `POST` | `/api/cart/items` | Ajouter un article au panier |
| `GET` | `/api/cart` | Contenu du panier |
| `PATCH` | `/api/cart/items/{id}` | Modifier la quantité |
| `DELETE` | `/api/cart/items/{id}` | Retirer un article |
| `POST` | `/api/checkout` | Créer une session Stripe Checkout |
| `POST` | `/api/stripe/webhook` | Réception des événements Stripe |
| `GET` | `/api/orders` | Historique des commandes |
| `GET` | `/api/orders/{id}` | Détail d'une commande |

## Flux de paiement

```
1. L'utilisateur ajoute des produits au panier
2. Il clique sur "Commander" → POST /api/checkout
3. Le back crée une Stripe Checkout Session et renvoie l'URL
4. Le front redirige vers la page de paiement Stripe
5. Stripe envoie un webhook checkout.session.completed
6. Le back vérifie la signature, dispatch un message Messenger
7. Le handler crée la commande, met à jour le stock, envoie l'email
```

## Installation locale

### Prérequis

- Docker Desktop
- Make
- Git
- [Stripe CLI](https://stripe.com/docs/stripe-cli) (pour les webhooks en dev)
- Un compte Stripe (mode test) → [dashboard.stripe.com](https://dashboard.stripe.com/test/apikeys)

### Démarrage rapide

```bash
# 1. Cloner le projet
git clone git@github.com:alex64dev/handshop.git
cd handshop

# 2. Configurer les variables d'environnement
cp .env.example .env
# Éditez .env avec vos clés Stripe (mode test)

# 3. Lancer le projet
make install

# 4. Dans un second terminal, écouter les webhooks Stripe
make stripe-listen
```

### URLs locales

| Service | URL |
|---------|-----|
| Frontend | [localhost:5173](http://localhost:5173) |
| API | [localhost:8080/api](http://localhost:8080/api) |
| MailHog | [localhost:8025](http://localhost:8025) |

### Commandes utiles

```bash
make start          # Démarrer les conteneurs
make stop           # Arrêter les conteneurs
make logs           # Voir les logs
make shell          # Shell dans le conteneur PHP
make lint           # Linting PHP + JS
make fix            # Correction automatique du style PHP
make test           # Lancer les tests
make db-migrate     # Exécuter les migrations
make stripe-listen  # Écouter les webhooks Stripe (dev)
```

## Stripe en mode test

Le projet fonctionne entièrement en mode test Stripe. Utilisez les cartes de test :

| Carte | Résultat |
|-------|----------|
| `4242 4242 4242 4242` | Paiement réussi |
| `4000 0000 0000 3220` | Authentification 3D Secure |
| `4000 0000 0000 0002` | Paiement refusé |

Date d'expiration : n'importe quelle date future. CVC : n'importe quel nombre à 3 chiffres.

## Sécurité

- Vérification de la signature Stripe sur chaque webhook (`Stripe-Signature` header)
- Traitement asynchrone des webhooks via Messenger (évite les timeouts)
- Idempotence : un même événement Stripe traité une seule fois
- Validation des prix côté serveur (jamais confiance au front)
- CSRF sur les formulaires
- CORS configuré par variable d'environnement

## CI/CD

Le pipeline GitHub Actions exécute à chaque push :

1. **Lint** — PHP CS Fixer + ESLint
2. **Analyse statique** — PHPStan niveau 6
3. **Tests** — PHPUnit avec base PostgreSQL
4. **Build** — Frontend + images Docker (sur `main`)

## Auteur

Développé par **Alexandre Peant**

## Licence

MIT
