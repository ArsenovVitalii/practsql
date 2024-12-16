<!--#0 — так как для всех заданий предполагается, что есть база данных school_management, то её для начала стоит создать-->
<?php
    /*так как таблиц будет больше, чем одна, то лучше бы создать функцию для добавления таблиц*/
    function create_table($connection, $table_query) {
        if ($connection->query($table_query)) {
            echo "Таблица создана успешно" . "<br>";
        } else {
            echo "Ошибка при создании таблицы: " . $connection->error . "<br>";
        }
    }

    /*и сразу же сами таблицы, а также само название БД (пришлось немного изменить структуру, ибо с предложенной по ТЗ выполнение всего функционала невозможно):*/
    $db_name = "school_management";
    $groups_table_query = "
    CREATE TABLE IF NOT EXISTS groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE
    )
    ";
    $students_table_query = "
    CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        group_id INT,
        FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL
    )
    ";
    $teachers_table_query = "
    CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )
    ";
    $courses_table_query = "
    CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        teacher_id INT,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
    );
    ";
    $student_courses_table_query = "
    CREATE TABLE IF NOT EXISTS student_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        course_id INT,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )
    ";

    /*теперь исполним подключение к БД*/
    $conn = new mysqli("localhost", "root", "");
    if ($conn->connect_error) {
        die("Подключение к " . $db_name . " <b>не</b> выполнено" . $conn->connect_error . "<br>");
    }
    echo "Подключение к " . $db_name . " выполнено" . "<br>";

    /*здесь же представлено создание БД*/
    if($conn->query("CREATE DATABASE IF NOT EXISTS $db_name")) {
        echo "База данных '$db_name' создана" . "<br>";
    } else {
        echo "Ошибка при создании базы данных: " . $conn->error . "<br>";
    }

    /*выберем получившуюся БД для последующего её наполнения*/
    if ($conn->select_db($db_name)) {
        echo "Используется база данных '$db_name'" . "<br>";
    } else {
        echo "Ошибка выбора базы данных: " . $conn->error . "<br>";
    }

    /*наполнение БД:*/
    create_table($conn, $groups_table_query);
    create_table($conn, $students_table_query);
    create_table($conn, $teachers_table_query);
    create_table($conn, $courses_table_query);
    create_table($conn, $student_courses_table_query);

    /*прервём подключение к БД*/
    $conn->close();
    echo "Подключние к '$db_name'" . " прервано" . "<br><br>";
?>



<!--#1-->
<?php
    /*исполним подключение к БД*/
    $conn = new mysqli("localhost", "root", "", "school_management");
    if ($conn->connect_error) {
        die("Подключение к " . $db_name . " <b>не</b> выполнено" . $conn->connect_error . "<br>");
    }
    echo "Подключение к " . $db_name . " выполнено" . "<br><br>";
?>



<!--#2-->
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $sql = "INSERT INTO students (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            /*чтобы БД не переполнилась постоянно сохранённой формой*/
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Ошибка: " . $stmt->error;
        }
        echo "Студент добавлен успешно";
    }
?>

<form method="POST">
    Имя студента: <input type="text" id="name" name="name" required>
    <button type="submit" name="add_student">Добавить студента</button>
</form>



<!--#3-->
<?php
    $sql = "SELECT students.id, students.name, students.group_id, groups.name AS group_name 
        FROM students
        LEFT JOIN groups ON students.group_id = groups.id
    ";
    $stmt = $conn->query($sql);
    $result = [];
    if ($stmt) {
        $result = $stmt->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "Ошибка: " . $conn->error;
    }
?>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Группа</th>
        <th>ID группы</th>
    </tr>
    <?php foreach ($result as $student): ?>
        <tr>
            <td><?= $student['id'] ?></td>
            <td><?= $student['name'] ?></td>
            <td><?= $student['group_name'] ?? '—' ?></td>
            <td><?= $student['group_id'] ?? '—' ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<br>



<!--#4-->
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
        $name = $_POST['name'];
        $sql = "INSERT INTO groups (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            /*чтобы БД не переполнилась постоянно сохранённой формой*/
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Ошибка: " . $stmt->error;
        }
        echo "Группа добавлена успешно";
    }
?>

<form method="POST">
    Группа: <input type="text" id="group_name" name="name" required>
    <button type="submit" name="add_group">Добавить группу</button>
</form>



<!--#5-->
<?php
    $sql_students = "SELECT id, name FROM students";
    $sql_groups = "SELECT id, name FROM groups";
    $students = $conn->query($sql_students);
    $groups = $conn->query($sql_groups);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
        $student_id = $_POST['student_id'];
        $group_id = $_POST['group_id'];

        if (!empty($student_id) && isset($group_id)) {
            $sql = "UPDATE students SET group_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $group_id, $student_id);

            if ($stmt->execute()) {
                echo "Группа обновлена";
                /*чтобы БД не переполнилась постоянно сохранённой формой*/
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Ошибка обновления группы: " . $conn->error;
            }
        } else {
            echo "Выберите студента и группу";
        }
    }
?>

<form method="POST">
    Студент: <select name="student_id" id="student" required>
        <option value="">Выбрать студента</option>
        <?php while ($student = $students->fetch_assoc()): ?>
            <option value="<?= $student['id'] ?>"><?= $student['name'] ?></option>
        <?php endwhile; ?>
    </select>
    <br><br>

    Группа: <select name="group_id" id="group" required>
        <option value="">Выбрать группу</option>
        <?php while ($group = $groups->fetch_assoc()): ?>
            <option value="<?= $group['id'] ?>"><?= $group['name'] ?></option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <button type="submit" name="update_group">Обновить группу</button>
</form>



<!--#6-->
<?php
    $sql = "SELECT students.name AS student_name, groups.name AS group_name 
                FROM students 
                LEFT JOIN groups ON students.group_id = groups.id";
    $stmt = $conn->query($sql);

    $result = [];
    if ($stmt) {
        $result = $stmt->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "Ошибка: " . $conn->error;
    }
?>

<table border="1">
    <tr>
        <th>Имя Студента</th>
        <th>Группа</th>
    </tr>
    <?php foreach ($result as $row): ?>
        <tr>
            <td><?= $row["student_name"] ?></td>
            <td><?= $row["group_name"] ?? "—" ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<br>



<!--#7-->
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_student_course'])) {
        $sql = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $_POST['student_id'], $_POST['course_id']);
        if ($stmt->execute()) {
            echo "Студент добавлен на курс";
            /*чтобы БД не переполнилась постоянно сохранённой формой*/
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Ошибка: " . $stmt->error;
        }
    }

    $students_sql = "SELECT id, name FROM students";
    $courses_sql = "SELECT id, name FROM courses";
    $students = $conn->query($students_sql);
    $courses = $conn->query($courses_sql);
?>

<form method="POST">
        Студент: <select name="student_id" required>
            <?php while ($student = $students->fetch_assoc()): ?>
                <option value="<?= $student['id'] ?>"><?= $student['name'] ?></option>
            <?php endwhile; ?>
        </select>
    <br>
        Курс: <select name="course_id" required>
            <?php while ($course = $courses->fetch_assoc()): ?>
                <option value="<?= $course['id'] ?>"><?= $course['name'] ?></option>
            <?php endwhile; ?>
        </select>
    <br>
    <button type="submit" name="register_student_course">Зарегистрировать</button>
</form>



<!--#8-->
<?php
    $sql = "SELECT courses.name AS course_name, COUNT(student_courses.student_id) AS student_count
        FROM courses
        LEFT JOIN student_courses ON courses.id = student_courses.course_id
        GROUP BY courses.id, courses.name";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

if ($result): ?>
    <table border="1">
        <tr>
            <th>Название курса</th>
            <th>Количество студентов</th>
        </tr>
        <?php while ($course = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $course['course_name'] ?></td>
                <td><?= $course['student_count'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <br>
<?php endif; ?>



<!--#9-->
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
        $student_id = $_POST['student_id'];
        $sql = "DELETE FROM students WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        if ($stmt->execute()) {
            /*чтобы БД не переполнилась постоянно сохранённой формой*/
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Ошибка: " . $stmt->error;
        }
        echo "Студент отчислен. Помянем";
    }
    
?>

<form method="POST">
    ID Студента: <input type="number" id="student_id" name="student_id" required>
    <button type="submit" name="delete_student">Отчислить студента</button>
</form>
<br>



<!--#10-->
<?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['student_id']) && isset($_POST['name'])) {
            $student_id = $_POST['student_id'];
            $name = $_POST['name'];

            $sql = "UPDATE students SET name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $name, $student_id);
            $stmt->execute();
            /*if ($stmt->execute()) {
                echo "Имя студента обновлено";
                // Избежание повторной отправки формы
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Ошибка: " . $stmt->error;
            }*/
        }
    }
?>

<form method="POST" action="">
    ID студента: <input type="number" id="student_id" name="student_id" required>
    Новое имя: <input type="text" id="name" name="name" required>
    <button type="submit">Обновить имя</button>
</form>



<!--#11-->
<?php
    $sql = "SELECT teachers.name AS teacher_name, courses.name AS course_name
            FROM teachers
            LEFT JOIN courses ON teachers.id = courses.teacher_id";
    $result = $conn->query($sql);
?>
    
<table border="1">
    <tr>
        <th>Преподаватель</th>
        <th>Курс</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['teacher_name'] ?></td>
        <td><?= $row['course_name'] ?? '—' ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<br>



<!--#12-->
<form method="GET">
    Имя студента: <input type="text" name="name" required>
    <button type="submit">Поиск</button>
</form>

<?php
if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $sql = "SELECT students.id, students.name, students.group_id, groups.name AS group_name 
        FROM students
        LEFT JOIN groups ON students.group_id = groups.id
            WHERE students.name LIKE ?";
    $stmt = $conn->prepare($sql);
    $search = "%$name%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Группа</th>
        <th>ID группы</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td><?= $row['group_name'] ?? '—' ?></td>
        <td><?= $row['group_id'] ?? '—' ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php
}
?>



<!--#13-->
<?php
    $sql = "SELECT * FROM students WHERE group_id IS NULL";
    $result = $conn->query($sql);
?>

<p>Студенты без группы</p>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<br>



<!--#14-->
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
        $name = $_POST['name'];
        $sql = "INSERT INTO courses (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            /*чтобы БД не переполнилась постоянно сохранённой формой*/
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Ошибка: " . $stmt->error;
        }
        echo "Курс добавлен успешно";
    }
?>

<form method="POST">
    Название курса: <input type="text" id="name" name="name" required>
    <button type="submit" name="add_course">Добавить курс</button>
</form>



<!--#15-->
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
        $name = $_POST['name'];
        $sql = "INSERT INTO teachers (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            /*чтобы БД не переполнилась постоянно сохранённой формой*/
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Ошибка: " . $stmt->error;
        }
        echo "Преподаватель зарегистрирован успешно";
    }
?>

<form method="POST">
    Преподаватель: <input type="text" id="teacher_name" name="name" required>
    <button type="submit" name="add_teacher">Зарегистрировать преподавателя</button>
</form>



<!--#16-->
<form method="GET">
    Название курса: <input type="text" name="course_name" required>
    <button type="submit">Найти</button>
</form>

<?php
if (isset($_GET['course_name'])) {
    $course_name = $_GET['course_name'];
    $sql = "SELECT students.name AS student_name
            FROM students
            JOIN student_courses ON students.id = student_courses.student_id
            JOIN courses ON student_courses.course_id = courses.id
            WHERE courses.name LIKE ?";
    $stmt = $conn->prepare($sql);
    $search = "%$course_name%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

<table border="1">
    <tr>
        <th>Имя студента</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['student_name'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php
}
?>



<!--#17-->
<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_id = $_POST['course_id'];
    $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    if ($stmt->execute()) {
        echo "Курс удалён";
    } else {
        echo "Ошибка: " . $stmt->error;
    }
}
?>

<form method="POST">
    ID курса для удаления: <input type="number" name="course_id" required>
    <button type="submit">Удалить</button>
</form>



<!--#18-->
<?php
if (isset($_GET['group_id'])) {
    $group_id = $_GET['group_id'];
    $sql = "SELECT * FROM students WHERE group_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<form method="GET">
    Группа:
    <select name="group_id" required>
        <?php
        $groups = $conn->query("SELECT * FROM groups");
        while ($row = $groups->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['name']}</option>";
        }
        ?>
    </select>
    <button type="submit">Фильтровать</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<br>



<!--#19-->
<?php
$sql = "SELECT students.name AS student_name, COUNT(student_courses.course_id) AS course_count
        FROM students
        JOIN student_courses ON students.id = student_courses.student_id
        GROUP BY students.id
        HAVING course_count > 1";
$result = $conn->query($sql);
?>

<table border="1">
    <tr>
        <th>Имя студента</th>
        <th>Количество курсов</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['student_name'] ?></td>
        <td><?= $row['course_count'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<br>



<!--#20-->
<?php
    $sql = "SELECT teachers.name AS teacher_name, COUNT(student_courses.student_id) AS total_students
            FROM teachers
            JOIN courses ON teachers.id = courses.teacher_id
            JOIN student_courses ON courses.id = student_courses.course_id
            GROUP BY teachers.id";
    $result = $conn->query($sql);
    ?>

<table border="1">
    <tr>
        <th>Преподаватель</th>
        <th>Количество студентов</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['teacher_name'] ?></td>
        <td><?= $row['total_students'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>