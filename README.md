# Shared Project Timesheets Bundle - Kimai 2 Plugin

A Kimai 2 plugin that allows you to share your project timesheets with anyone you want to grant access to.

## Features

- Create publicly accessible urls for the project timesheets you want to share
- Manage the view and decide what will be exposed
    - Password protection
    - Show/hide user of records (name of user)
    - Show/hide rates of records (hour rate, total rate)

## Installation

First clone this plugin to your Kimai installation `plugins` directory:
```
cd /kimai/var/plugins/
git clone --depth 1 --branch master https://github.com/dexterity42/SharedProjectTimesheetsBundle.git
```

Go back to the root of your Kimai installation and execute database migrations:
```
cd /kimai/
bin/console kimai:bundle:shared-project-timesheets:install
```

Then rebuild the cache: 
```
bin/console cache:clear
bin/console cache:warmup
```

You're done. Open up your browser and navigate to "Shared project timesheets".

## Permissions

Currently, there are no specific plugin permissions. The role `ROLE_SUPER_ADMIN` is required to manage the shared project timesheets.
