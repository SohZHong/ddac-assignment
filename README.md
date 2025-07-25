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

Once both clis are installed, cd into the project and run:

```sh
npm install && npm run build
composer run dev
```

Your application will be accessible at http://localhost:8000
