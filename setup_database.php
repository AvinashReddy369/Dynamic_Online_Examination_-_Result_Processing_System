<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$port = 3306;

// Create connection without DB to create DB first
$conn = new mysqli($host, $user, $pass, "", $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS online_exam";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
    exit();
}

// Select DB
$conn->select_db("online_exam");

// Create Users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_users) === TRUE) {
    echo "Table users created successfully<br>";
}

// Create Results table
$sql_results = "CREATE TABLE IF NOT EXISTS results (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    course VARCHAR(100),
    score INT(3),
    total INT(3),
    exam_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql_results) === TRUE) {
    echo "Table results created successfully<br>";
}

// Drop Questions table to reset it nicely
$conn->query("DROP TABLE IF EXISTS questions");

// Create Questions table
$sql_questions = "CREATE TABLE IF NOT EXISTS questions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course VARCHAR(50) NOT NULL,
    question TEXT NOT NULL,
    opt1 VARCHAR(255) NOT NULL,
    opt2 VARCHAR(255) NOT NULL,
    opt3 VARCHAR(255) NOT NULL,
    opt4 VARCHAR(255) NOT NULL,
    ans INT(1) NOT NULL
)";

if ($conn->query($sql_questions) === TRUE) {
    echo "Table questions created successfully<br>";
}

// Insert Questions Data
$insert_q = $conn->prepare("INSERT INTO questions (course, question, opt1, opt2, opt3, opt4, ans) VALUES (?, ?, ?, ?, ?, ?, ?)");

$q_data = [
    // DBMS
    ['DBMS', 'What does DBMS stand for?', 'Database Management System', 'Data Board Management System', 'Database Maintenance System', 'Data Backup Management System', 1],
    ['DBMS', 'Which SQL statement is used to extract data from a database?', 'GET', 'OPEN', 'EXTRACT', 'SELECT', 4],
    ['DBMS', 'Which of the following is not a relational database model?', 'MySQL', 'MongoDB', 'PostgreSQL', 'Oracle', 2],
    ['DBMS', 'Which of the following is used to delete an existing row from a database table?', 'REMOVE', 'DELETE', 'DROP', 'TRUNCATE', 2],
    ['DBMS', 'A primary key must be ______.', 'Unique and not null', 'Null', 'Shared', 'Can be a duplicate', 1],
    ['DBMS', 'Which command is used to remove a table from the database?', 'DROP TABLE', 'DELETE TABLE', 'REMOVE TABLE', 'TRUNCATE TABLE', 1],
    ['DBMS', 'What is a foreign key?', 'A key that uniquely identifies a record', 'A key used to encrypt the database', 'A key that links two tables together', 'A key used for indexing', 3],
    ['DBMS', 'Data manipulation language (DML) includes:', 'CREATE, ALTER, DROP', 'SELECT, INSERT, UPDATE, DELETE', 'GRANT, REVOKE', 'COMMIT, ROLLBACK', 2],
    ['DBMS', 'Which of the following is a NoSQL database?', 'MySQL', 'Microsoft SQL Server', 'Cassandra', 'Oracle', 3],
    ['DBMS', 'What does ACID stand for in DBMS?', 'Atomicity, Consistency, Isolation, Durability', 'Accuracy, Consistency, Isolation, Dependency', 'Atomicity, Correctness, Integrity, Durability', 'Accuracy, Completeness, Independence, Durability', 1],
    
    // DS
    ['DS', 'How can we describe an array in the best possible way?', 'Container of different types', 'Container of similar physical structure', 'Container that stores the elements of similar types', 'None', 3],
    ['DS', 'Which data structure is used for implementing recursion?', 'Queue', 'Stack', 'Linked List', 'Array', 2],
    ['DS', 'Which of the following data structures is non-linear?', 'Array', 'Linked List', 'Stack', 'Tree', 4],
    ['DS', 'Which of the following allows elements to be added to either end, but not the middle?', 'Queue', 'Stack', 'Deque (Double-ended queue)', 'Tree', 3],
    ['DS', 'What is the time complexity of a purely linear search?', 'O(1)', 'O(n)', 'O(log n)', 'O(n log n)', 2],
    ['DS', 'Which data structure follows the First-In-First-Out (FIFO) principle?', 'Stack', 'Tree', 'Queue', 'Graph', 3],
    ['DS', 'What is the worst-case time complexity of QuickSort?', 'O(n)', 'O(n log n)', 'O(n^2)', 'O(log n)', 3],
    ['DS', 'Which tree property ensures that the left child is smaller and right child is strictly greater than the parent?', 'AVL Tree Property', 'Binary Search Tree Property', 'Heap Property', 'Red-Black Tree Property', 2],
    ['DS', 'In a hash table, what handles the situation when two keys hash to the same index?', 'Probing or Chaining', 'Sorting', 'Re-hashing the entire table', 'Skipping the value', 1],
    ['DS', 'Graphs can be represented using:', 'Adjacency matrix only', 'Adjacency list only', 'Adjacency matrix or Adjacency list', 'Hash maps only', 3],
    
    // CN
    ['CN', 'What does the term OSI stand for?', 'Open Systems Interconnection', 'Operating Systems Interface', 'Open Software Interface', 'Optical System INterface', 1],
    ['CN', 'Which layer of the OSI model does the router operate at?', 'Data Link Layer', 'Transport Layer', 'Network Layer', 'Session Layer', 3],
    ['CN', 'What is the format of an IPv4 address?', '16 bit', '32 bit', '64 bit', '128 bit', 2],
    ['CN', 'Which protocol is used to reliably transmit emails?', 'HTTP', 'FTP', 'SMTP', 'SNMP', 3],
    ['CN', 'The term HTTP stands for?', 'High-level Text Transfer Protocol', 'Hypertext Transfer Protocol', 'Hyper Transfer Text Protocol', 'Hyper Test Transfer Protocol', 2],
    ['CN', 'What is the default port number for HTTP?', '21', '22', '80', '443', 3],
    ['CN', 'Which network topology requires a central hub?', 'Ring', 'Bus', 'Star', 'Mesh', 3],
    ['CN', 'What does DNS stand for?', 'Data Network System', 'Domain Name System', 'Dynamic Network Service', 'Domain Naming Server', 2],
    ['CN', 'Which of these protocols ensures guaranteed delivery of packets?', 'UDP', 'TCP', 'IP', 'ICMP', 2],
    ['CN', 'Which address is used to uniquely identify a network interface card (NIC)?', 'IP Address', 'URL', 'MAC Address', 'Port Number', 3],
    
    // OS
    ['OS', 'What is an operating system?', 'Hardware', 'A program that manages hardware and software', 'Antivirus', 'System unit', 2],
    ['OS', 'Which of the following is not an operating system?', 'Oracle', 'Linux', 'Windows', 'DOS', 1],
    ['OS', 'What is a process in an OS?', 'A program in execution', 'A hard disk part', 'A network layer', 'A driver', 1],
    ['OS', 'What is virtual memory?', 'A technique that allows execution of processes that are not completely in memory', 'A memory card', 'RAM', 'Hard disk space', 1],
    ['OS', 'Which algorithm is not a CPU scheduling algorithm?', 'Round Robin', 'First Come First Serve', 'Shortest Job First', 'Bubble Sort', 4],
    ['OS', 'What is a thread in an operating system?', 'A lightweight process', 'A piece of hardware', 'A system call', 'A deadlock prevention mechanism', 1],
    ['OS', 'A situation where two or more processes are blocked forever, waiting for each other is called:', 'Starvation', 'Deadlock', 'Mutex', 'Semaphore', 2],
    ['OS', 'What is the main function of the command interpreter?', 'To provide an interface for user to issue commands', 'To get and execute the next user-specified command', 'To handle files and directories', 'Both A and B', 4],
    ['OS', 'Page fault occurs when:', 'The page is corrupted by application data', 'The page is in memory', 'The page is not in memory', 'The system requires shutting down', 3],
    ['OS', 'Which directory implementation is used in most modern operating systems?', 'Single-level directory', 'Two-level directory', 'Tree-structured directory', 'Linear directory', 3],
    
    // Java (Adding a new section briefly, just for extra variety!)
    ['Java', 'Which of the following is not a Java features?', 'Dynamic', 'Architecture Neutral', 'Use of pointers', 'Object-oriented', 3],
    ['Java', 'What is the return type of the hashCode() method in the Object class?', 'Object', 'int', 'long', 'void', 2],
    ['Java', 'Which keyword is used for accessing the features of a package?', 'package', 'import', 'extends', 'export', 2],
    ['Java', 'In Java, an interface can _____ another interface.', 'extend', 'implement', 'inherit', 'not interact with', 1],
    ['Java', 'Which exception is thrown when divide by zero occurs?', 'NullPointerException', 'ArithmeticException', 'NumberFormatException', 'ArrayIndexOutOfBoundsException', 2]
];

foreach ($q_data as $row) {
    $insert_q->bind_param("ssssssi", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
    $insert_q->execute();
}
$total_inserted = count($q_data);
echo "Inserted $total_inserted questions successfully into questions table.<br>";

$conn->close();

echo "<br><a href='index.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
?>
