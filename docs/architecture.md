---
icon: lucide/layout-grid
---

# Architecture du projet

## Vue d’ensemble

Le projet **SafePay** repose sur une architecture web classique basée sur le principe de séparation des responsabilités.  
Chaque partie de l’application a un rôle bien défini :

- l’affichage (HTML / CSS)
- la logique métier (PHP)
- la base de données (MySQL)
- l’interactivité (JavaScript)

Cette organisation permet :

- une meilleure lisibilité du code
- une maintenance facilitée
- un travail collaboratif plus efficace

---

## Structure des répertoires

L’arborescence principale du projet est organisée comme suit :

```text
SafePay/
├── .github
├── .gitignore
├── config
├── docs
├── logique
├── public
├── templates
└── zensical.toml
```


---

## Description des dossiers principaux

### 📁 `logique/` — Couche métier

Ce dossier contient tous les traitements PHP :

- requêtes SQL
- calculs métiers
- contrôles d'accès
- génération de rapports (PDF / CSV / XLS)
- préparation des données pour l'affichage

Chaque fichier représente une **fonctionnalité métier**
Ce dossier constitue le **cœur fonctionnel de l'application

---

### 📁 `templates/` — Vues (affichage)

Ce dossier contient les pages affichées à l’écran :

- tableaux de données
- graphiques
- formulaires
- interfaces utilisateur

Les fichiers de ce dossier sont responsables uniquement de l'affichage.
Les données sont toujours injectées depuis les scripts PHP de la couche métier

---

### 📁 `public/` — Point d’entrée Web

Ce dossier contient les fichiers accessibles publiquement :

- pages PHP appelées par le navigateur
- feuilles de styes CSS
- scripts JavaScript
- ressources graphiques (images)

Chaque fichier :

1. appelle la logique dans `logique/`
2. puis inclut le template correspondant dans `templates/`

---

### 📁 `docs/` — Documentation technique

Contient :
- les fichiers Markdown (`.md`)
- la structure du site de documentation

La documentation est **automatiquement déployée** via GitHub Pages.

---

### 📁 `.github/` — Déploiement automatique

Contient les workflows GitHub Actions :

- génération automatique du site
- déploiement sur la documentation
- intégration continue

---

##  Modèle architectural (MVC simplifié)

Le projet s'inspire du modèle **MVC** (Model / Vue / Controller):

| Composant | Rôle |
|----------|------|
| Modèle   | Base de données MySQL |
| Vue      | Fichiers HTML (`templates/`) |
| Contrôleur | Fichiers PHP (`logique/`) |

Il s'agit d'un **MVC simplifié** adapté à la structure PHP.


## Génération de documents


Exports disponibles :

L'application permet d'exporter les données sous différents formats :

- PDF 
- CSV
- XLS

Ces exports sont gérés dans la couche métier (`logique/`).

---

## Architecture de la documentation

La documentation repose sur **Zensical** :

- rédaction en Markdown (`.md`)
- génération statique du site
- déploiement sur GitHub Pages via GitHub Actions.

---
