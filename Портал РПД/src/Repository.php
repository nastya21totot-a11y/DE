<?php
declare(strict_types=1);

final class Repository
{
    private PDO $db;

    public function __construct(array $config)
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $this->db = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]);
    }

    public function findUser(string $login): ?array
    {
        return $this->query('SELECT * FROM users WHERE login = ?', [$login])->fetch() ?: null;
    }

    public function register(array $fields, string $password): bool
    {
        $this->db->beginTransaction();
        try {
            $this->query(
                'INSERT INTO users (login, password_hash, full_name, phone, email) VALUES (?, ?, ?, ?, ?)',
                [$fields['login'], password_hash($password, PASSWORD_DEFAULT), $fields['full_name'], $fields['phone'], $fields['email']]
            );
            $teacher = (int)$this->db->lastInsertId();
            $this->query('INSERT INTO teacher_disciplines (teacher_id, discipline_id) SELECT ?, id FROM disciplines', [$teacher]);
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof PDOException && (int)($error->errorInfo[1] ?? 0) === 1062) {
                return false;
            }
            throw $error;
        }
    }

    public function disciplines(?int $teacher = null): array
    {
        if ($teacher === null) {
            return $this->query('SELECT id, name FROM disciplines ORDER BY id')->fetchAll();
        }
        return $this->query(
            'SELECT d.id, d.name FROM disciplines d JOIN teacher_disciplines td ON td.discipline_id = d.id
             WHERE td.teacher_id = ? ORDER BY d.id', [$teacher]
        )->fetchAll();
    }

    public function submit(int $teacher, int $discipline, string $version, string $file, string $name, string $comment): void
    {
        $this->query(
            'INSERT INTO applications (teacher_id, discipline_id, version_number, stored_file, original_file, version_comment)
             VALUES (?, ?, ?, ?, ?, ?)', [$teacher, $discipline, $version, $file, $name, $comment]
        );
    }

    public function saveTeacherComment(int $id, int $teacher, string $comment): bool
    {
        return $this->query(
            "UPDATE applications SET teacher_comment = ? WHERE id = ? AND teacher_id = ? AND status = 'rejected'",
            [$comment, $id, $teacher]
        )->rowCount() > 0;
    }

    public function review(int $id, string $status, string $comment): bool
    {
        return $this->query(
            'UPDATE applications SET status = ?, chair_comment = ? WHERE id = ?',
            [$status, $comment === '' ? null : $comment, $id]
        )->rowCount() > 0;
    }

    public function applicationFile(int $id, array $user): ?array
    {
        return ($user['role'] === 'admin'
            ? $this->query('SELECT stored_file, original_file FROM applications WHERE id = ?', [$id])
            : $this->query('SELECT stored_file, original_file FROM applications WHERE id = ? AND teacher_id = ?', [$id, $user['id']])
        )->fetch() ?: null;
    }

    public function applications(?int $teacher, int $discipline, string $status, int $page): array
    {
        $conditions = [];
        $params = [];
        foreach (['a.teacher_id' => $teacher, 'a.discipline_id' => $discipline, 'a.status' => $status] as $column => $value) {
            if ($value) {
                $conditions[] = "$column = ?";
                $params[] = $value;
            }
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $total = (int)$this->query('SELECT COUNT(*) FROM applications a' . $where, $params)->fetchColumn();
        $limit = 6;
        $pages = max(1, (int)ceil($total / $limit));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $limit;
        $rows = $this->query(
            'SELECT a.*, d.name AS discipline, u.full_name AS teacher, u.email
             FROM applications a JOIN disciplines d ON d.id = a.discipline_id JOIN users u ON u.id = a.teacher_id'
             . $where . " ORDER BY a.submitted_at DESC, a.id DESC LIMIT $limit OFFSET $offset",
            $params
        )->fetchAll();
        return [$rows, $page, $pages, $total];
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}
