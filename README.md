# 📅 Gestion d'emploi du temps scolaire (PHP / MySQL)

Application web de gestion d'emplois du temps pour un établissement : cours, salles, classes, enseignants, disponibilités, devoirs et rendus. Réalisée en binôme (Charles Traoré & Kabran Uriel).

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4) ![MySQL](https://img.shields.io/badge/MySQL-8-4479A1) ![Composer](https://img.shields.io/badge/deps-Composer-885630)

## Fonctionnalités

- **3 rôles** : administrateur, enseignant, étudiant (accès contrôlé par rôle)
- **Emploi du temps** : création / édition / suppression des cours (jour, horaires, salle, classe, enseignant) — `schedule-management.php`
- **Disponibilités des enseignants** — `availability.php`
- **Classes, salles, matières, utilisateurs** — pages d'administration
- **Devoirs** : publication, pièces jointes, dépôt et consultation des rendus
- **Inscription / connexion** avec sessions PHP, mots de passe hachés (`password_hash`), protection **CSRF** et échappement des entrées
- Notifications et messagerie (tables prévues)

## Stack technique

- **PHP 7.4+** (PDO), **MySQL**
- Composer (`phpdotenv`, `phpmailer`)
- HTML / CSS (thème personnalisé), JavaScript

## Structure

```
public/        # pages accessibles (login, dashboard, schedule-management, assignments…)
src/           # configuration, classes Core (Auth, Database), vues partagées
config/        # configuration par variables d'environnement (DB_HOST, DB_NAME…)
database/      # schema.sql et school_schedule.sql (jeu de données anonymisé)
```

## Installation

1. **Prérequis** : PHP 7.4+, MySQL, Composer, un serveur local (XAMPP/WAMP).
2. **Cloner** dans `htdocs/php_calendrier`.
3. **Importer la base** :
   ```bash
   mysql -u root -p -e "CREATE DATABASE school_schedule CHARACTER SET utf8mb4"
   mysql -u root -p school_schedule < database/school_schedule.sql
   ```
4. **Dépendances** : `composer install`
5. **Configurer** la connexion via les variables d'environnement (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) ou dans `config/config.php`.
6. Ouvrir `http://localhost/php_calendrier/public/`.

## Compte de démonstration

| Rôle | Utilisateur | Mot de passe |
|---|---|---|
| Admin | `admin` | `password` |

> Les comptes réels ont été retirés du jeu de données publié. **Changez ce mot de passe** hors d'un environnement de démonstration.

## Limites connues

- Identifiants BD encore codés en dur dans `fetch-courses.php` et `schedule-management.php` : à passer par `config/config.php`.
- Deux fichiers de configuration (`config/` et `src/`) coexistent : à unifier.

## Auteurs

**Charles Traoré** & **Kabran Uriel**.
