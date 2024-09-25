<?php
/**
 * MySQL Database Interaction Class
 *
 * This class provides methods to interact with a MySQL database using MySQLi.
 * It includes functionality to connect, query, insert, update, delete, and count records.
 *
 * @author 
 *   Abbas Pourfallah
 * @github 
 *   https://github.com/pourfallah/simple-php-mysql
 */

class MySQL
{
    /**
     * @var mysqli|false Database connection instance
     */
    private $connection = false;

    /**
     * @var array Stores the status of various database operations
     */
    public $work = [];

    /**
     * @var array Stores error messages
     */
    public $error = [];

    /**
     * Connect to the MySQL database
     *
     * @param string|false $user     Database username
     * @param string|false $pass     Database password
     * @param string|false $name     Database name
     * @param string|false $host     Database host
     * @param int          $port     Database port (default: 3306)
     *
     * @return bool True on success, false on failure
     */
    public function connect($user = false, $pass = false, $name = false, $host = false, $port = 3306)
    {
        if ($this->connection) {
            return true;
        }

        // Attempt to establish a new MySQLi connection
        $this->connection = new mysqli($host, $user, $pass, $name, $port);

        // Check for connection errors
        if (mysqli_connect_errno() || !$this->connection) {
            $this->error[] = "Connect failed: " . mysqli_connect_error();
            die('Database Error: ' . implode(', ', $this->error));
        }

        // Set the character set to UTF-8
        if (!$this->connection->set_charset("utf8")) {
            $this->error[] = "Error loading character set utf8: " . $this->connection->error;
            return false;
        }

        return true;
    }

    /**
     * Close the current database connection
     *
     * @return void
     */
    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = false;
        }
    }

    /**
     * Execute a MySQL query
     *
     * @param string $Q The SQL query to execute
     *
     * @return mysqli_result|false The result set on success, false on failure
     */
    public function query($Q)
    {
        if (empty($this->connection)) {
            $this->error[] = "No active database connection.";
            return false;
        }

        // Replace placeholder prefix with actual database prefix
        $Q = preg_replace('/#{prefix}#i/', MT_config::db_prefix, $Q);

        if (!$Q) {
            $this->error[] = "Invalid query after prefix replacement.";
            return false;
        }

        // Execute the query
        $result = $this->connection->query($Q);

        // Check for query execution errors
        if (!$result) {
            $this->error[] = "Query Error [{$this->connection->sqlstate}]: " . $this->connection->error;
            return false;
        }

        return $result;
    }

    /**
     * Execute a query and fetch a single row
     *
     * @param string $Q The SQL query to execute
     *
     * @return array|false Associative array of the row data, or false on failure
     */
    public function line($Q)
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        $result = $this->query($Q);

        if (!$result) {
            return false;
        } else {
            // Log the number of affected rows for line queries
            $this->work['line'][] = "{$this->connection->affected_rows} Row(s) read.";
        }

        // Fetch and return the associative array of the first row
        return $result->fetch_assoc() ?: false;
    }

    /**
     * Execute a query and fetch all rows as a table
     *
     * @param string          $Q    The SQL query to execute
     * @param string|false    $id    Optional column name to index the results
     * @param bool            $unic  Whether to store multiple rows under the same index
     *
     * @return array|false Array of fetched rows indexed by $id if provided, or false on failure
     */
    public function table($Q, $id = false, $unic = false)
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        $result = $this->query($Q);

        if (!$result) {
            return false;
        } else {
            // Log the number of affected rows for table queries
            $this->work['table'][] = "{$this->connection->affected_rows} Row(s) read.";
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Process the row data
            $processedData = $this->mrus($row);

            if ($id && isset($processedData[$id])) {
                if ($unic) {
                    // Store multiple rows under the same index
                    $data[$processedData[$id]][] = $processedData;
                } else {
                    // Overwrite with the latest row for the same index
                    $data[$processedData[$id]] = $processedData;
                }
            } else {
                // Append the row to the data array
                $data[] = $processedData;
            }
        }

        return !empty($data) ? $data : false;
    }

    /**
     * Insert a new record into a table
     *
     * @param string $table    The table name
     * @param array  $args     Associative array of column => value
     * @param string $where    Optional WHERE clause
     * @param array  $options  Additional options (e.g., 'conditions', 'extra')
     *
     * @return int|false The inserted record's ID on success, false on failure
     */
    public function insert($table, $args = [], $where = '', $options = [])
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        $columns = [];
        $values = [];

        // Prepare columns and values for the INSERT statement
        foreach ($args as $key => $value) {
            $columns[] = "`$key`";
            $values[] = $value === null ? 'NULL' : "'" . $this->connection->real_escape_string($value) . "'";
        }

        // Build optional clauses
        $whereClause = !empty($where) ? " $where " : '';
        $conditions = !empty($options['conditions']) ? $options['conditions'] : '';
        $extra = !empty($options['extra']) ? " " . $options['extra'] . " " : '';

        // Construct the INSERT query
        $query = "INSERT $conditions INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")$whereClause$extra;";

        // Execute the query
        $result = $this->query($query);

        if (!$result) {
            return false;
        } else {
            // Log the number of inserted rows
            $this->work[$table][] = "{$this->connection->affected_rows} Row(s) inserted.";
        }

        // Return the ID of the inserted record
        return $this->connection->insert_id;
    }

    /**
     * Delete records from a table based on a condition
     *
     * @param string      $table     The table name
     * @param string|null $condition The WHERE condition for deletion
     *
     * @return bool True on success, false on failure
     */
    public function delete($table, $condition = null)
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        if (empty($condition)) {
            $this->error[] = "No condition provided for DELETE operation.";
            return false;
        }

        // Construct the DELETE query
        $query = "DELETE FROM `$table` WHERE $condition";
        $result = $this->query($query);

        if (!$result) {
            return false;
        } else {
            // Log the number of deleted rows
            $this->work[$table][] = "{$this->connection->affected_rows} Row(s) deleted.";
        }

        return true;
    }

    /**
     * Update records in a table based on a condition
     *
     * @param string $table     The table name
     * @param array  $data      Associative array of column => new value
     * @param string $condition The WHERE condition for updating
     * @param int    $limit     Number of records to update (default: 1)
     *
     * @return bool True on success, false on failure
     */
    public function update($table, $data, $condition, $limit = 1)
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        if (empty($data)) {
            $this->error[] = "No data provided for UPDATE operation.";
            return false;
        }

        $set = [];

        // Prepare SET clauses for the UPDATE statement
        foreach ($data as $key => $value) {
            $escapedValue = $value === null ? 'NULL' : "'" . $this->connection->real_escape_string($value) . "'";
            $set[] = "`$key` = $escapedValue";
        }

        // Build the LIMIT clause
        $limitClause = ($limit > 0) ? "LIMIT $limit" : '';

        // Construct the UPDATE query
        $query = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $condition $limitClause";
        $result = $this->query($query);

        if (!$result) {
            return false;
        } else {
            // Log the number of updated rows
            $this->work[$table][] = "{$this->connection->affected_rows} Row(s) updated.";
        }

        return true;
    }

    /**
     * Count the number of records in a table based on a condition
     *
     * @param string      $table     The table name
     * @param string|null $condition The WHERE condition for counting
     *
     * @return int|false The count of records, or false on failure
     */
    public function countRecords($table, $condition = null)
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        // Construct the COUNT query
        $query = $condition ? "SELECT COUNT(*) AS count FROM `$table` WHERE $condition" : "SELECT COUNT(*) AS count FROM `$table`";
        $result = $this->line($query);

        if (!$result) {
            return false;
        } else {
            // Log the counting operation
            $this->work[$table][] = "{$this->connection->affected_rows} Row(s) counted.";
        }

        return isset($result['count']) ? (int)$result['count'] : false;
    }

    /**
     * Get the ID of the last inserted record
     *
     * @return int|false The last insert ID, or false if no connection
     */
    public function lastId()
    {
        if (!$this->connection) {
            $this->error[] = "No active database connection.";
            return false;
        }

        return $this->connection->insert_id;
    }

    /**
     * Process and sanitize input strings
     *
     * @param mixed $input    The input data to process
     * @param bool  $checkBr  Whether to convert newlines to <br> tags
     *
     * @return mixed The processed string or array
     */
    private function mrus($input, $checkBr = true)
    {
        if (is_array($input)) {
            // Recursively process arrays
            foreach ($input as $key => $value) {
                $input[$key] = $this->mrus($value, $checkBr);
            }
            return $input;
        }

        // Replace escaped characters with their actual representations
        $str = str_replace(
            ["\\'", '\\"', '\r', '\n\r', '\r\n', '\n'],
            ["'", '"', '', "\n", "\n", $checkBr ? '<br>' : ' '],
            $input
        );

        return $str;
    }
}
?>
