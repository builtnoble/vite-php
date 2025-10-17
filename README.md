<div style="text-align: center">
    <h1>Vite Manifest Reader for PHP</h1>
    <a href="https://github.styleci.io/repos/1078430247?branch=main"><img src="https://github.styleci.io/repos/1078430247/shield?branch=main" alt="StyleCI"></a>
</div>

---

Laravel provides an excellent built-in Vite integration through its Vite class but, for developers working outside of Laravel’s ecosystem, there’s a gap.

This package aims to fill that gap, making it easy to integrate Vite into standalone PHP applications, micro-frameworks, or custom setups while keeping a clean and familiar workflow.

It reads Vite’s `manifest.json` file, resolves asset URLs, and outputs the correct `<script>` and `<link>` tags for both development and production environments.

While it can be used on its own, it’s primarily intended as a core utility for framework-agnostic Vite integrations — for example, as part of a [Plates](https://platesphp.com/) or [Twig](https://twig.symfony.com/) extension.
