# Livror

![version](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/darkirbyy/07bb4b086f8e7dea73754e73bc5c1bb2/raw/livror-version.json)
![coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/darkirbyy/07bb4b086f8e7dea73754e73bc5c1bb2/raw/livror-coverage.json)

Small webapp to share reviews of games with my friends, developed while learning Symfony.

## Prerequisite

- Back-end:
  - **Symfony**: 7.4 framework
  - **PHP**: 8.4 (compatible with Symfony 7.4)
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

## User provider

In production, this app is designed to rely on the [Hub app](https://github.com/darkirbyy/hub) to provide and authentify users, thanks to a shared session.
In dev, it's possible to emulate this behavior or to mock the hub by creating dummy users.

### Emulate the prod behavior

- clone the Hub project, install it, and configure it as explained.
- start the hub server first, so the port will probably be `8000` for the http and `3306` for the database
- create an application with these parameters :
  - **status** : `user only`
  - **name** : `livror`
  - **path** : `https://127.0.0.1:8001/`
  - **right** : add one with **role** = `user`.
  - the rest can be random
- create one or more users, add add the right `livror - user` to them.
- in this project, in the `.env.local` file, set :
  - `HUB_DATABASE_URL="mysql://root:hub_password@127.0.0.1:3306/hub_db"`
  - `HUB_BASE_URL="https://127.0.0.1:8000"`
  - `HUB_ACCOUNT_ROUTE=/account`
- start this project, so the port will probably be `8001` for the http and `3307` for the database
- that's it ! when visiting a page, you'll be automatically redirected to the hub to log in.

### Mock the hub and use dummy users

- in the `.env.local` file, set :
  - `APP_MOCK_HUB=true`
  - `HUB_DATABASE_URL="mysql://root:livror_password@127.0.0.1:3306/user_db"`
  - `HUB_BASE_URL=""`
  - `HUB_ACCOUNT_ROUTE=""`
- execute theses commands to create and prepare the user database along with the app database :
  - `symfony console doctrine:database:create --connection=account`
  - `symfony console doctrine:schema:update --em=account --force`.
- that's it ! when loading fixtures (see [Dev](#dev)), four dummy users will be created and you'll be connected as the first one.

## Dev

To generate fake random data, use the Foundry `DevStory` with `symfony console doctrine:fixtures:load`.  

To mock the external API calls, set the `APP_MOCK_HUB=true` in the `.env.local` file:
  
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
