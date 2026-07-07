# AuraPHP Project

A lightweight, high-performance PHP web application utilizing AuraPHP components, optimized for serverless execution and seamless deployment on **Vercel**. 

![Deployment Status](https://img.shields.io/badge/Deployment-Vercel%20Serverless-black?style=for-the-badge&logo=vercel)
![Language](https://img.shields.io/badge/Language-PHP%208.x-777bb4?style=for-the-badge&logo=php)
![Framework Component](https://img.shields.io/badge/Framework-AuraPHP-purple?style=for-the-badge)

---

## 🚀 Architectural Highlights

AuraPHP provides independent, decoupled packages that are perfect for serverless environments due to their low overhead and high execution speed.
* **Serverless Execution:** Fully optimized to run inside stateless Vercel Serverless Functions, keeping cold start times to an absolute minimum.
* **Decoupled Architecture:** Utilizes clean Aura component practices to keep the codebase modular, highly testable, and lightning-fast.
* **Modern Routing & Dependency Injection:** Implements robust request routing and clean decoupling of system dependencies.

---

## 🛠️ Tech Stack & Infrastructure

* **Hosting Platform:** Vercel (Stateless Serverless Functions via the Vercel PHP Runtime).
* **Core Engine:** PHP 8.x.
* **Framework Layer:** AuraPHP decoupled components.
* **Package Management:** Composer.

---

## 📦 Vercel Serverless Configuration

To host this AuraPHP architecture on Vercel, the project uses a custom `vercel.json` runtime adapter layer to correctly route incoming HTTP requests to your PHP entry points.

```json
{
  "functions": {
    "api/**/*.php": {
      "runtime": "vercel-php@0.7.3"
    }
  },
  "routes": [
    { "src": "/(.*)", "dest": "/api/index.php" }
  ]
]

💻 Local Workspace Development Setup
To clone this repository and run the development environment locally:

1. Install Dependencies
Ensure you have PHP 8.x and Composer installed on your system.
# Clone the repository and install packages
composer install

2. Boot the Local Server
Run the built-in PHP development engine:
php -S localhost:8000 -t public/
