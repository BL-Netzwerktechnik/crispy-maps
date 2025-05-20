# Crispy Maps

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

To get started quickly, use the included `docker-compose.yml`:

```bash
git clone https://github.com/BL-Netzwerktechnik/crispy-maps.git
cd crispy-maps
cp .env.example .env
docker-compose up --build
```

The app will be available at `http://localhost:80`.


## 🤝 Contributing & Issues

This repository is a **mirror** of our internal GitLab instance. As such, **GitHub pull requests cannot be merged directly** into the main codebase.

If you would like to contribute, feel free to open a pull request or an issue here on GitHub. We **manually review and import** accepted changes into our internal GitLab repository, and they are later mirrored back to GitHub.

> ✉️ **Important:** If your pull request is merged internally, we will ensure that your original **commit attribution is preserved**, so your contributions remain visible and credited in the commit history.

We use **Jira** for internal issue tracking. If your GitHub issue is linked to a Jira ticket, don’t be surprised if there’s limited visible activity here — discussions may be ongoing internally.

We still encourage contributions, bug reports, and feature suggestions — feel free to open a [GitHub Issue](https://github.com/BL-Netzwerktechnik/crispy-maps/issues)!