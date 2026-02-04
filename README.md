# Project Monolith: Unified Framework Integration Proof of Concept

## Executive Correspondence

**Date:** February 4, 2026  
**Subject:** Formal Documentation of the Unified PHP/Frontend Architectual Proof of Concept  
**To:** Stakeholders and Development Teams

Dear Colleagues,

I am pleased to present this **Proof of Concept (PoC)**, a technical demonstration of a unified monorepo architecture. This project validates the possibility of maintaining a single-domain, multi-framework ecosystem—venerably referred to as a "Mono Website"—without the administrative burden of reverse proxies or complex HTTP request forwarding.

By leveraging **PHP** as the primary entry point and orchestrator, we have established a system where diverse frontend frameworks (specifically React and Vue.js) coexist harmoniously. This approach ensures that all requests are handled by a single server entity, providing a seamless external experience while maintaining internal modularity.

The following documentation outlines the architectural specifications, implementation details, and current technical constraints of this paradigm.

---

## Architectural Overview

The core philosophy of this project is to use PHP not just for backend logic, but as the master router for the entire application.

### The Entry Point

The system uses `index.php` as the central dispatcher. Utilizing a custom routing mechanism, the server identifies the requested URI and serves the corresponding frontend bundle by parsing the Vite manifest.

### Technical Stack

- **Orchestrator:** PHP 8.x with a custom Router.
- **Frontend Frameworks:** React (TypeScript) & Vue.js (JavaScript).
- **Build System:** Vite & Rolldown, utilized for multi-entrypoint compilation.
- **Server:** PHP Built-in Development Server (automated via Vite plugin).

---

## Key Features

1. **True Mono-Repo / Mono-Website:** Operates under a single domain without proxying (e.g., Nginx `proxy_pass`).
2. **Simplified Backend Integration:** Native PHP access for API endpoints (`/api`) within the same environment as the UI.
3. **Decentralized Frontend Development:** Individual teams can work in React or Vue within their designated directories while sharing the same build pipeline and deployment target.
4. **Automated Development Environment:** A custom Vite plugin synchronizes the PHP server lifecycle with the Vite build process.

---

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Node.js & pnpm
- Composer

### Installation

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Install Node dependencies:
   ```bash
   pnpm install
   ```

### Development

To launch the development environment (including the auto-rebuilding frontend and the PHP server):

```bash
pnpm dev
```

### Production Build

To generate the optimized distribution bundles:

```bash
pnpm build
```

---

## Known Limitations and Technical Constraints

While this architecture provides significant simplicity and deployment advantages, certain trade-offs have been accepted for this Proof of Concept:

### 1. Lack of Hot Module Replacement (HMR)

In this configuration, HMR is disabled. The system relies on `vite build --watch`.

- **Consequence:** Developers must manually refresh the browser after the build completes to see changes.
- **Rationale:** Implementing HMR would necessitate a multi-port setup (one for PHP, one for the Vite dev server), increasing maintenance complexity and deviating from the "Mono Website" principle where PHP is the sole provider of the frontend.

### 2. Framework Navigation (Hard Reloads)

When transitioning between different framework entry points (e.g., moving from a React-driven page to a Vue-driven page), the application requires a full page reload.

- **Consequence:** Standard Single Page Application (SPA) routing cannot be used across frameworks. External links or `window.location.href` must be utilized.
- **Rationale:** Each framework resides in its own entry point defined in the Vite manifest. A clean state is required when switching context between the React and Vue runtimes.

---

## Conclusion

This project serves as a robust foundation for building complex, multi-framework applications where administrative simplicity and backend integration are paramount. We believe the benefits of a single-domain entry point outweigh the current development-time constraints.

Thank you for your attention to this architectural advancement.

**Sincerely,**

_[Jericho Aquino](https://github.com/eru123)_
