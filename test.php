<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});
$config = [
    'db_host'=>'localhost',
    'db_basename'=>'db1',
    'db_pass'=>'xanatar',
    'db_user'=>'root'
];
$us = new UserSearch($config);
//$result = $us->search(['AND',['=','Страна','Russia'],['!=','Состояние пользователя','passive']]);
$result = $us->search([
    'AND',
    [
        'OR',
        ['=','Состояние пользователя','active'],
        ['!=','Страна','Russia']
    ],
    ['!=','E-Mail','a@a.a']
]);
if(Empty($result))
    echo $us->error;
else
    print_r($result);
?>