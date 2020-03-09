<?php

/**
 * Created by PhpStorm.
 * User: felima
 * Date: 24/07/2018
 * Time: 11:56
 */

class DbOperation
{
    //Database connection link
    private $con;

    //Class constructor
    function __construct()
    {
        //Getting the DbConnect.php file
        require_once dirname(__FILE__) . '/DbConnect.php';

        //Creating a DbConnect object to connect to the database
        $db = new DbConnect();

        //Initializing our connection link of this class
        //by calling the method connect of DbConnect class
        $this->con = $db->connect();
    }

    //Method will create a new user
    public function createUser($name, $username, $pass, $email, $school, $facebookId = null)
    {

        //First we will check whether the user is already registered or not
        if ($this->isUserExists($username)) {
            return [USER_FAILURE, 'Username already exists'];
        }

        if($facebookId){
            if ($this->isFacebookExists($facebookId)) {
                return [USER_FAILURE, 'Facebook ID already exists'];
            }
        }
        if ($this->isEmailExist($email)) {
            return [USER_FAILURE, 'Email already exists'];
        }


        //Encrypting the password
        $password = md5($pass);

        //Generating an API Key
        $apikey = $this->generateApiKey();

        //Crating an statement
        $stmt = $this->con->prepare("INSERT INTO tbl_usuario(nome, email, userlogin, passlogin, api_key, instituicao, facebookId) values(?, ?, ?, ?, ?, ?, ?)");

        //Binding the parameters
        $stmt->bind_param("sssssss", $name, $email, $username, $password, $apikey, $school, $facebookId);

        //Executing the statment
        $result = $stmt->execute();

        //Closing the statment
        $stmt->close();

        //If statment executed successfully
        if ($result) {
            //Returning 101 means student created successfully
            return [USER_CREATED, 'User created'];
        } else {
            //Returning 103 means failed to create student
            return [USER_FAILURE, 'Error on create user'];
        }
    }

    public function updateUser($name, $username, $email, $school, $id)
    {
        $stmt = $this->con->prepare("UPDATE tbl_usuario SET nome = ?, userlogin = ?, email = ?, instituicao = ? WHERE id_usuario = ?");
        $stmt->bind_param("ssssi", $name, $username, $email, $school, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function updateUserPass($name, $username, $pass, $email, $school, $id)
    {
        $hashed_password =  md5($pass);
        $stmt = $this->con->prepare("UPDATE tbl_usuario SET nome = ?, userlogin = ?, email = ?, passlogin = ?, instituicao = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssssi", $name, $username, $email, $hashed_password, $school, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    //Method for user login
    public function userLogin($username, $pass)
    {
        if ($this->isUserExists($username) || $this->isEmailExist($username)) {
            //Generating password hash
            $password = md5($pass);
            //Creating query
            $stmt = $this->con->prepare("SELECT * FROM tbl_usuario WHERE (userlogin=? or email=?) and passlogin=?");
            //binding the parameters
            $stmt->bind_param("sss", $username, $username, $password);
            //executing the query
            $stmt->execute();
            //Storing result
            $stmt->store_result();
            //Getting the result
            $num_rows = $stmt->num_rows;
            //closing the statment
            $stmt->close();
            //If the result value is greater than 0 means user found in the database with given username and password
            //So returning true

            if ($num_rows > 0) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASSWORD_DO_NOT_MATCH;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }

    //Method for facebook login
    public function facebookLogin($facebookId)
    {
        if ($this->isFacebookExists($facebookId)) {
            //Creating query
            $stmt = $this->con->prepare("SELECT * FROM tbl_usuario WHERE facebookId=?");
            //binding the parameters
            $stmt->bind_param("s", $facebookId);
            //executing the query
            $stmt->execute();
            //Storing result
            $stmt->store_result();
            //Getting the result
            $num_rows = $stmt->num_rows;
            //closing the statment
            $stmt->close();
            //If the result value is greater than 0 means user found in the database with given username and password
            //So returning true

            if ($num_rows > 0) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASSWORD_DO_NOT_MATCH;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }

    //This method will return user detail
    public function getUser($username)
    {
        $stmt = $this->con->prepare("SELECT id_usuario,nome,userlogin,email,instituicao,api_key FROM tbl_usuario WHERE (userlogin=? or email=?)");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();

        $stmt->bind_result($id, $name, $username, $email, $school, $apikey);
        $stmt->fetch();
        $user = array();
        $user['id'] = $id;
        $user['username'] = $username;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['school'] = $school;
        $user['apikey'] = $apikey;

        //returning the user
        return $user;
    }

    public function getUserEmail($email)
    {
        $stmt = $this->con->prepare("SELECT userlogin FROM tbl_usuario WHERE email=? ");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->bind_result($username);
        $stmt->fetch();
        $user = $username;

        //returning the user
        return $user;
    }

    //This method will return user detail
    public function getUserFacebook($facebookId)
    {
        $stmt = $this->con->prepare("SELECT id_usuario,nome,userlogin,email,instituicao,api_key FROM tbl_usuario WHERE facebookId=?");
        $stmt->bind_param("s", $facebookId);
        $stmt->execute();

        $stmt->bind_result($id, $name, $username, $email, $school, $apikey);
        $stmt->fetch();
        $user = array();
        $user['id'] = $id;
        $user['username'] = $username;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['school'] = $school;
        $user['apikey'] = $apikey;

        //returning the user
        return $user;
    }

    //Checking whether a user already exist
    public function isUserExists($username)
    {
        $rs = $this->con->query("SELECT count(1) from tbl_usuario WHERE userlogin = '$username'");
        $row =  $rs->fetch_array();
        $rs->close();
        $result = (bool) $row[0];
        return $result;
        // var_dump($row);

        // $stmt = $this->con->prepare("SELECT id_usuario from tbl_usuario WHERE userlogin = ?");
        // $stmt->bind_param("s", $username);
        // $stmt->execute();
        // $stmt->store_result();
        // $num_rows = $stmt->num_rows;
        // $stmt->close();
        // return $num_rows > 0;
    }

    public function isEmailExist($email)
    {

        $rs = $this->con->query("SELECT count(1) from tbl_usuario WHERE email = '$email' ");
        $row =  $rs->fetch_array();
        $rs->close();
        $result = (bool) $row[0];
        return $result;

        // $stmt = $this->con->prepare("SELECT id_usuario FROM tbl_usuario WHERE email = ?");
        // $stmt->bind_param("s", $email);
        // $stmt->execute();
        // $stmt->store_result();
        // return $stmt->num_rows > 0;
    }

    //Checking whether a facebook already exist
    public function isFacebookExists($facebookId)
    {

        $rs = $this->con->query("SELECT count(1) from tbl_usuario WHERE facebookId = '$facebookId' ");
        $row =  $rs->fetch_array();
        $rs->close();
        $result = (bool) $row[0];
        return $result;


        // $stmt = $this->con->prepare("SELECT count(1) from tbl_usuario WHERE facebookId = ?");
        // $stmt->bind_param("s", $facebookId);
        // $stmt->execute();
        // $stmt->store_result();
        // $num_rows = $stmt->num_rows;
        // $stmt->close();
        // return $num_rows > 0;
    }

    //Checking whether a user already exist in analytics table
    private function isUserIDExists($id)
    {
        $stmt = $this->con->prepare("SELECT primeiro_acesso from tbl_analitics WHERE fk_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($date);
        $stmt->fetch();
        return $date;
    }

    public function generateApiReset($email)
    {
        $apiReset = $this->generateApiKey();

        $stmt = $this->con->prepare("UPDATE tbl_usuario SET resetAPI = ? WHERE email = ?");
        $stmt->bind_param("ss", $apiReset, $email);
        $stmt->execute();
        return $apiReset;
    }

    public function isValidUser($api_key)
    {
        //Creating an statement
        $stmt = $this->con->prepare("SELECT id_usuario from tbl_usuario WHERE api_key = ?");

        //Binding parameters to statement with this
        //the question mark of queries will be replaced with the actual values
        $stmt->bind_param("s", $api_key);

        //Executing the statement
        $stmt->execute();

        //Storing the results
        $stmt->store_result();

        //Getting the rows from the database
        //As API Key is always unique so we will get either a row or no row
        $num_rows = $stmt->num_rows;

        //If the fetched row is greater than 0 returning  true means user is valid
        return $num_rows > 0;
    }

    //This method will generate a unique api key
    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    public function updatePassword($newpassword, $api)
    {
        $hashed_password =  md5($newpassword);
        $resetAPI = null;

        $stmt = $this->con->prepare("UPDATE tbl_usuario SET passlogin = ?, resetAPI = ? WHERE resetAPI = ?");
        $stmt->bind_param("sss", $hashed_password, $resetAPI, $api);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return PASSWORD_CHANGED;
        return PASSWORD_NOT_CHANGED;
    }

    //Method will create a new discipline
    public function createDisciplineV2($name, $idUser, $days)
    {

        //Crating an statement
        // $stmt = $this->con->prepare("INSERT INTO tbl_disciplina(nome, fk_usuario,  sala, professor) values(?, ?, ?, ?)");
        $stmt = $this->con->prepare("INSERT INTO tbl_disciplina(nome, fk_usuario) values(?, ?)");

        //Binding the parameters
        $stmt->bind_param("si", $name, $idUser);

        //Executing the statment
        $result = $stmt->execute();

        //If statment executed successfully
        if ($result) {
            $this->createDisciplineHours($days, $this->con->insert_id);
            $this->createFrequency($idUser);
            return DISCIPLINE_CREATED;
        } else {
            return DISCIPLINE_FAILURE;
        }
    }

    //Method will create a new discipline
    public function createDiscipline($name, $day, $idUser, $hourBegin, $hourEnd)
    {

        //Crating an statement
        $stmt = $this->con->prepare("INSERT INTO tbl_disciplina(nome, dia, hr_inicio, hr_fim, fk_usuario) values(?, ?, ?, ?, ?)");

        //Binding the parameters
        $stmt->bind_param("ssssi", $name, $day, $hourBegin, $hourEnd, $idUser);

        //Executing the statment
        $result = $stmt->execute();

        //If statment executed successfully
        if ($result) {
            $this->migrateDisciplineById($this->con->insert_id, $day, $hourBegin, $hourEnd);
            $this->createFrequency($idUser);
            return DISCIPLINE_CREATED;
        } else {
            return DISCIPLINE_FAILURE;
        }
    }

    public function createDisciplineHours($days, $disciplineId)
    {
        foreach ($days as $day) {

            $day = (array) $day;
            $stmt = $this->con->prepare("INSERT INTO tbl_horarios(dia_semana, hora_inicio, hora_fim, fk_disciplina) values(?, ?, ?, ?)");

            $stmt->bind_param("issi", $day['day'], $day['hourBegin'], $day['hourEnd'], $disciplineId);

            $stmt->execute();

            $stmt->close();
        }
    }

    public function deleteDisciplineHours($disciplineId)
    {
        $stmt = $this->con->prepare("DELETE FROM tbl_horarios WHERE fk_disciplina = ?");
        $stmt->bind_param("i", $disciplineId);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function createReminder($title, $text, $idUser, $alarm)
    {

        //Crating an statement
        $stmt = $this->con->prepare("INSERT INTO tbl_lembrete(titulo, texto, alarme, fk_usuario) values(?, ?, ?, ?)");

        //Binding the parameters
        $stmt->bind_param("sssi", $title, $text, $alarm, $idUser);

        //Executing the statment
        $result = $stmt->execute();

        //If statment executed successfully
        if ($result) {
            return REMINDER_CREATED;
        } else {
            return REMINDER_FAILURE;
        }
    }

    public function createFrequency($idUser)
    {
        //get iddiscipline
        $idDiscipline = $this->getLastID('tbl_disciplina', 'id_disciplina');
        $init = 0;

        //Crating an statement 
        $stmt = $this->con->prepare("INSERT INTO tbl_frequencia(aulas, faltas, fk_usuario, fk_disciplina) values(?, ?, ?, ?)");

        //Binding the parameters
        $stmt->bind_param("iiii", $init, $init, $idUser, $idDiscipline);

        //Executing the statment
        $result = $stmt->execute();
    }

    public function getLastID($table, $field)
    {
        $stmt = $this->con->prepare("SELECT max(" . $field . ") from " . $table);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->fetch();
        return $id;
    }

    public function getPhotoName($id)
    {
        $stmt = $this->con->prepare("SELECT caminho FROM tbl_foto WHERE id_foto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($caminho);
        $stmt->fetch();
        return $caminho;
    }

    public function getLastAmount($id)
    {
        $stmt = $this->con->prepare("SELECT max(qtde_acessos) from tbl_analitics where fk_usuario = " . $id);
        $stmt->execute();
        $stmt->bind_result($amount);
        $stmt->fetch();
        return $amount;
    }

    //Method will create a new discipline
    public function addAnalytics($system, $versionSystem, $modelDevice, $brandDevice, $idUser)
    {

        $xAccess = 1;
        $date = date('Y-m-d H:i:s');
        error_log("date :" . $date);
        $firstDate = $this->isUserIDExists($idUser);
        if ($firstDate != null) {
            $xAccess = $this->getLastAmount($idUser) + 1;
        } else {
            $firstDate = date('Y-m-d H:i:s');
        }

        //Crating an statement
        $stmt = $this->con->prepare("INSERT INTO tbl_analitics(sistema, versao_sis, modelo_disp, marca_disp, primeiro_acesso, ultimo_acesso, qtde_acessos,fk_usuario) values(?, ?, ?, ?, ?, ?, ?, ?)");

        //Binding the parameters
        $stmt->bind_param("ssssssii", $system, $versionSystem, $modelDevice, $brandDevice, $firstDate, $date, $xAccess, $idUser);

        //Executing the statment
        $result = $stmt->execute();

        //If statment executed successfully
        if ($result) {
            return LOG_ADD;
        } else {
            return LOG_FAILURE;
        }
    }

    public function migrateDisciplineById($disciplineId, $day, $hourBegin, $hourEnd)
    {
        $days = explode(",", $day);
        foreach ($days as $index => $day) {
            if ($day === 'true') {
                $stmt = $this->con->prepare("INSERT INTO tbl_horarios(dia_semana, hora_inicio, hora_fim, fk_disciplina) values(?, ?, ?, ?)");

                $stmt->bind_param("issi", $index, $hourBegin, $hourEnd, $disciplineId);

                $result = $stmt->execute();

                $stmt->close();
            }
        }
    }

    public function migrateDiscipline($discipline)
    {
        $days = explode(",", $discipline['day']);
        foreach ($days as $index => $day) {
            if ($day === 'true') {
                $stmt = $this->con->prepare("INSERT INTO tbl_horarios(dia_semana, hora_inicio, hora_fim, fk_disciplina) values(?, ?, ?, ?)");

                $stmt->bind_param("issi", $index, $discipline['hourBegin'], $discipline['hourEnd'], $discipline['id']);

                $result = $stmt->execute();

                $stmt->close();
            }
        }
    }

    public function getAllDisciplines()
    {
        $stmt = $this->con->prepare("SELECT id_disciplina, nome, dia, hr_inicio, hr_fim FROM tbl_disciplina");
        $stmt->execute();
        $stmt->bind_result($id, $name, $day, $hourBegin, $hourEnd);
        $disciplines = array();

        while ($stmt->fetch()) {
            $discipline = array();
            $discipline['id'] = $id;
            $discipline['name'] = $name;
            $discipline['hourBegin'] = $hourBegin;
            $discipline['hourEnd'] = $hourEnd;
            $discipline['day'] = $day;
            array_push($disciplines, $discipline);
        }

        $stmt->close();
        return $disciplines;
    }

    public function getDisciplineHours($displineId)
    {
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);

        $stmt = $this->con->prepare("SELECT id_horario, dia_semana, hora_inicio, hora_fim FROM tbl_horarios WHERE fk_disciplina = ? ORDER BY dia_semana ASC");
        $stmt->bind_param("i", $displineId);
        $result = $stmt->execute();
        $stmt->bind_result($id, $day, $hourBegin, $hourEnd);
        $hours = array();

        while ($stmt->fetch()) {
            $hour = array();
            $hour['id'] = $id;
            $hour['day'] = $day;
            $hour['hourBegin'] = $hourBegin;
            $hour['hourEnd'] = $hourEnd;
            $hour['disciplineid'] = $displineId;
            array_push($hours, $hour);
        }

        $stmt->close();
        return $hours;
    }

    //Method to get all the disciplines of a particular user
    public function getDisciplinesV2($userid)
    {
        $stmt = $this->con->prepare("SELECT id_disciplina, nome FROM tbl_disciplina WHERE fk_usuario = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($id, $name);
        $disciplines = array();

        while ($stmt->fetch()) {
            $discipline = array();
            $discipline['id'] = $id;
            $discipline['name'] = $name;
            $discipline['userid'] = $userid;
            array_push($disciplines, $discipline);
        }

        $stmt->close();

        foreach ($disciplines as $index => $discipline) {
            $disciplines[$index]['days'] = $this->getDisciplineHours($discipline['id']);
        }

        return $disciplines;
    }

    //Method to get all the disciplines of a particular user
    public function getDisciplines($userid)
    {
        $stmt = $this->con->prepare("SELECT id_disciplina, nome, dia, hr_inicio, hr_fim, sala, professor
                                        FROM tbl_disciplina 
                                        WHERE fk_usuario = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($id, $name, $day, $hourBegin, $hourEnd, $room, $teacher);
        $disciplines = array();

        while ($stmt->fetch()) {
            $discipline = array();
            $discipline['id'] = $id;
            $discipline['name'] = $name;
            $discipline['hourBegin'] = $hourBegin;
            $discipline['hourEnd'] = $hourEnd;
            $discipline['day'] = $day;
            $discipline['userid'] = $userid;
            $discipline['room'] = $room;
            $discipline['teacher'] = $teacher;
            array_push($disciplines, $discipline);
        }

        $stmt->close();
        return $disciplines;
    }

    //Method to get all the frequency of a particular user
    public function getFrequency($id)
    {
        $stmt = $this->con->prepare("SELECT id_freq , fk_disciplina, aulas, faltas FROM tbl_frequencia WHERE fk_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id, $idDiscipline, $aulas, $faltas);
        $frequencies = array();

        while ($stmt->fetch()) {
            $frequency = array();
            $frequency['id'] = $id;
            $frequency['idDisciplina'] = $idDiscipline;
            $frequency['aulas'] = $aulas;
            $frequency['faltas'] = $faltas;
            array_push($frequencies, $frequency);
        }

        $stmt->close();
        return $frequencies;
    }

    public function getReminder($id)
    {
        $stmt = $this->con->prepare("SELECT id_lembrete , titulo, texto, alarme FROM tbl_lembrete WHERE fk_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id, $title, $text, $alarm);
        $reminders = array();

        while ($stmt->fetch()) {
            $reminder = array();
            $reminder['id'] = $id;
            $reminder['title'] = $title;
            $reminder['text'] = $text;
            $reminder['alarm'] = $alarm;
            array_push($reminders, $reminder);
        }

        $stmt->close();
        return $reminders;
    }

    public function updateDisciplineV2($name, $days, $id)
    {
        $stmt = $this->con->prepare("UPDATE tbl_disciplina 
                                       SET nome = ?
                                     WHERE id_disciplina = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $this->deleteDisciplineHours($id);
            $this->createDisciplineHours($days, $id);
            return true;
        }
        return false;
    }

    public function updateDiscipline($name, $hourBegin, $hourEnd, $day, $id)
    {
        $stmt = $this->con->prepare("UPDATE tbl_disciplina SET hr_inicio = ?, nome = ?, hr_fim = ?, dia = ? WHERE id_disciplina = ?");
        $stmt->bind_param("ssssi", $hourBegin, $name, $hourEnd, $day, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $this->deleteDisciplineHours($id);
            $this->migrateDisciplineById($id, $day, $hourBegin, $hourEnd);
            return true;
        }
        return false;
    }

    public function updateFrequency($aula, $falta, $id)
    {
        $stmt = $this->con->prepare("UPDATE tbl_frequencia SET aulas = ?, faltas = ? WHERE id_freq = ?");
        $stmt->bind_param("iii", $aula, $falta, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function updateReminder($title, $text, $alarm, $id)
    {
        $stmt = $this->con->prepare("UPDATE tbl_lembrete SET texto = ?, titulo = ?, alarme = ? WHERE id_lembrete = ?");
        $stmt->bind_param("sssi", $text, $title, $alarm, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function deleteDiscipline($id)
    {
        $this->deleteFrequency($id);
        $this->deleteDisciplineHours($id);
        $stmt = $this->con->prepare("DELETE FROM tbl_disciplina WHERE id_disciplina = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function deleteFrequency($idDiscipline)
    {
        $stm = $this->con->prepare("DELETE FROM tbl_frequencia WHERE fk_disciplina = ?");
        $stm->bind_param("i", $idDiscipline);
        $stm->execute();
    }

    public function deleteReminder($id)
    {
        $stmt = $this->con->prepare("DELETE FROM tbl_lembrete WHERE id_lembrete = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function savePhoto($photo, $idUser, $iddisciplina)
    {
        //Crating an statement
        $stmt = $this->con->prepare("INSERT INTO tbl_foto(caminho, disciplina , fk_usuario) values(?, ?, ?)");

        //Binding the parameters
        $stmt->bind_param("sii", $photo, $iddisciplina, $idUser);

        //Executing the statment
        $result = $stmt->execute();

        //Closing the statment
        $stmt->close();

        //If statment executed successfully
        if ($result) {
            return PHOTO_SAVED;
        } else {
            return PHOTO_FAILURE;
        }
    }

    //Method to get all the photos of a particular user
    public function getPhotos($userid)
    {
        $stmt = $this->con->prepare("SELECT id_foto, caminho, disciplina, dataCreate FROM tbl_foto WHERE fk_usuario = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($id, $caminho, $disciplina, $data);
        $photos = array();

        while ($stmt->fetch()) {
            $photo = array();
            $photo['id'] = $id;
            $photo['caminho'] = $caminho;
            $photo['disciplina'] = $disciplina;
            $photo['userid'] = $userid;
            $photo['data'] = $data;
            array_push($photos, $photo);
        }

        $stmt->close();
        return $photos;
    }

    public function deletePhoto($id)
    {
        $stmt = $this->con->prepare("DELETE FROM tbl_foto WHERE id_foto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function deleteFilePhoto($id)
    {
        $stmt = $this->con->prepare("SELECT caminho FROM tbl_foto WHERE id_foto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($caminho);
        $stmt->fetch();
        $filename = $caminho;

        return $filename;
    }

    public function deleteFilePhotoDiscipline($id)
    {
        $stmt = $this->con->prepare("SELECT caminho FROM tbl_foto WHERE disciplina = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($caminho);
        $filenames = array();

        while ($stmt->fetch()) {
            array_push($filenames, $caminho);
        }

        return $filenames;
    }

    public function deletePhotoDisciplina($id)
    {
        $stmt = $this->con->prepare("DELETE FROM tbl_foto WHERE disciplina = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }


    public function updateHorario($params)
    {
        $stmt = $this->con->prepare("UPDATE tbl_horarios 
        SET  hora_inicio = ?, hora_fim = ?, dia_semana= ? , fk_disciplina = ? 
        where id_horario = ? ");


        $stmt->bind_param(
            'ssiii',
            $params['hora_inicio'],
            $params['hora_fim'],
            $params['dia_semana'],
            $params['id_disciplina'],
            $params['id_horario']
        );

        // $stmt = $this->con->prepare("UPDATE tbl_usuario SET nome = ?, userlogin = ?, email = ?, instituicao = ? WHERE id_usuario = ?");
        // $stmt->bind_param("ssssi", $name, $username, $email, $school, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }

    public function deleteHorario($id_horario)
    {

        $stmt = $this->con->prepare("DELETE FROM tbl_horarios where id_horario = ?");
        $stmt->bind_param('i', $id_horario);

        $stmt->execute();

        if ($stmt->affected_rows > 0)
            return true;
        return false;
    }
}
