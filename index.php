<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
/**
 * Created by PhpStorm.
 * User: felima
 * Date: 26/07/2018
 * Time: 12:08
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\StreamInterface;
use Slim\Http\UploadedFile;

//including the required files
require_once 'include/DbOperation.php';
require 'vendor/autoload.php';

//Creating a slim instance
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);


$container = $app->getContainer();
$container['upload_directory_photos'] = __DIR__ . '/uploadsPhotos';

$app->get('/migratedisciplinehours', function (Request $request, Response $response) {
    $db = new DbOperation();
    $result = $db->getAllDisciplines();

    foreach ($result as $discipline) {
        $db->migrateDiscipline($discipline);
    }

    $message['error'] = false;
    $message['message'] = 'Displines hours were migrated to the new table';
    return $response->withJson($message, 200, JSON_PRETTY_PRINT);
});

$app->post('/createuser', function (Request $request, Response $response) {

    $error_return = verifyRequiredParams(array('email', 'password', 'name', 'username', 'school'), $request, $response);

    if ($error_return == null) {

        $request_data = $request->getParsedBody();

        $db = new DbOperation;

        // $db->isUserExists($request_data['username']);


        $facebookId = null;
        if (isset($request_data['facebookId'])) {
            $facebookId = $request_data['facebookId'];
        }

        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $username = $request_data['username'];
        $school = $request_data['school'];


        list($result, $msg) = $db->createUser($name, $username, $password, $email, $school, $facebookId);


        if ($result == USER_CREATED) { //success
            $message['error'] = false;
            $message['message'] = $msg;
            return $response->withJson($message, 201, JSON_PRETTY_PRINT);
        } else if ($result == USER_FAILURE) { //error
            $message['error'] = true;
            $message['message'] = $msg;
            return $response->withJson($message, 422, JSON_PRETTY_PRINT);
        }
    }

    return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
});

$app->put('/updateuser/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $error_return = verifyRequiredParams(array('name', 'username', 'email', 'school'), $request, $response);

        if ($error_return == null) {
            $request_data = $request->getParsedBody();
            $name = $request_data['name'];
            $username = $request_data['username'];
            $email = $request_data['email'];
            $school = $request_data['school'];

            $db = new DbOperation();
            $response_data = array();
            $body = $response->getBody();

            $resultUpdate = false;

            if (isset($request_data['pass'])) {
                $pass = $request_data['pass'];
                $resultUpdate = $db->updateUserPass($name, $username, $pass, $email, $school, $id);
            } else {
                $resultUpdate = $db->updateUser($name, $username, $email, $school, $id);
            }

            if ($resultUpdate) {
                $response_data['error'] = false;
                $response_data['message'] = 'User Updated Successfully';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            } else {
                $response_data['error'] = true;
                $response_data['message'] = 'Please try again later';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->post('/forgetpassword', function (Request $request, Response $response) {

    $error_return = verifyRequiredParams(array('email'), $request, $response);

    if ($error_return == null) {

        $request_data = $request->getParsedBody();
        $email = $request_data['email'];
        $db = new DbOperation;
        $result = array();

        if ($db->isEmailExist($email)) {
            $api = $db->generateApiReset($email);
            $usuario = $db->getUserEmail($email);
            require_once("forgot-password-recovery-mail.php");

            if ($isSendEmail) {
                $result['error'] = false;
                $result['message'] = 'Verificar sua caixa de e-mail para recuperar sua senha';
            } else {
                $result['error'] = true;
                $result['message'] = 'Ocorreu um erro ao enviar o e-mail' . $mail->ErrorInfo;
            }

            return $response->withJson($result, 200, JSON_PRETTY_PRINT);
        } else {
            $result['error'] = true;
            $result['message'] = 'E-mail não cadastrado!';

            return $response->withJson($result, 404, JSON_PRETTY_PRINT);
        }
    }

    return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
});

$app->post('/userlogin', function (Request $request, Response $response) {
    //verifying required parameters
    $error_return = verifyRequiredParams(array('username', 'password'), $request, $response);

    if ($error_return == null) {
        $request_data = $request->getParsedBody();

        //getting post values
        $username = $request_data['username'];
        $password = $request_data['password'];

        //Creating DbOperation object
        $db = new DbOperation();

        //Creating a response array
        $response_data = array();

        $result = $db->userLogin($username, $password);

        //If username password is correct
        if ($result == USER_AUTHENTICATED) {

            //Getting user detail
            $user = $db->getUser($username);

            //Generating response
            $response_data['error'] = false;
            $response_data['user'] = $user;
            $response_data['message'] = 'Login efetuado com sucesso';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        } else if ($result == USER_NOT_FOUND) {
            //Generating response
            $response_data['error'] = true;
            $response_data['message'] = 'Usuário não existe';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        } else if ($result == USER_PASSWORD_DO_NOT_MATCH) {
            $response_data['error'] = true;
            $response_data['message'] = 'Usuario e/ou senha invalida';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        }
    }

    return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
});

$app->post('/fblogin', function (Request $request, Response $response) {
    //verifying required parameters
    $error_return = verifyRequiredParams(array('facebookId'), $request, $response);

    if ($error_return == null) {
        $request_data = $request->getParsedBody();

        //getting post values
        $facebookId = $request_data['facebookId'];

        //Creating DbOperation object
        $db = new DbOperation();

        //Creating a response array
        $response_data = array();

        $result = $db->facebookLogin($facebookId);

        //If username password is correct
        if ($result == USER_AUTHENTICATED) {
            //Getting user detail
            $user = $db->getUserFacebook($facebookId);

            //Generating response
            $response_data['error'] = false;
            $response_data['user'] = $user;
            $response_data['message'] = 'Login efetuado com sucesso';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        } else if ($result == USER_NOT_FOUND) {
            //Generating response
            $response_data['error'] = true;
            $response_data['message'] = 'Usuário não existe';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        } else if ($result == USER_PASSWORD_DO_NOT_MATCH) {
            $response_data['error'] = true;
            $response_data['message'] = 'ID do Facebook inválido';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        }
    }

    return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
});

$app->post('/changepassword/{api}', function (Request $request, Response $response) {
    //verifying required parameters
    $error_return = verifyRequiredParams(array('email', 'newPassword'), $request, $response);

    if ($error_return == null) {
        $request_data = $request->getParsedBody();

        //getting post values
        $email = $request_data['email'];
        $newpassword = $request_data['newPassword'];

        //Creating DbOperation object
        $db = new DbOperation();

        //Creating a response array
        $response_data = array();

        $result = $db->updatePassword($newpassword, $email);

        //If username password is correct
        if ($result == PASSWORD_CHANGED) {

            //Generating response
            $response_data['error'] = false;
            $response_data['message'] = 'Senha trocada com sucesso';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        } else if ($result == PASSWORD_NOT_CHANGED) {
            //Generating response
            $response_data['error'] = true;
            $response_data['message'] = 'Algum erro ocorreu';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        }
    }

    return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
});

$app->post('/v2/creatediscipline', function (Request $request, Response $response) {
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $error_return = verifyRequiredParams(array('name', 'days', 'iduser'), $request, $response);

        if ($error_return == null) {

            $request_data = $request->getParsedBody();

            $days = json_decode($request_data['days'], true);
            $name = $request_data['name'];
            $iduser = $request_data['iduser'];

            // $sala = $request_data['sala'];
            // $professor = $request_data['professor'];

            $db = new DbOperation;

            $result = $db->createDisciplineV2($name, $iduser, $days);

            $message = array();

            if ($result == DISCIPLINE_CREATED) { //success
                $message['error'] = false;
                $message['message'] = 'Disciplina criada com sucesso';
                $message['lastid'] = $db->getLastID('tbl_disciplina', 'id_disciplina');

                return $response->withJson($message, 201, JSON_PRETTY_PRINT);
            } else if ($result == DISCIPLINE_FAILURE) { //error
                $message['error'] = true;
                $message['message'] = 'Algum erro ocorreu';

                return $response->withJson($message, 422, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }

    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->post('/creatediscipline', function (Request $request, Response $response) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $error_return = verifyRequiredParams(array('name', 'hourBegin', 'hourEnd', 'day', 'iduser'), $request, $response);

        if ($error_return == null) {

            $request_data = $request->getParsedBody();

            $day = $request_data['day'];
            $hourBegin = $request_data['hourBegin'];
            $hourEnd = $request_data['hourEnd'];
            $name = $request_data['name'];
            $iduser = $request_data['iduser'];

            $db = new DbOperation;

            $result = $db->createDiscipline($name, $day, $iduser, $hourBegin, $hourEnd);

            $message = array();

            if ($result == DISCIPLINE_CREATED) { //success
                $message['error'] = false;
                $message['message'] = 'Disciplina criada com sucesso';
                $message['lastid'] = $db->getLastID('tbl_disciplina', 'id_disciplina');

                return $response->withJson($message, 201, JSON_PRETTY_PRINT);
            } else if ($result == DISCIPLINE_FAILURE) { //error
                $message['error'] = true;
                $message['message'] = 'Algum erro ocorreu';

                return $response->withJson($message, 422, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }

    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->get('/v2/getdisciplines/{id}', function (Request $request, Response $response, array $args) {
    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {
        $id = $args['id'];
        $db = new DbOperation();
        $result = $db->getDisciplinesV2($id);
        $response_data = array();
        $response_data['error'] = false;
        $response_data['disciplines'] = $result;

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->get('/getdisciplines/{id}', function (Request $request, Response $response, array $args) {
    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {
        $id = $args['id'];
        $db = new DbOperation();
        $result = $db->getDisciplines($id);
        $response_data = array();
        $response_data['error'] = false;
        $response_data['disciplines'] = $result;

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->put('/v2/updatediscipline/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $error_return = verifyRequiredParams(array('name','days'), $request, $response);

        if ($error_return == null) {
            $request_data = $request->getParsedBody();
            $name = $request_data['name'];
            $days =  json_decode($request_data['days']);

            // // return $response->withJson($days, 200, JSON_PRETTY_PRINT);

            $db = new DbOperation();
            $response_data = array();
            $body = $response->getBody();

            if ($db->updateDisciplineV2($name, $days, $id)) {
                $response_data['error'] = false;
                $response_data['message'] = 'Discipline Updated Successfully';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            } else {
                $response_data['error'] = true;
                $response_data['message'] = 'Please try again later';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->put('/updatediscipline/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $error_return = verifyRequiredParams(array('name', 'day', 'hourBegin', 'hourEnd'), $request, $response);

        if ($error_return == null) {
            $request_data = $request->getParsedBody();
            $name = $request_data['name'];
            $day = $request_data['day'];
            $hourBegin = $request_data['hourBegin'];
            $hourEnd = $request_data['hourEnd'];

            $db = new DbOperation();
            $response_data = array();
            $body = $response->getBody();

            if ($db->updateDiscipline($name, $hourBegin, $hourEnd, $day, $id)) {
                $response_data['error'] = false;
                $response_data['message'] = 'Discipline Updated Successfully';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            } else {
                $response_data['error'] = true;
                $response_data['message'] = 'Please try again later';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->delete('/deletediscipline/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $db = new DbOperation();
        $response_data = array();

        if ($db->deleteDiscipline($id)) {
            $response_data['error'] = false;
            $response_data['message'] = 'Discipline has been deleted';
        } else {
            $response_data['error'] = true;
            $response_data['message'] = 'Plase try again later';
        }

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->get('/getfrequency/{id}', function (Request $request, Response $response, array $args) {
    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {
        $id = $args['id'];
        $db = new DbOperation();
        $result = $db->getFrequency($id);
        $response_data = array();
        $response_data['error'] = false;
        $response_data['frequency'] = $result;

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->put('/updatefrequency/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $error_return = verifyRequiredParams(array('faltas', 'aulas'), $request, $response);

        if ($error_return == null) {
            $request_data = $request->getParsedBody();
            $faltas = $request_data['faltas'];
            $aulas = $request_data['aulas'];

            $db = new DbOperation();
            $response_data = array();
            $body = $response->getBody();

            if ($db->updateFrequency($aulas, $faltas, $id)) {
                $response_data['error'] = false;
                $response_data['message'] = 'Frequency Updated Successfully';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            } else {
                $response_data['error'] = true;
                $response_data['message'] = 'Please try again later';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->post('/createreminder', function (Request $request, Response $response) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $error_return = verifyRequiredParams(array('title', 'text', 'alarm', 'iduser'), $request, $response);

        if ($error_return == null) {

            $request_data = $request->getParsedBody();

            $title = $request_data['title'];
            $text = $request_data['text'];
            $alarm = $request_data['alarm'];
            $iduser = $request_data['iduser'];

            $db = new DbOperation;

            $result = $db->createReminder($title, $text, $iduser, $alarm);

            $message = array();

            if ($result == REMINDER_CREATED) { //success
                $message['error'] = false;
                $message['message'] = 'Lembrete criado com sucesso';
                $message['lastid'] = $db->getLastID('tbl_lembrete', 'id_lembrete');

                return $response->withJson($message, 201, JSON_PRETTY_PRINT);
            } else if ($result == REMINDER_FAILURE) { //error
                $message['error'] = true;
                $message['message'] = 'Algum erro ocorreu';

                return $response->withJson($message, 422, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }

    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->get('/getreminders/{id}', function (Request $request, Response $response, array $args) {
    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {
        $id = $args['id'];
        $db = new DbOperation();
        $result = $db->getReminder($id);
        $response_data = array();
        $response_data['error'] = false;
        $response_data['reminders'] = $result;

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->put('/updatereminder/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $error_return = verifyRequiredParams(array('title', 'text', 'alarm'), $request, $response);

        if ($error_return == null) {
            $request_data = $request->getParsedBody();
            $title = $request_data['title'];
            $text = $request_data['text'];
            $alarm = $request_data['alarm'];

            $db = new DbOperation();
            $response_data = array();
            $body = $response->getBody();

            if ($db->updateReminder($title, $text, $alarm, $id)) {
                $response_data['error'] = false;
                $response_data['message'] = 'Reminder Updated Successfully';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            } else {
                $response_data['error'] = true;
                $response_data['message'] = 'Please try again later';

                return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->delete('/deletereminder/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $db = new DbOperation();
        $response_data = array();

        if ($db->deleteReminder($id)) {
            $response_data['error'] = false;
            $response_data['message'] = 'Reminder has been deleted';
        } else {
            $response_data['error'] = true;
            $response_data['message'] = 'Plase try again later';
        }

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->post('/savephoto', function (Request $request, Response $response) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $error_return = verifyRequiredParams(array('iddiscipline', 'iduser'), $request, $response);

        if ($error_return == null) {
            $request_data = $request->getParsedBody();

            $iddiscipline = $request_data['iddiscipline'];
            $iduser = $request_data['iduser'];
            $db = new DbOperation;

            $error_file = verifyRequiredFile('photo', $request, $response);

            if ($error_file == null) {
                $directory = $this->get('upload_directory_photos');

                $uploadedFiles = $request->getUploadedFiles();
                $uploadedFile = $uploadedFiles['photo'];

                $filename = moveUploadedFile($directory, $uploadedFile);

                $result = $db->savePhoto($filename, $iduser, $iddiscipline);
                $message = array();

                if ($result == PHOTO_SAVED) { //success
                    $message['error'] = false;
                    $message['message'] = 'Photo salva com sucesso';
                    $message['lastid'] = $db->getLastID('tbl_foto', 'id_foto');
                    $message['name'] = $db->getPhotoName($message['lastid']);

                    return $response->withJson($message, 201, JSON_PRETTY_PRINT);
                } else if ($result == PHOTO_FAILURE) { //error
                    $message['error'] = true;
                    $message['message'] = 'Algum erro ocorreu';

                    return $response->withJson($message, 422, JSON_PRETTY_PRINT);
                }
            }

            if ($error_file != null) {
                return $response->withJson($error_file, 400, JSON_PRETTY_PRINT);
            } else {
                $message = array();
                $message['error'] = true;
                $message['message'] = 'Foto ja existe';

                return $response->withJson($message, 409, JSON_PRETTY_PRINT);
            }
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->get('/downloadphoto/{caminho}', function (Request $request, Response $response, array $args) {
    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {
        $caminho = $args['caminho'];
        $directory = $this->get('upload_directory_photos');

        $file = $directory . '/' . $caminho;
        $fh = fopen($file, 'rb');

        $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

        return $response->withHeader('Content-Type', 'application/force-download')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Type', 'application/download')
            ->withHeader("Content-Type", "image/jpeg")
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . basename($file) . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public')
            ->withBody($stream); // all stream contents will be sent to the response

    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->get('/getphotos/{id}', function (Request $request, Response $response, array $args) {
    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {
        $id = $args['id'];
        $db = new DbOperation();
        $result = $db->getPhotos($id);
        $response_data = array();
        $response_data['error'] = false;
        $response_data['photos'] = $result;

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->delete('/deletephoto/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $db = new DbOperation();
        $filename = $db->deleteFilePhoto($id);
        $response_data = array();
        $directory = $this->get('upload_directory_photos');
        $caminho = $directory . DIRECTORY_SEPARATOR . $filename;

        if (@unlink($caminho)) {
            if ($db->deletePhoto($id)) {
                $response_data['error'] = false;
                $response_data['message'] = 'Photo has been deleted';
            } else {
                $response_data['error'] = true;
                $response_data['message'] = 'Please try again later';
            }
        } else {
            $response_data['error'] = true;
            $response_data['message'] = 'Error deleting photo';
        }

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->delete('/deletephotodisciplina/{id}', function (Request $request, Response $response, array $args) {

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        $id = $args['id'];
        $db = new DbOperation();
        $filenames = $db->deleteFilePhotoDiscipline($id);
        $response_data = array();
        $directory = $this->get('upload_directory_photos');

        foreach ($filenames as $file) {
            $caminho = $directory . DIRECTORY_SEPARATOR . $file;
            if (!@unlink($caminho)) {
                $response_data['error'] = true;
                $response_data['message'] = 'Error deleting photo';
                return $response->withJson($response_data, 400, JSON_PRETTY_PRINT);
            }
        }

        if ($db->deletePhotoDisciplina($id)) {
            $response_data['error'] = false;
            $response_data['message'] = 'Disciplines Photos has been deleted';
        } else {
            $response_data['error'] = true;
            $response_data['message'] = 'Please try again later';
        }

        return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
    }
    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});

$app->post('/addanalyticslog', function (Request $request, Response $response) {

    $error_return = verifyRequiredParams(array('system', 'versionSystem', 'modelDevice', 'brandDevice', 'iduser'), $request, $response);

    if ($error_return == null) {

        $request_data = $request->getParsedBody();

        $system = $request_data['system'];
        $versionSystem = $request_data['versionSystem'];
        $modelDevice = $request_data['modelDevice'];
        $brandDevice = $request_data['brandDevice'];
        $iduser = $request_data['iduser'];

        $db = new DbOperation;

        $result = $db->addAnalytics($system, $versionSystem, $modelDevice, $brandDevice, $iduser);

        $message = array();

        if ($result == LOG_ADD) { //success
            $message['error'] = false;
            $message['message'] = 'Log adicionado com sucesso';

            return $response->withJson($message, 201, JSON_PRETTY_PRINT);
        } else if ($result == LOG_FAILURE) { //error
            $message['error'] = true;
            $message['message'] = 'Algum erro ocorreu';

            return $response->withJson($message, 422, JSON_PRETTY_PRINT);
        }
    }

    return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
});

//Method to verify the params is it ok
function verifyRequiredParams($required_fields, $request, $response)
{

    //Assuming there is no error
    $error = false;

    //Error fields are blank
    $error_fields = "";

    //Getting the request parameters
    $request_params = $request->getParsedBody();

    $body = $response->getBody();

    //Looping through all the parameters
    foreach ($required_fields as $field) {

        //if any requred parameter is missing
        if (!isset($request_params[$field])) {
            //error is true
            $error = true;

            //Concatnating the missing parameters in error fields
            $error_fields .= $field . ', ';
        }
    }

    //if there is a parameter missing then error is true
    if ($error) {
        //Creating response array
        $error_detail = array();

        //Adding values to response array
        $error_detail["error"] = true;
        $error_detail["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        // $body->write($response->withJSON($error_detail,400));
        return $error_detail;
    }
    return null;
}

//Method to verify the file is it ok
function verifyRequiredFile($required_file, $request, $response)
{

    //Getting the request file
    $uploadedFiles = $request->getUploadedFiles();

    // handle single input with single file upload
    if (isset($uploadedFiles[$required_file])) {
        $uploadedFile = $uploadedFiles[$required_file];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            return null;
        }
    }

    //Creating response array
    $error_detail = array();

    //Adding values to response array
    $error_detail["error"] = true;
    $error_detail["message"] = 'Required file ' . $required_file . ' is missing or empty';
    return $error_detail;
}

//Method to authenticate a user
function authenticateUser($request, $response)
{

    //Getting request headers
    $headers = $request->getHeaders();
    $message = array();

    $body = $response->getBody();

    //Verifying the headers
    if (isset($headers['HTTP_AUTHORIZATION'])) {

        //Creating a DatabaseOperation boject
        $db = new DbOperation();

        //Getting api key from header
        $api_key = $headers['HTTP_AUTHORIZATION'][0];

        //Validating apikey from database
        if (!$db->isValidUser($api_key)) {
            $message["error"] = true;
            $message["message"] = "Acesso negado. Api key inválido";
            $message["api"] = $api_key;
            $body->write($response->withJSON($message, 401));

            return $message;
        }
    } else {
        // api key is missing in header
        $message["error"] = true;
        $message["message"] = "Esta faltando Api key";
        $body->write($response->withJSON($message, 400));

        return $message;
    }

    return null;
}

//Method to moves the uploaded file to the upload directory and assigns it a unique name 
//to avoid overwritting an existing uploaded file

function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}


$app->post('/updatehour', function (Request $request, Response $response) {

    $error_return = verifyRequiredParams(array('id_horario', 'dia_semana', 'hora_inicio', 'hora_fim', 'id_disciplina'), $request, $response);

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        if ($error_return === null) {
            $db = new DbOperation();
            $result = $db->updateHorario($request->getParsedBody());
            $response_data['error'] = !$result;
            $response_data['message'] =  $result ? 'Horario Updated Successfully' : 'Please try again later';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }

    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});



$app->post('/deletehour', function (Request $request, Response $response) {

    $error_return = verifyRequiredParams(array('id_horario'), $request, $response);

    $error_auth = authenticateUser($request, $response);

    if ($error_auth == null) {

        if ($error_return === null) {
            $db = new DbOperation();
            $result = $db->deleteHorario($request->getParsedBody()['id_horario']);
            $response_data['error'] = !$result;
            $response_data['message'] =  $result ? 'Horario delete Successfully' : 'Please try again later';

            return $response->withJson($response_data, 200, JSON_PRETTY_PRINT);
        }

        return $response->withJson($error_return, 400, JSON_PRETTY_PRINT);
    }

    return $response->withJson($error_auth, 401, JSON_PRETTY_PRINT);
});




$app->run();
