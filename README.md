# Laravel Inertia React Datatable

A Laravel package for creating datatables with Inertia.js and React.

## Installation

You can install the package via composer:

```bash
composer require arkhas/inertia-datatable
```

## Configuration

### Publishing Configuration Files

To publish the configuration files, run:

```bash
php artisan vendor:publish --tag=config
```

This will publish two configuration files:
- `config/inertia-datatable.php`: Default configuration for the datatable package

### Accessing Configuration Values

You can access the configuration values in your code using the `config()` helper:

```php
// Access default configuration
$defaultPageSize = config('inertia-datatable.pagination.default_page_size');
```

## Usage

### Backend (PHP)

```php
use Arkhas\InertiaDatatable\InertiaDatatable;
use Arkhas\InertiaDatatable\Table;
use App\Models\User;

// Create a new datatable
$datatable = new InertiaDatatable();

// Set up the table columns
$table = new Table(User::query());
$table->columns([
    // Define your columns here
]);

// Set up the query

// Render the datatable
return $datatable
    ->table($table)
    ->render('Users/Index');
```

### Frontend (React)

#### Installation

First, publish the assets:

```bash
php artisan vendor:publish --tag=assets
```

Then, install the npm package:

```bash
npm i @arkhas/inertia-datatable
# or
yarn add @arkhas/inertia-datatable
```

#### CSS Configuration

To ensure that the CSS classes used in the datatable package are properly applied, you need to include the package's UI components in your Tailwind CSS configuration.

If you don't have a `tailwind.config.js` file in your project, create one with the following content:

```js
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/js/**/*.{js,ts,jsx,tsx}",
    "./vendor/arkhas/inertia-datatable/resources/js/components/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

If you already have a `tailwind.config.js` file, add the package's UI components to the `content` array:

```js
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // ... your existing content paths
    "./vendor/arkhas/inertia-datatable/resources/js/components/**/*.{js,ts,jsx,tsx}",
  ],
  // ... rest of your configuration
}
```

This will ensure that the CSS classes used in the datatable package's UI components are included in your application's CSS file.

#### Usage

In your React component:

```jsx
import { Datatable } from '@arkhas/inertia-datatable';

export default function Users() {
    return (
        <div>
            <h1>Users</h1>
            <Datatable route="users.index" />
        </div>
    );
}
```

### Internationalization (i18n)

Translations are defined in PHP files in the package's `/lang` directory. These can be published to your Laravel application's `/lang/vendor/inertia-datatable` directory for customization.

To publish the translation files, run:

```bash
php artisan vendor:publish --tag=translations
```

This will publish the translation files to:

- `/lang/vendor/inertia-datatable/en/messages.php`: English translations
- `/lang/vendor/inertia-datatable/fr/messages.php`: French translations

Example of a translation file (`messages.php`):

```php
<?php

return [
    'search_placeholder' => 'Search...',
    'reset' => 'Reset',
    'actions' => 'Actions',
    'edit' => 'Edit',
    'view_details' => 'View Details',
    'delete' => 'Delete',
    'rows_selected' => ':count of :total row(s) selected',
    'rows_per_page' => 'Rows per page',
    'page_info' => 'Page :current of :total',
    'go_to_first_page' => 'Go to first page',
    'go_to_previous_page' => 'Go to previous page',
    'go_to_next_page' => 'Go to next page',
    'go_to_last_page' => 'Go to last page',
    'columns' => 'Columns',
    'search_columns_placeholder' => 'Search columns...',
    'no_columns_found' => 'No columns found',
    'filter_selected' => ':count selected',
    'no_results_found' => 'No results found',
    'clear_filters' => 'Clear filters',
    'sort_ascending' => 'Sort Ascending',
    'sort_descending' => 'Sort Descending',
    'hide' => 'Hide'
];
```

The package will automatically load these translations and make them available to the React components. Note that Laravel's placeholders (`:count`, `:total`, etc.) are automatically converted to the format expected by i18next (`{{count}}`, `{{total}}`, etc.) when passed to the frontend.

## License

The MIT License (MIT).
