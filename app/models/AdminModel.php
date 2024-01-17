<?php

namespace bng\Models;

class AdminModel extends BaseModel {

    public function getAllClients()
    {

        $this->db_connect();
        $results = $this->query(

            "SELECT p.id, AES_DECRYPT(p.name, '" . MYSQL_AES_KEY . "') name, " .
            "p.gender, " .
            "p.birthdate, " .
            "AES_DECRYPT(p.email, '" . MYSQL_AES_KEY . "') email, " .
            "AES_DECRYPT(p.phone, '" . MYSQL_AES_KEY . "') phone, p.interests, " .
            "p.created_at, AES_DECRYPT(a.name, '" . MYSQL_AES_KEY . "') agent ".
            "FROM persons p LEFT JOIN agents a ON p.id_agent = a.id " .
            "WHERE p.deleted_at IS NULL ORDER BY created_at DESC",
        );

        return $results;

    }

    public function getAgentClientsStats()
    {
        $sql = "SELECT * FROM (" .
            "SELECT " .
            "p.id_agent, AES_DECRYPT(a.name,'".MYSQL_AES_KEY . "') agente, ".
            "COUNT(*) total_clientes FROM persons p LEFT JOIN agents a ".
            "ON a.id = p.id_agent WHERE p.deleted_at IS NULL ".
            "GROUP BY id_agent ) a ORDER BY total_clientes DESC";

        $this->db_connect();
        $results = $this->query($sql);
        return $results->results;

    }

    public function getGlobalStats()
    {
        $this->db_connect();

        //total agents
        $results['total_agents'] = $this->query("SELECT count(*) value FROM agents")->results[0];

        //total clients
        $results['total_clients'] = $this->query("SELECT count(*) value FROM persons WHERE deleted_at IS NULL")->results[0];

        //total inative clients
        $results['total_deleted_clients'] = $this->query("SELECT count(*) value FROM persons WHERE deleted_at IS NOT NULL")->results[0];

        //avg number of clients per agent
        $results['average_clients_per_agent'] = $this->query("SELECT (total_persons / total_agents) value FROM (
        SELECT (SELECT COUNT(*) FROM persons) total_persons, (SELECT COUNT(*) FROM agents) total_agents ) a")->results[0];

        //yonguer client
        $results['younger_client'] = $this->query("SELECT TIMESTAMPDIFF(YEAR, birthdate, CURRENT_DATE) value FROM persons ORDER BY birthdate DESC LIMIT 1")->results[0];

        //older client
        $results['oldest_client'] = $this->query("SELECT TIMESTAMPDIFF(YEAR, birthdate, CURRENT_DATE) value FROM persons ORDER BY birthdate ASC LIMIT 1")->results[0];

        //avg age
        $results['age_average'] = $this->query("SELECT AVG(TIMESTAMPDIFF(YEAR, birthdate, CURRENT_DATE)) value FROM persons")->results[0];

        //% by gender - males
        $results['percentage_males'] = $this->query("SELECT CAST((total_males/total_clients) * 100 AS DECIMAL(5,2)) value FROM
                                        (SELECT (SELECT COUNT(*) FROM persons) total_clients,
                                        (SELECT COUNT(*) FROM persons WHERE gender = 'm') total_males ) a")->results[0];

        //% by gender - females
        $results['percentage_females'] = $this->query("SELECT CAST((total_femeales/total_clients) * 100 AS DECIMAL(5,2)) value FROM
                                        (SELECT (SELECT COUNT(*) FROM persons) total_clients,
                                        (SELECT COUNT(*) FROM persons WHERE gender = 'f') total_femeales ) a")->results[0];

        return $results;
    }

    public function getAgentsForManagement()
    {
        $this->db_connect();
        $results = $this->query("
            SELECT
            id,
            AES_DECRYPT(name, '".MYSQL_AES_KEY."') `name`,
            passwrd,
            profile,
            last_login,
            created_at,
            updated_at,
            deleted_at
            FROM agents
        ");

        return $results;
    }

    public function checkIfAgentExists($agent_name)
    {
        $param = [':name' => $agent_name];

        $this->db_connect();
        $sql = "SELECT id FROM agents 
                WHERE name = AES_ENCRYPT(:name, '". MYSQL_AES_KEY . "')";
        $results = $this->query($sql, $param);

        if ($results->affected_rows == 0){
            return false;
        } else {
            return true;
        }

    }

    public function addNewAgent($data)
    {
        //generate purl
        $chars = 'abcdefghijkabcdefghijkabcdefghijkABCDEFGHIJKABCDEFGHIJKABCDEFGHIJK';
        $purl = substr(str_shuffle($chars), 0, 20);

        //add to the db
        $params = [
            ':name' => $data['text_name'],
            ':profile' => $data['select_profile'],
            ':purl' => $purl
        ];

        $this->db_connect();
        $sql = "INSERT INTO agents VALUES (
                0,
               AES_ENCRYPT(:name, '" . MYSQL_AES_KEY . "'),
               NULL,
               :profile,
               :purl,
               NULL,
               NULL,
               NOW(),
               NULL,
               NULL)";
        $results = $this->non_query($sql, $params);

        if ($results->affected_rows == 0) {
            return [
                'status' => 'error'
            ];
        } else {
            return [
                'status' => 'success',
                'email' => $data['text_name'],
                'purl' => $purl
            ];
        }
    }
    public function getAgentdata($id)
    {
        $params = [
            ':id' => $id
        ];

        $this->db_connect();
        $sql = "SELECT id, AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, 
            profile, 
            created_at, 
            updated_at,
            deleted_at
            FROM agents WHERE id = :id";
        $results = $this->query($sql, $params);

        return $results->results[0];

    }

    public function checkIfUserExists($id, $text_name)
    {
        // check if there is another agent with the same name (email)
        $params = [
            ':id' => $id,
            ':name' => $text_name
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM agents " .
            "WHERE AES_ENCRYPT(:name, '" . MYSQL_AES_KEY . "') = name " .
            "AND id <> :id"
            , $params);

        return $results->affected_rows != 0 ? true : false;
    }

    public function editAgent($id, $data)
    {
        $params = [
            ':id' => $id,
            ':name' => $data['text_name'],
            ':profile' => $data['select_profile']
        ];
        $this->db_connect();
        $results = $this->non_query(
            "UPDATE agents SET " .
            "name = AES_ENCRYPT(:name, '" . MYSQL_AES_KEY . "'), " .
            "profile = :profile, " .
            "updated_at = NOW() " .
            "WHERE id = :id"
            , $params);
        return $results;
    }

    public function getAgentdataAndTotalClients($id)
    {
        $params = [
            ':id' => $id
        ];

        $this->db_connect();
        $sql = "SELECT 
            id, 
            AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, 
            profile, 
            created_at, 
            updated_at,
            deleted_at,
            (SELECT COUNT(*) FROM persons WHERE id_agent = :id) total_clients
            FROM agents WHERE id = :id";
        $results = $this->query($sql, $params);

        return $results->results[0];
    }

    public function deleteAgent($id)
    {
        $params = [
            ':id' => $id
        ];

        $this->db_connect();
        $sql = "UPDATE agents SET
                deleted_at = NOW()   
                WHERE id = :id";

        $results = $this->non_query($sql, $params);

        return $results;
    }

    public function recoverAgent($id)
    {
        // recover the agent
        $params = [
            ':id' => $id
        ];
        $this->db_connect();
        $results = $this->non_query(
            "UPDATE agents SET " .
            "deleted_at = NULL " .
            "WHERE id = :id"
            , $params);
        return $results;
    }


}