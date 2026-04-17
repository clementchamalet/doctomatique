# Doctomatique

Application web PHP/MySQL inspiree de Doctolib, realisee dans un cadre pedagogique. Le projet permet de rechercher des praticiens, consulter des disponibilites et gerer des rendez-vous cote patient, praticien, secretaire et administrateur.

## Apercu

- `index.php` : page d'accueil
- `pages/` : pages PHP de l'application
- `api/` : endpoints AJAX pour la recherche, la prise et l'annulation de rendez-vous
- `assets/css/` : feuilles de style
- `assets/js/` : scripts front-end
- `assets/images/` : images et icones

## Fonctionnalites principales

- recherche de praticiens par specialite et par ville
- consultation d'une fiche praticien et de ses creneaux
- prise et annulation de rendez-vous
- espace patient
- espace professionnel
- espace secretaire
- espace administrateur

## Prerequis

- PHP 8.x avec `mysqli` et `pdo_mysql`
- MySQL ou MariaDB
- un serveur web local ou le serveur integre PHP

## Installation locale

1. Cloner ou copier le projet sur votre machine.
2. Copier `config.local.example.php` vers `config.local.php`.
3. Renseigner vos identifiants de base de donnees dans `config.local.php`.
4. Importer votre base de donnees MySQL.
5. Lancer le projet avec votre environnement habituel.

Exemple avec le serveur integre PHP :

```bash
php -S localhost:8000
```

Puis ouvrir `http://localhost:8000`.

## Configuration

Le projet charge d'abord les valeurs par defaut definies dans `pages/config.php`, puis applique si besoin :

- les variables d'environnement `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `APP_TIMEZONE`
- le fichier local non versionne `config.local.php`

Cela permet de garder les secrets hors du depot public.

## Publication sur GitHub

Avant de publier le projet :

- verifier que `config.local.php` n'est pas versionne
- verifier qu'aucune donnee reelle n'est presente dans `assets/messages/`
- relire les textes legaux si le projet est diffuse publiquement
- preciser dans la description GitHub qu'il s'agit d'un projet pedagogique inspire d'une plateforme existante

## Remarques

- Le depot est prepare pour etre public sans exposer les identifiants de base de donnees.
- Le code a ete garde volontairement proche de sa structure initiale.
