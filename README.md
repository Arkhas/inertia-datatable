# Laravel Inertia React Datatable

The DataTable library provides a powerful and flexible way to create interactive data tables in your Laravel application. It integrates with Inertia.js to provide a seamless experience between your Laravel backend and React frontend.


## Key Features
• Easy to use: Simple API for creating and configuring DataTables

• Flexible: Customizable columns, filters, and actions

• Interactive: Built-in support for sorting, filtering, and pagination

• Exportable: Built-in support for exporting data with customizable formatting

• Responsive: Works well on all screen sizes

• Integrated: Seamlessly integrates with Laravel and React



## Installation

```bash
composer require arkhas/inertia-datatable
```

And install the frontend package using npm:

```bash
npm install @arkhas/inertia-datatable
```

For the classes to work properly, you need to ensure to add this to your app.css file:

```css
@source '../../vendor/arkhas/inertia-datatable/resources/**/*.tsx';
```

## Basic Usage

### Creating a DataTable Class

To create a new DataTable, you need to create a class that extends `InertiaDatatable`. Here's a basic example:

```php
<?php

namespace App\DataTables;

use App\Models\User;
use Arkhas\InertiaDatatable\EloquentTable;
use Arkhas\InertiaDatatable\InertiaDatatable;
use Arkhas\InertiaDatatable\Columns\Column;
use Illuminate\Http\Request;

class UserDataTable extends InertiaDatatable
{
    protected array $availablePageSizes = [5, 10, 25, 100];

    protected function setup(): void
    {
        $table = new EloquentTable(User::query());
        $table->columns([
            Column::make('id')->label('ID'),
            Column::make('name')->label('Name'),
            Column::make('created_at')->label('Created At'),
        ]);

        $this->table($table);
    }
}
```

## License

The MIT License (MIT).
