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
$table = new Table();
$table->columns([
    // Define your columns here
]);

// Set up the query
$query = User::query();

// Render the datatable
return $datatable
    ->table($table)
    ->query($query)
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
npm install @arkhas/inertia-datatable
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

The `Datatable` component will automatically fetch data from the backend using the route specified in the `route` prop. It will also handle pagination, sorting, and filtering.

#### Props

| Prop | Type | Description |
|------|------|-------------|
| `route` | string | The name of the route to fetch data from. |

### Internationalization (i18n)

The package supports internationalization using i18next and react-i18next. Translations are loaded from PHP files in the `/lang` directory.

#### Configuration

Translations are defined in PHP files in the package's `/lang` directory. These can be published to your Laravel application's `/lang/vendor/inertia-datatable` directory for customization.

To publish the translation files, run:

```bash
php artisan vendor:publish --tag=translations
```

This will publish the translation files to:

- `/lang/vendor/inertia-datatable/en/messages.php`: English translations
- `/lang/vendor/inertia-datatable/fr/messages.php`: French translations
- `/lang/vendor/inertia-datatable/es/messages.php`: Spanish translations

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

#### Usage in React

To use the translations in your React application, you need to initialize the i18n system with the translations from the PHP files:

```jsx
import { initializeI18n, useTranslation } from '@arkhas/inertia-datatable';

// Initialize i18n with the translations from PHP files
// This should be done once in your application, typically in a layout component
// The translations are automatically passed to the frontend by the package
initializeI18n(window.config?.inertiaDatatable);

// In your component
function MyComponent() {
  const { t } = useTranslation();

  return (
    <div>
      <h1>{t('some_translation_key')}</h1>
    </div>
  );
}
```

The language is automatically detected from the `lang` attribute of the HTML document. You can also change the language programmatically:

```jsx
import { i18n } from '@arkhas/inertia-datatable';

// Change the language
i18n.changeLanguage('fr');
```

Note: Make sure your Laravel application has the appropriate PHP translation files in the `/lang/vendor/inertia-datatable/{locale}/messages.php` directory for each language you want to support. You can publish the default translation files using the `php artisan vendor:publish --tag=translations` command.

#### Usage in PHP

The package also supports translations in PHP classes. When defining columns and filters, you can use translation keys for labels:

```php
use Arkhas\InertiaDatatable\Columns\Column;
use Arkhas\InertiaDatatable\Filters\Filter;
use Arkhas\InertiaDatatable\Filters\FilterOption;

// Define a column with a translatable label
$column = Column::make('name')
    ->label('column.name'); // Will be translated using Laravel's __() function

// Define a filter with a translatable label
$filter = Filter::make('status')
    ->label('filter.status'); // Will be translated using Laravel's __() function

// Define filter options with translatable labels
$filter->options([
    FilterOption::make('active')
        ->label('filter.status.active'), // Will be translated
    FilterOption::make('inactive')
        ->label('filter.status.inactive'), // Will be translated
]);
```

Make sure to define these translation keys in your Laravel language files.

## License

The MIT License (MIT).
