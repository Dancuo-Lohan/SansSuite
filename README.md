# Sans Suite

Sans Suite is a small, local-first job application tracker built with the [CorianderPHP framework](https://github.com/CorianderPHP/CorianderPHP), PHP 8.2, SQLite, TypeScript, and Tailwind CSS.

It replaces a growing spreadsheet with a clearer place to record applications, update their status, keep notes and attachments, add contacts or follow-ups, and review upcoming events in a calendar. Applications can also be searched, filtered, archived, and exported to CSV.

All personal data stays on the local computer. The SQLite database, attachments, and `.env` file are excluded from Git.

## Windows installation with WampServer

Requirements: WampServer with PHP 8.2 or newer, Composer, and Node.js. Enable the Apache rewrite module and the PHP `pdo_sqlite` and `sqlite3` extensions.

1. Place the project in a WampServer directory, for example:

   ```text
   C:\wamp64\www\websites\SansSuite
   ```

2. Use WampServer's **Add a Virtual Host** page with `sanssuite` as the host name and the project root as its path, then restart all WampServer services.

3. Run these commands from the project root:

   ```powershell
   composer install
   Copy-Item .env-example .env
   php coriander migrate
   Set-Location nodejs
   npm install
   npm run build-prod
   ```

4. Open [http://sanssuite/](http://sanssuite/) in a browser.

Run the project tests from the root directory with:

```powershell
composer test
```

`CorianderCore/` contains the framework and should not be modified from this project.
