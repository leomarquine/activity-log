# Activity Log
Register all activities in your application's models.

## Installation
Install through Composer
```
composer require marquine/activity-log
```

## Setup
### Step 1: Register the Service Provider
Add the ActivityLogServiceProvider to the providers array in the `config/app.php` file;
```
Marquine\ActivityLog\ActivityLogServiceProvider::class,
```

### Step 2: Publish the package resources and run the migration.
Publish the files:
```
php artisan vendor:publish --tag activity-log
```
The migration is not automatically loaded, so you can make changes like add constraints, change the table name, change id columns types, etc. Customize the migration if needed then migrate the table:
```
php artisan migrate
```
### Step 3: Create an Activity Model
Create a model (if you choose a different name, make sure to change the `model` option in the `config/activity.php` file):
```
php artisan make:model Activity
```
Use the `ActivityLog` trait in the created model:
```php
<?php

namespace App;

use Marquine\ActivityLog\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use ActivityLog;
}
```

### Step 4: Choose the loggable models
Use the `Loggable` trait in any model that you want to log its activities:
```php
<?php

namespace App;

use Marquine\ActivityLog\Loggable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Loggable;
}
```

## License
Activity Log is licensed under the [MIT license](http://opensource.org/licenses/MIT).
