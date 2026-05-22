# Livror

![version](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/darkirbyy/07bb4b086f8e7dea73754e73bc5c1bb2/raw/livror-version.json)
![coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/darkirbyy/07bb4b086f8e7dea73754e73bc5c1bb2/raw/livror-coverage.json)

Small webapp to share reviews of games with my friends, developed while learning Symfony.

## Prerequisite

- Back-end:
  - **Symfony**: 7.4 framework
  - **PHP**: 8.4 (compatible with Symfony 7.4) with APCU extension for caching
  - **Composer**: >= 2.8 for dependency management
  - **MariaDB**: 11.8 through **docker** for the database
- Front-end:
  - **Node.js**: 22.x
  - **npm**: >= 10.x for dependency management
  - **Sass**: >= 1.82
  - **Webpack Encore**: 5.x
- **git** and **git-flow** for source and version control
- **symfony CLI** for main commands
- **GitHub** to share and deploy

## Code quality

**Prettier** with custom modules from `@zackad/prettier-plugin-twig` and `@prettier/plugin-php` for twig and PHP files.  
To prettify one file:

- in the console, execute `npm run pretty-file <file>`.
- if using VSCode, install the *Prettier* extension and set the config file path to `linter/.prettierrc.json`, then use *Format Document*.

To prettify all files, run `npm run pretty-all`.

**Linter**:

- **php-cs-fixer**: for PHP files in `src` and `tests` directories
- **twig-cs-fixer**: for twig files in `templates` directory
- **stylelint**: for CSS/SCSS files in `assets/styles` directory
- **eslint**: for JS files in `assets/controllers` directory

To lint all files from one type, run `composer lint-[php|twig|scss|js]`.  
To lint all files, run `composer lint-all`.

## Install

After cloning the project:

- install the dependencies with `composer install` and `npm install`.
- copy the `.env` file into a `.env.local` file and customize the values.  
:information_source: `DATABASE_URL` is not mandatory for dev environment as Symfony will get its value from docker.  
- start the php/web server along with docker and npm server with `symfony server:start -d`.  
- execute `symfony console doctrine:migrations:migrate`.

To use default git hooks, run `git config core.hooksPath ./githooks`. Current hooks are

- prettify and linting all staged files before commit
- running tests before push : all tests for `main` branch, unit tests otherwise

## User provider and authentication

In production, this app is designed to rely on a Keycloak instance to provide and authentify users.
In dev, it's possible to use a real connection to a Keycloak instance configured with a dedicated dev client + admin client or to mock this connection with fake users.

### Real connection

- create a dev client `livror-dev` with:
  - Access settings: root URL = `https://localhost:8000` and Valid redirects URIs = `/*`
  - Roles : USER without the ROLE_ prefix
  - Client authentication: ON with `Standard flow`
  - Client scopes: select the dedicated one, then `Add mapper` -> `By configuration`:
    - Client ID: `livror-dev`
    - Client Role Prefix: `ROLE_`
    - Token Claim Name: `resource_access.${client_id}.roles`
    - Add to userinfo: ON

- create the admin client (see [Deploy](#deploy)).

- in the `.env.local` file, adapt all variables inside the `###> mainick/keycloak-client-bundle ###` block.

### Mock connection

- in the `.env.local` file, set `APP_MOCK_KEYCLOAK=true`.
- that's it ! when loading fixtures (see [Dev](#dev)), four dummy users will be created and you'll be connected as the first one.

## Dev

To generate fake random data, use the Foundry `DevStory` with `symfony console doctrine:fixtures:load`.  

To mock the external API calls, set the `APP_MOCK_API=true` in the `.env.local` file:
  
- Steam API GET responses will be replaced with dummy data from `test/Mock/ApiMockData.php` file
- Discord API POST will be written into `var/discord/` directory

To increment the version, use `symfony console bizkit:versioning:increment`.  

## Test

To start a specific test suite, run `composer tests-[unit|inte|func]`.  
To start all tests, run `composer tests-all`.

:warning: Tests that require a database connection use a specific database suffixed with `_test`, automatically created when needed. For Symfony to get the `DATABASE_URL` value from docker in test environnement, it's mandatory to run PHPUnit through symfony with `symfony php bin/phpunit`.

:information_source: Tests are indenpendant of the chosen user provider, as it will always create temporary users in the test database.

## Deploy

A workflow to test, build and deploy the application is preconfigured.  
The workflow can be triggered manually in GitHub Actions or automatically when pushing to main (for prod) or to develop (for stag).  

Along with the authentication client, this application requires a connection to Keycloak REST API, to retrieve all authorized users. For that, create an admin client `livror-admin`:

- Client authentication: ON with `Service account roles`
- Service account roles: add `view-users`, `view-realm` and `view-clients` from `realm-managment`
