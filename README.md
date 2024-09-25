# simple-php-mysql
Simple Mysql Class in PHP

**Simple PHP MySQL** is a lightweight PHP class designed to simplify interactions with MySQL databases using MySQLi. It provides methods for connecting, querying, inserting, updating, deleting, and counting records with ease and security.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [License](#license)
- [Author](#author)
- [Acknowledgments](#acknowledgments)

## Features

- **Easy Database Connection**: Simplifies establishing a connection to your MySQL database.
- **CRUD Operations**: Methods to Create, Read, Update, and Delete records effortlessly.
- **Secure Queries**: Utilizes real escaping to protect against SQL injection.
- **Error Handling**: Comprehensive error logging and reporting.
- **Flexible Usage**: Suitable for small to medium-sized projects.

## Installation

You can include the `MySQL.php` class in your project by following these steps:

### Using Composer (Recommended)

1. **Initialize Composer** (if you haven't already):
   ```bash
   composer init

2. **Require the Package:**
   ```bash
   composer require pourfallah/simple-php-mysql

4. **Include Composer’s Autoload in Your PHP Script:**
   ```php
   require 'vendor/autoload.php';

### Manual Installation

1. **Download the MySQL.php File:**
       Clone the repository or download the file directly from [GitHub](https://github.com/pourfallah/simple-php-mysql).

2. **Include the Class in Your PHP Script:**
   ```php
   require_once 'path/to/MySQL.php';

## Usage Example:
Here’s a basic example of how to use the MySQL class in your project:

```php
<?php
// Include the MySQL class (assuming it's saved as MySQL.php)
require_once 'MySQL.php';

// Create a new MySQL instance
$db = new MySQL();

// Connect to the database
if ($db->connect('username', 'password', 'database_name', 'localhost')) {
    // Insert a new record
    $insertId = $db->insert('users', [
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'age' => 30
    ]);

    if ($insertId) {
        echo "New user inserted with ID: " . $insertId;
    }

    // Fetch a single user
    $user = $db->line("SELECT * FROM `users` WHERE `id` = $insertId");
    print_r($user);

    // Update the user's age
    $db->update('users', ['age' => 31], "id = $insertId");

    // Count the number of users
    $userCount = $db->countRecords('users');
    echo "Total users: " . $userCount;

    // Delete the user
    $db->delete('users', "id = $insertId");

    // Close the connection
    $db->close();
} else {
    // Handle connection error
    print_r($db->error);
}
?>
```

## Parameters

- **connect**(user,user,pass, name,name,host, $port): Establishes a connection to the MySQL database.
- **insert**(table,table,args, where,where,options): Inserts a new record into a specified table.
- **line**($Q): Executes a query and returns a single row.
- **table**(Q,Q,id, $unic): Executes a query and returns multiple rows.
- **update**(table,table,data, condition,condition,limit): Updates existing records in a table.
- **delete**(table,table,condition): Deletes records from a table.
- **countRecords**(table,table,condition): Counts the number of records in a table based on a condition.
- **lastId**(): Retrieves the ID of the last inserted record.
- **close**(): Closes the database connection.

## License

This project is licensed under the [MIT License](https://gapgpt.app/LICENSE). You are free to use, modify, and distribute this software as per the terms of the license.

## Author

- Abbas Pourfallah
    **GitHub**: [@pourfallah](https://github.com/pourfallah)
    **GitHub** Repository: [simple-php-mysql](https://github.com/pourfallah/simple-php-mysql)

## Acknowledgments
- Inspired by the need for simple and secure PHP-MySQL interactions.
- Thanks to the open-source community for their invaluable resources and support.
