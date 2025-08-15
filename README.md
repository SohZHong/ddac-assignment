# DDAC Assignment

## Running the Application

### Installing Dependencies

Ensure you have composer and laravel cli installed:

```sh
composer --v
laravel --v
```

If not, install them through:

Composer:

```sh
brew install composer
```

Laravel:

```sh
composer global require laravel/installer
```

After installing laravel cli, add the following into your `~/.zshrc` file.

```sh
# composer
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

Reload the `~/.zshrc` file:

```sh
source ~/.zshrc
```

Now try:

```sh
composer --version
laravel --version
```

Once both clis are installed, modify the `.env` file for postgres connection:

```.env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1       # Modify accordingly for remote
DB_PORT=5432            # Modify accordingly for remote
DB_DATABASE=laravel     # Modify accordingly for remote
DB_USERNAME=postgres    # Modify accordingly for remote
DB_PASSWORD=            # Modify accordingly for remote
```

Migrate the database files:

```sh
php artisan migrate
```

Run the seeder:

```sh
php artisan db:seed
```

> ![IMPORTANT]
> For a complete database rebuild, run `php artisan migrate:fresh --seed` instead

Now run:

```sh
npm install && npm run build
composer run dev
```

Your application will be accessible at http://localhost:8000

### Contributing to the codebase

Here are common Artisan commands for adding new functionality.

#### 1. Creating a Controller

```sh
php artisan make:controller ExampleController
```

Creates a basic controller in `app/Http/Controllers/`.

Options:

- Resource controller (with index, create, store, show, edit, update, destroy):

```sh
php artisan make:controller ExampleController --resource
```

- Controller inside a folder:

```sh
php artisan make:controller Admin/ExampleController
```

#### 2. Creating a Model

```sh
php artisan make:model Example
```

This creates `app/Models/Example.php`.

With migration and factory in one go:

```sh
php artisan make:model Example -m -f
```

#### 3. Creating a Migration

```sh
php artisan make:migration create_examples_table
```

This creates a migration file in `database/migrations/`.

Run migrations:

```sh
php artisan migrate
```

#### 4. Adding Routes

Open `routes/web.php` (for web routes) and register your controller method:

```php
use App\Http\Controllers\ExampleController;

Route::get('/examples', [ExampleController::class, 'index']);
```

#### 5. Creating a Seeder

```sh
php artisan make:seeder ExampleSeeder
```

This creates a seed file in `database/seeders/`.

Run seeder individually:

```sh
php artisan db:seed --class=ExampleSeeder
```

> [!NOTE]
> Ideally you want to create a corresponding `Factory` class inside `database/factories/` and add them into `DatabaseSeeder.php`
