

**Crispy Maps** is a plugin for CrispyCMS that allows easy integration of interactive maps with categorized location markers.

It’s ideal for projects that aim to present location-based content in a visual and structured way – for example, urban exploration platforms, thematic maps, or alternative travel guides.
A prominent real-world example using Crispy Maps is [Lostplace-Karte.de](https://lostplace-karte.de).

---

## ✨ Features

* Interactive map with categorized markers (using FontAwesome icons)
* Location data management including coordinates, descriptions, categories, and tags (e.g. *inaccessible*, *fee required*)
* Support for contextual **location tags** per entry
* Built-in admin interface for managing locations and static pages (e.g. About, Imprint)

> **Note:** Multiple map layers are **not supported** at this time.

---

## 🌍 Language Support

The current version of Crispy Maps is **hardcoded in German**.
Once Crispy CMS adds official support for plugin translations, we plan to offer full **multilingual support**.

---

## 🚧 Development Status

* Many parts of the codebase still use naming conventions related to the original "Lostplaces" project.
* The location tagging system is currently hardcoded but will be moved to a fully database-driven model in future versions.

---

## 🚀 Installation

To get started quickly, use the included `docker-compose.yaml`:

```bash
git clone https://github.com/BL-Netzwerktechnik/crispy-maps.git
cd crispy-maps
cp .env.example .env
docker-compose up --build
```

The app will be available at `http://localhost:80`.

---

## 🤝 Contributing & Issues

This repository is a **mirror** of our internal Git server. As a result, changes, pull requests, or issue responses may not appear immediately.

We use **Jira** internally for issue tracking. If your GitHub issue is linked to a Jira ticket, don’t be surprised if public activity appears low — discussions are likely happening internally.

We still encourage contributions, bug reports, and feature suggestions — feel free to open a [GitHub Issue](https://github.com/BL-Netzwerktechnik/crispy-maps/issues)!