<?php

class UserSearch{

    private $db_host;
    private $db_user;
    private $db_basename;
    private $pdo;
    private $param; //array of parameters for query
    private $pc; //pdo query parameter counter

    public $error; //error message

    //prepare "where" for sql query
    private function tosql($str){
        $vocabular = [
            'ID' => [
                'where' => 't1.id',
                'type' =>\PDO::PARAM_INT
            ],
            'E-Mail' => [
                'where' => 't1.email',
                'type' => \PDO::PARAM_STR
            ],
            'Страна' => [
                'where' =>'country',
                'type' => \PDO::PARAM_STR
            ],
            'Имя' => [
                'where' => 'firstname',
                'type' => \PDO::PARAM_STR
            ],
            'Состояние пользователя' => [
                'where' =>'state',
                'type' => \PDO::PARAM_STR
            ],
        ];
        if(!isset($vocabular[$str])){
            $this->error = 'Unknown condition '.$str;
            return false;
        }else
            return $vocabular[$str];
    }

    public function __construct($config){

        $this->db_host = $config['db_host'];
        $this->db_user = $config['db_user'];
        $this->db_basename = $config['db_basename'];

        $dsn = "mysql:host=".$config['db_host'].";dbname=".$config['db_basename'].";charset=utf8";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $opt);
   }

    //search operation
    public function search($ar){
        $this->pc=0;
        $logic = $this->logic($ar);
        If(!$logic){
            $this->error = 'Logic parsing error.';
            return false;
        }

        $sql = '
          SELECT t1.id,t1.email,t1.role,t1.reg_date
          FROM `db1`.`users` as t1
          JOIN (
            SELECT t3.`user`,
            GROUP_CONCAT(IF(t3.`item`=\'country\',t3.`value`,\'\' ) SEPARATOR \'\') as country,
            GROUP_CONCAT(IF(t3.`item`=\'firstname\',t3.`value`,\'\' ) SEPARATOR \'\') as firstname,
            GROUP_CONCAT(IF(t3.`item`=\'state\',t3.`value`,\'\' ) SEPARATOR \'\') as state
            FROM `db2`.`users_about` as t3
            GROUP BY t3.`user`
            ) as t2
          ON t1.id=t2.`user`
        WHERE '.$logic;
        $stmt = $this->pdo->prepare($sql);
        foreach($this->param as $key => $data){
            $stmt->bindParam($data['name'], $this->param[$key]['value'], $data['type']);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //transforming logic condition from math language to sql
    public function logic($ar){
        $operator = array_shift($ar);
        if(empty($operator))return false;

        if($operator=='OR' || $operator=='AND'){
            $first_operand=array_shift($ar);
            if(empty($first_operand))return false;
            $str = '('.$this->logic($first_operand);
            foreach($ar as $sub_ar){
                if(!empty($sub_ar))
                    $str .= ' '.$operator.' '.$this->logic($sub_ar);
            }
            $str .= ')';
        }elseif($operator=='=' || $operator=='!=' ){
            $pname = ':p'.$this->pc++; //parameter identifier
            $translation=$this->tosql($ar[0]);
            $str = '('.$translation['where'].' '.$operator.$pname.')';
            $this->param[]=['name'=>$pname,'value'=>$ar[1],'type'=>$translation['type']];
        }else
            return false;

        return $str;
    }
}
?>