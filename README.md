# Gondwana Rate Assignment — Justina Haiduwa

This repository contains a PHP REST API and a simple frontend to interact with it. The API accepts the assignment payload, transforms it to the remote API format and relays the remote API response back to the UI.

## How to run locally (quick)
1. Copy `.env.example` to `.env` and adjust if necessary.
2. Start a PHP built-in server from the project root so that `/rates` resolves to `api/index.php`.

   Option A (serve project root, requires PHP >= 8):

   ```bash
   php -S 0.0.0.0:8080 -t .
   ```

   Option B (serve api folder):

   ```bash
   php -S 0.0.0.0:8080 -t api api/index.php
   ```

3. Open `frontend/index.html` in your browser (or browse `http://localhost:8080/frontend/index.html` if using root server).

## Run in GitHub Codespaces
1. Push repository to GitHub.
2. Open repository in Codespaces.
3. Codespaces will use the provided devcontainer (PHP 8.1). Start the built-in server as above.

## SonarCloud
- A GitHub Action placeholder `sonarcloud-analysis.yml` is included and runs on pull requests. Configure SonarCloud tokens in GitHub secrets as described on SonarCloud docs.

## Notes & Limitations
- The remote API URL is set in `.env`. The assignment endpoint is used as-is.
- This implementation maps ages to `Adult` (>=18) or `Child` (<18). Adjust rules as required.
- The frontend is intentionally simple and focuses on functionality and clarity.

## Author
Justina Haiduwa
