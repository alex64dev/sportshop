# HandShop — E-commerce associatif avec Stripe

Boutique en ligne pour un club sportif. Catalogue de produits (maillots, équipements, goodies), panier, paiement sécurisé via Stripe Checkout, gestion des commandes avec webhooks asynchrones, et backoffice d'administration complet.

> Conçu comme un module indépendant, intégrable à terme dans le site [Senpereko Eskubaloia](https://senpereko-eskubaloia.com).

## Points techniques clés

| Domaine | Détail |
|---|---|
| **Back-end** | PHP 8.3 · Symfony 7 · EasyAdmin 4 |
| **Front-end** | React · Vite · TypeScript · Tailwind CSS |
| **Paiement** | Stripe Checkout · Webhooks · signature verification |
| **Asynchrone** | Symfony Messenger (traitement webhook + emails) |
| **Sécurité** | CSRF · rate limiting · login throttling · tokens hashés SHA256 |
| **Base de données** | PostgreSQL 16 (Neon en prod) |
| **Infrastructure** | Docker Compose · GitHub Actions CI/CD |
| **Qualité** | PHP CS Fixer · PHPStan niveau 6 · PHPUnit · ESLint |
| **Déploiement** | Render (API) + Vercel (front) |

## Architecture

```
front/  → Catalogue, panier, checkout (React SPA)       → localhost:5173
api/    → API REST + Backoffice admin (Symfony)          → localhost:8080
        ├── /api/*    Endpoints publics (stateless)
        └── /admin/*  Backoffice EasyAdmin (stateful, auth requise)
```

## Entités

| Entité | Description |
|--------|-------------|
| `User` | Administrateurs du backoffice (email, rôles, mot de passe hashé, verrouillage, reset token) |
| `Product` | Nom, description, slug, prix (centimes), image, catégorie (enum), statut actif |
| `ProductVariant` | Taille, couleur, stock par variante, SKU unique |
| `Cart` / `CartItem` | Panier anonyme par session (UUID), items avec quantités, validation stock |
| `Order` / `OrderItem` | Commande validée, référence unique (HS-YYYYMMDD-XXXX), statut, données dénormalisées |
| `Payment` | Stripe session/payment intent ID, statut, montant, date de paiement |
| `StripeEventLog` | Idempotence des webhooks (un événement traité une seule fois) |

## Backoffice admin

Le backoffice EasyAdmin (`/admin`) permet de :

- **Gérer les produits** — CRUD complet avec variantes inline (tailles, couleurs, stock)
- **Suivre les commandes** — Visualisation, mise à jour du statut (expédié, livré)
- **Consulter les paiements** — Historique Stripe en lecture seule
- **Gérer les administrateurs** — Ajout, verrouillage de comptes, rôles (admin/super admin)
- **Dashboard** — Statistiques (commandes du jour, chiffre d'affaires, alertes stock bas)

## API endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/products` | Liste des produits (paginée, filtrable par catégorie) |
| `GET` | `/api/products/{slug}` | Détail d'un produit + variantes |
| `POST` | `/api/cart/items` | Ajouter un article au panier |
| `GET` | `/api/cart` | Contenu du panier |
| `PATCH` | `/api/cart/items/{id}` | Modifier la quantité |
| `DELETE` | `/api/cart/items/{id}` | Retirer un article |
| `POST` | `/api/checkout` | Créer une session Stripe Checkout |
| `POST` | `/api/stripe/webhook` | Réception des événements Stripe |
| `GET` | `/api/orders/{reference}` | Suivi d'une commande par référence |

## Flux de paiement

```
1. L'utilisateur ajoute des produits au panier
2. Il clique sur "Commander" → POST /api/checkout
3. Le back crée une Order PENDING + une Stripe Checkout Session et renvoie l'URL
4. Le front redirige vers la page de paiement Stripe
5. Stripe envoie un webhook checkout.session.completed
6. Le back vérifie la signature, dispatch un message Messenger (async)
7. Le handler met à jour la commande (PAID), décrémente le stock, envoie l'email de confirmation
```

## Authentification admin

Le système d'authentification du backoffice reprend les mêmes patterns que le site Senpereko Eskubaloia :

- **Connexion** — Formulaire de login avec protection CSRF
- **Throttling** — 5 tentatives max par 15 minutes
- **Verrouillage** — Les comptes peuvent être désactivés par un super admin
- **Reset password** — Token généré (64 chars), stocké hashé en SHA256, expiration 1h, envoi par email
- **Rôles** — `ROLE_ADMIN` (gestion produits/commandes) et `ROLE_SUPER_ADMIN` (gestion des utilisateurs)

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

# 4. Créer un super admin
docker compose exec php php bin/console app:create-super-admin admin@handshop.dev motdepasse

# 5. Dans un second terminal, écouter les webhooks Stripe
make stripe-listen
```

### URLs locales

| Service | URL |
|---------|-----|
| Frontend | [localhost:5173](http://localhost:5173) |
| API | [localhost:8080/api](http://localhost:8080/api) |
| Backoffice | [localhost:8080/admin](http://localhost:8080/admin) |
| MailHog | [localhost:8025](http://localhost:8025) |

### Commandes utiles

```bash
# Docker
make start          # Démarrer les conteneurs
make stop           # Arrêter les conteneurs
make logs           # Voir les logs
make shell          # Shell dans le conteneur PHP

# Qualité
make lint           # PHP CS Fixer + PHPStan + ESLint
make fix            # Correction automatique du style PHP
make test           # Lancer les tests PHPUnit

# Base de données
make db-migrate     # Exécuter les migrations
make entity         # Créer/modifier une entité Doctrine
make migration      # Créer un fichier de migration
make fixtures       # Charger les données de démonstration

# Stripe
make stripe-listen  # Écouter les webhooks Stripe (dev)

# Git
make pr             # Créer une pull request
make pr-merge       # Merger en squash + cleanup
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

- **Stripe** — Vérification de la signature sur chaque webhook, idempotence via `StripeEventLog`
- **Webhooks** — Traitement asynchrone via Messenger (évite les timeouts)
- **Prix** — Validation côté serveur uniquement (jamais confiance au front)
- **Auth** — Login throttling (5/15min), comptes verrouillables, tokens de reset hashés SHA256
- **Sessions** — HTTPOnly, SameSite=Lax, cookie sécurisé en prod
- **CSRF** — Protection sur les formulaires admin
- **CORS** — Configuré par variable d'environnement
- **Rate limiting** — Sur le checkout (5 requêtes/minute)
- **Qualité** — Pre-commit hook bloque les commits si lint ou PHPStan échoue

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
