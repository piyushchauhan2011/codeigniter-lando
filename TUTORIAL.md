# CodeIgniter Learning Notes

This project now includes a mini tutorial module:

- `GET /hello` route/controller/view demo
- Shared layout + CSS + JavaScript assets
- `posts` table migration + `PostModel`
- Basic create/list flow with validation

## 1) Configure database in `.env`

Copy `env` to `.env` if needed, then set:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'https://my-first-lamp-app.lndo.site/'
app.forceGlobalSecureRequests = true
app.indexPage = ''
database.default.hostname = database
database.default.database = lamp
database.default.username = lamp
database.default.password = lamp
database.default.DBDriver = MySQLi
database.default.port = 3306
```

## 2) Run migration

```bash
lando php spark migrate --all
```

Use `--all` because the job portal authentication tables come from CodeIgniter Shield. To load demo portal users, roles, jobs, and applications data, run:

```bash
lando php spark db:seed JobPortalDemoSeeder
```

## 3) Open pages

- `/hello`
- `/posts`
- `/posts/new`
