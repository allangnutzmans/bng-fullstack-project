<?php

namespace bng\Models;

class Agents extends BaseModel
{
    public function checkLogin($username, $password)
    {
        //check if login is valid
        $params = [
            ":username" => $username
        ];

        //check if user exists on db
        $this->db_connect();
        $query = "SELECT id, passwrd FROM agents WHERE AES_ENCRYPT(:username, '".MYSQL_AES_KEY."') = name";
        $results = $this->query($query, $params);

        //user dont exists -> retunr false
        if  ($results->affected_rows == 0){
            return [
                'status' => false
            ];
        }

        if(!password_verify($password,  $results->results[0]->passwrd)){
            return [
                'status' => false
            ];
        }

        //login is ok
        return [
            'status' => true
        ];
    }

    public function getUserData($username)
    {
        //get usr data to insert on the session
        $params = [
            ':username' => $username
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT id, AES_DECRYPT(name, '".MYSQL_AES_KEY."') name, profile FROM agents
                   WHERE AES_ENCRYPT(:username, '".MYSQL_AES_KEY."') = name"
        , $params);


        return [
            'status' => 'success',
            'data' => $results->results[0]
        ];



    }

    public function setUserLastLogin($id)
    {
        $params = [
            ':id' => $id
        ];

        $results = $this->non_query(
            "UPDATE agents SET ".
                "last_login = NOW() ".
                "WHERE id = :id",
            $params
        );
        return $results;

    }

    public function getAgentClients($id_agent)
    {
        $param= [
            ':id_agent' => $id_agent
        ];

        $this->db_connect();
        $results = $this->query(
            "SELECT id, AES_DECRYPT(name, '".MYSQL_AES_KEY."') name, " .
            "gender, birthdate, AES_DECRYPT(email, '".MYSQL_AES_KEY."') email, " .
            "AES_DECRYPT(phone, '".MYSQL_AES_KEY."') phone, " .
            "interests, created_at, updated_at " .
            "FROM persons WHERE id_agent = :id_agent " .
            "AND deleted_at IS NULL",
            $param);

        return [
            'status' => 'success',
            'data' => $results->results
        ];
    }

    public function checkIfClientExists($post_data)
    {
        $params = [
            ':id_agent' => $_SESSION['user']->id,
            ':client_name' => $post_data['text_name']
        ];


        $this->db_connect();
        $results = $this->query("SELECT id FROM persons WHERE AES_ENCRYPT(:client_name,'" .MYSQL_AES_KEY."') = name " .
        "AND id_agent = :id_agent", $params);


        if($results->affected_rows == 0){
            return [
                'status' => false
            ];
        } else {
            return [
                'status' => true
            ];
        }

    }

    public function addNewClientToDB($post_data)
    {
        $birthdate = new \DateTime($post_data['text_birthdate']);

        $params = [
            ':name' => $post_data['text_name'],
            ':gender' => $post_data['radio_gender'],
            ':birthdate' => $birthdate->format('Y-m-d H:i:s'),
            ':email' => $post_data['text_email'],
            ':phone' => $post_data['text_phone'],
            ':interests' => $post_data['text_interests'],
            ':id_agent' => $_SESSION['user']->id,
        ];

        //0 permite o auto-incremento
        $this->db_connect();
        $this->non_query("INSERT INTO persons VALUES(".
            "0, " .
            "AES_ENCRYPT(:name, '". MYSQL_AES_KEY . "'), " .
            ":gender, ".
            ":birthdate, ".
            "AES_ENCRYPT(:email, '". MYSQL_AES_KEY . "'), " .
            "AES_ENCRYPT(:phone, '". MYSQL_AES_KEY . "'), " .
            ":interests, :id_agent, ".
            "NOW(), ".
            "NOW(), ".
            "NULL)"
            ,$params);

    }

    public function getClientData($client_id)
    {
        $params = [
            ':client_id' => $client_id
        ];

        $this->db_connect();
        $results = $this->query(

            "SELECT id, AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, " .
            "gender, " .
            "birthdate, " .
            "AES_DECRYPT(email, '" . MYSQL_AES_KEY . "') email, " .
            "AES_DECRYPT(phone, '" . MYSQL_AES_KEY . "') phone, interests " .
            "FROM persons WHERE id = :client_id",
            $params);

        if ($results->affected_rows == 0) {
            return [
                'status' => 'error'
            ];
        }else {
            return [
                'status' => 'success',
                'data' => $results->results[0]
            ];
        }
    }

    public function checkOtherClientWithSameName($id_client, $text_name)
    {
        $params = [
            ':id' => $id_client,
            ':name' => $text_name,
            'id_agent' => $_SESSION['user']->id
        ];

        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM persons WHERE ".
            "id <> :id AND " .
            "id_agent = :id_agent AND ".
            "AES_ENCRYPT(:name, '". MYSQL_AES_KEY . "') = name",
            $params
        );

        if ($results->affected_rows != 0 ){
            return ['status' => true];
        } else {
            return ['status' => false];
        }
    }

    public function updateClientData($id, $post_data)
    {
        $birthdate = new \DateTime($post_data['text_birthdate']);

        $params = [
            ':id' => $id,
            ':name' => $post_data['text_name'],
            ':gender' => $post_data['radio_gender'],
            ':birthdate' => $birthdate->format('Y-m-d H:i:s'),
            ':email' => $post_data['text_email'],
            ':phone' => $post_data['text_phone'],
            ':interests' => $post_data['text_interests'],
        ];

        $this->db_connect();
        $this->non_query("UPDATE persons SET " .
            "name = AES_ENCRYPT(:name, '". MYSQL_AES_KEY . "'), " .
            "gender = :gender, " .
            "birthdate = :birthdate, " .
            "email = AES_ENCRYPT(:email, '". MYSQL_AES_KEY . "'), " .
            "phone = AES_ENCRYPT(:phone, '". MYSQL_AES_KEY . "'), " .
            "interests = :interests, " .
            "updated_at = NOW() " .
            "WHERE id = :id", $params);
    }

    public function deleteClient($id)
    {
        $param = [
                ':id' => $id
        ];

        $this->db_connect();
        $this->non_query("DELETE FROM persons WHERE id = :id", $param);

    }

    public function checkCurrentPassword($current_password)
    {
        $param = [
            ":id" => $_SESSION['user']->id,
        ];

        $this->db_connect();
        $result = $this->query("SELECT passwrd FROM agents WHERE " .
        "id = :id", $param);


        if (password_verify($current_password, $result->results[0]->passwrd)){
            return [
                "status" => true
            ];
        } else {
            return [
                "status" => false
            ];
        }

    }

    public function updateAgentPassword($new_password)
    {
        $new_password = "Aa123456";
        $params = [
            ":passwrd" => password_hash($new_password, PASSWORD_DEFAULT),
            ":id" => $_SESSION['user']->id
        ];

        $this->db_connect();
        $this->query("UPDATE agents FROM set passwrd = :passwrd, updated_at = NOW() WHERE id = :id", $params);
    }

    public function checkNewAgentPurl($purl)
    {
        $param = [
            ':purl' => $purl
        ];

        $sql = "SELECT id FROM agents WHERE purl = :purl";
        $this->db_connect();
        $results = $this->query($sql, $param);

        if ($results->affected_rows == 0){
            return ['status' => false];
        } else {
            return [
                'status' => true,
                'id' => $results->results[0]->id
                ];
        }

    }

    public function newAgentPassword($id, $password)
    {
        $params = [
            ":id" => $id,
            ":passwrd" => password_hash($password, PASSWORD_DEFAULT)
        ];

        $sql = "UPDATE agents SET 
                  passwrd = :passwrd , 
                  purl = NULL, 
                  updated_at = NOW() 
                  WHERE id = :id ";
        $this->db_connect();
        $this->non_query($sql, $params);
    }

}















