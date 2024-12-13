# Symfony Template

Template to quick start any Symfony project.

## Prerequisite

- Back-end:
  - **Symfony**: 7.1 framework
  - **PHP**: 8.2 (compatible with Symfony 7.1)
  - **Composer**: >= 2.8 for dependency management
  - **MariaDB**: 11.5 through docker for the database
- Front-end:
  - **Node.js**: 18.x
  - **npm**: >= 9.2 for dependency management
  - **Sass**: >= 1.82
  - **Webpack Encore**: 5.0
- **git** and **git-flow** for source and version control
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

## Install and dev

After cloning the project:

- Install the dependencies with `composer install` and `npm install`.
- Copy the `.env.dev` file into a `.env.dev.local` file and customize the values.  
- Copy the `.env` file into a `.env.local` file and customize `APP_NAME`.  
:information_source: `DATABASE_URL` is not mandatory for dev environment as Symfony will get the correct values from docker.

Start the php/web server along with docker and npm server with `symfony server:start -d`.  
Check the logs with `symfony server:logs`.  
Stop all the services with `symfony server:stop`.

To use default git hooks, run `git config core.hooksPath ./githooks`. Current hooks are

- prettify and linting all staged files before commit
- running all unit tests before push

To increment the version, use `symfony console bizkit:versioning:increment`.

## Deploy

A workflow to build and deploy the application is preconfigured. Some variables and secrets have to been set up on GitHub:

- Global parameters:
  - variables: server **ADDR** (domain name)
  - secrets: server **PORT** for SSH connection
- Environment parameters (for prod and test):
  - variables: **PATH** where to copy the application on the server
  - secrets: server **USER** and private **KEYS** for SSH connection

The workflow can be triggered manually in GitHub Actions or automatically when pushing to main (for prod) or to develop (for test).  
:warning: Automatic triggers are disabled by default, uncomment the corresponding lines in `.github/workflows/main.yml`.

On the server, to correctly route the request as the app lives in a subdirectory, use this nginx location block (replacing *symfo-base* with the chosen `APP_NAME`):

```ini
location @symfo-base {
  rewrite ^/symfo-base/(.*)$ /symfo-base/index.php/$1 last;
}

location /symfo-base/ {
  alias /usr/share/nginx/www/symfo-base/public/;
  try_files $uri @symfo-base;

  location ~ ^/symfo-base/index\.php(/|$) {
    fastcgi_split_path_info ^(/symfo-base/index\.php)(/.*)$;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    fastcgi_pass php-fpm:9000;
    internal;
  }
}
```

:warning: On the server, don't forget to create the user and database in the RDBMS, and to create a `.env.local` file with prod/test env and corresponding database credentials.
