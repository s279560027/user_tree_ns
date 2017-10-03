<?php

class Tree
{
    private $_db;

    public function __construct($db)
    {
        $this->_db = $db;
    }

    public function getFiles($folderId)
    {
        $stmt = $this->_db->prepare('SELECT * FROM tree WHERE `left` > (SELECT `left` FROM tree WHERE id = ?) AND `right` <  (SELECT `right` FROM tree WHERE id = ?) AND `type` = "file" ORDER BY `left`');
        $stmt->execute([$folderId, $folderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoot()
    {
        $stmt = $this->_db->prepare('SELECT * FROM tree WHERE `left` = 1');
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add($name, $type = 'folder', $parentId = 0)
    {
        if ($parentId === 0) {
            $stmt = $this->_db->prepare('INSERT INTO tree(`left`, `right`, `level`, `name`, `type`) VALUES(1, 2, 0, ?, ?)');
            $stmt->execute([$name, 'folder']);
            return false;
        }
        $stmt = $this->_db->prepare('SELECT * FROM tree WHERE id = ?');
        $stmt->execute([$parentId]);
        $treeItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($treeItem['type'] === 'file') {
            return false;
        }

        $stmt = $this->_db->prepare('UPDATE tree SET `right` = `right` + 2, `left` = CASE WHEN `left` > ? then `left` + 2 else `left` end WHERE `right` >= ?');
        $stmt->execute([
                $treeItem['right'],
                $treeItem['right'],
            ]
        );

        $stmt = $this->_db->prepare('INSERT INTO tree(`left`, `right`, `level`, `name`, `type`) VALUES(?, ?, ?, ?, ?)');
        try {
            $stmt->execute([
                    $treeItem['right'],
                    $treeItem['right'] + 1,
                    $treeItem['level'] + 1,
                    $name,
                    $type
                ]
            );
            $insertId = $this->_db->lastInsertId();
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return isset($insertId) ? $insertId : false;
    }
}

try {
    $pdo = new PDO('sqlite:./db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS tree
	(
		id integer primary key AUTOINCREMENT,
		name varchar(100) null,
		`left` integer not null,
		`right` integer not null,
		level integer not null,
		type varchar(100) null
	);');
    $pdo->exec('DELETE FROM `tree`');
    $tree = new Tree($pdo);

    $tree->add('root', 0);
    $root = $tree->getRoot();

    $fotosId = $tree->add('Fotos', 'folder', $root['id']);

    $tree->add('FileCats', 'file', $fotosId);
    $tree->add('FileCats1', 'file', $fotosId);
    $tree->add('FileCats2', 'file', $fotosId);

    $videosId = $tree->add('Videos', 'folder', $root['id']);

    $tree->add('VideoDog', 'file', $videosId);
    $tree->add('VideoDog2', 'file', $videosId);
    $tree->add('VideoDog3', 'file', $videosId);

    $tree->getFiles($videosId);

} catch (Exception $e) {
    echo $e->getMessage() . "\n";
    echo 'file: ', $e->getFile() . "\n";
    echo 'line: ', $e->getLine() . "\n";
}



