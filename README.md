# Shared Project Timesheets Bundle - Kimai 2 Plugin

A Kimai 2 plugin that allows you to share your project timesheets with anyone you want to grant access to.

## Features

- Create publicly accessible urls for the project timesheets you want to share
- Access control feature
  - protect the shared project timesheets with a password
- View control feature
  - show or hide user of records (name of user)
  - show or hide rates of records (hour rate, total rate)
  - show or hide chart with day comparison by selected month
  - show or hide chart with month comparison by selected year
- View customizations
  - define whether and how to merge records of a day (e.g. merge records of one day, use description of last record)

## Installation

First clone this plugin to your Kimai installation `plugins` directory:
```
cd /kimai/var/plugins/
git clone https://github.com/dexterity42/SharedProjectTimesheetsBundle.git
```

Go back to the root of your Kimai installation and clear the cache:
```
cd /kimai/
bin/console cache:clear
bin/console cache:warmup
```

Execute database migrations:
```
bin/console kimai:bundle:shared-project-timesheets:install
```

You're done. Open up your browser and navigate to "Shared project timesheets".

## Permissions

Currently, there are no specific plugin permissions. The role `ROLE_SUPER_ADMIN` is required to manage the shared project timesheets.
