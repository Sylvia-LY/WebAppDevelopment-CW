<?php

// 755 file permission is required for this to execute

class CommentsAPI {

    private $dbh = null;
    private $status;
    private $data = array();

    function __construct() {
        // to be replaced with actual values
        $dsn = '';
        $user = '';
        $password = '';
        
        try {
            $this->dbh = new PDO($dsn, $user, $password);
            // sets the error reporting mode
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            $this->status = 500;
            $this->data = ['error' => 'database connection failed: ' . $e->getMessage()];
        }
        
    }

    function __destruct() {
        $this->dbh = null;
    }

    public function handle_request() {
        if ($this->status !== 500) {
            // determine HTTP method
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $this->read();
                    break;
                case 'POST':
                    $this->write();
                    break;
                default:
                    $this->status = 405;
                    $this->data = ['error' => 'method not allowed'];
            }
        }


        http_response_code($this->status);
        // send response as json
        header('Content-Type: application/json');
        echo json_encode($this->data);
    }

    private function read() {
        // presence check
        if (!isset($_GET['oid'])) {
            $this->status = 400;
            $this->data = ['error' => 'oid is required'];
            return;
        }

        $oid = $_GET['oid'];

        if (strlen($oid) === 0 || strlen($oid) > 32 || !ctype_alnum($oid)) {
            $this->status = 400;
            $this->data = ['error' => 'invalid input'];
            return;
        }

        try {
            $sql = 'SELECT id, DATE_FORMAT(`date`, "%d %M %Y") as date, name, comment FROM `comments` WHERE `oid` = :oid';
            $sth = $this->dbh->prepare($sql);
            $sth->bindParam(':oid', $oid, PDO::PARAM_STR);
            $sth->execute();

            if ($sth->rowCount() > 0) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
                $comments = [];

                foreach ($result as $row) {
                    array_push($comments, ['id' => (int)$row['id'], 'date' => $row['date'], 'name' => $row['name'], 'comment' => $row['comment']]);
                }

                $this->data = array('oid' => $oid, 'comments' => $comments);
                $this->status = 200;

            }
            else {
                // no content
                $this->status = 204;

            }

        }
        catch (PDOException $e) {
            $this->status = 500;
            $this->data = ['error' => 'database error: ' . $e->getMessage()];
        }



    }

    private function write() {
        if (!isset($_POST['oid']) || !isset($_POST['name']) || !isset($_POST['comment'])) {
            $this->status = 400;
            $this->data = ['error' => 'oid, name, and comment are required'];
            return;
        }

        // trim input values and prepare for the non empty check below
        $oid = trim($_POST['oid']);
        $name = trim($_POST['name']);
        $comment = trim($_POST['comment']);

        if ($oid === '' || $name === '' || $comment === '' || strlen($oid) > 32 || strlen($name) > 64 || !ctype_alnum($oid)) {
            $this->status = 400;
            $this->data = ['error' => 'invalid input'];
            return;
        }

        try {
            $sql = 'INSERT INTO `comments` (`oid`, `name`, `comment`) VALUES (:oid, :name, :comment)';
            $sth = $this->dbh->prepare($sql);
            $sth->bindParam(':oid', $oid, PDO::PARAM_STR);
            $sth->bindParam(':name', $name, PDO::PARAM_STR);
            $sth->bindParam(':comment', $comment, PDO::PARAM_STR);
            $sth->execute();

            $this->data = array('id' => (int)$this->dbh->lastInsertId());
            // 201 created
            $this->status = 201;


        }
        catch (PDOException $e) {
            $this->status = 500;
            $this->data = ['error' => 'database error: ' . $e->getMessage()];
        }

    }

}


$api = new CommentsAPI();
$api->handle_request();