<?php
include '../MagicRepository.class.php';

if(isset($argv[1]) == "mysql" ) {
    $con = Connection::MySql("localhost","test","test","test");
    $con->query("drop table noticias");
    $con->query("drop table destaques");
    $con->query("drop table autores");
    $con->query("drop table comm");
    $con->exec(file_get_contents("noticias.mysql"));
} else {
    $con = Connection::Sqlite("noticias.db3");
    $con->query("drop table noticias");
    $con->query("drop table autores");
    $con->query("drop table destaques");
    $con->query("drop table comm");
    $con->exec(file_get_contents("noticias.sql"));
}

if($con->errorcode() != "00000") {
    echo "Deu erro criando o Banco de Dados.\n";
    var_dump($con->errorinfo());
    return ;
} else {
    echo "Sucesso criando BD\n";
}
/* */
$con->query("insert into noticias (id , titulo, texto) values ( 1 , 'test', 'testando');");
$con->query("insert into noticias (id , titulo, texto) values ( 2 , 'aff', 'ninguem merece');");
$con->query("insert into noticias (id , titulo, texto) values ( 3 , 'diogo', 'esta entediado');");
$con->query("insert into noticias (id , titulo, texto) values ( 4 , 'debora', 'esta dormindo');");
/* */
if($con->errorcode() != "00000") {
    echo "Deu erro populando o Banco de Dados.\n";
    var_dump($con->errorinfo());
    return ;
} else {
    echo "Sucesso populando DB\n";
}
$db = new MagicSql($con,"noticias");

$noticia = $db->getNew();
if($noticia->titulo !== null or $noticia->id !== null) {
    echo "Falha getNew.\n";
} else {
    echo "Sucesso getNew.\n";
}
$noticia->titulo = "Inserindo";
$noticia->texto = "Texto inserido";

$db->insert($noticia);
$query = $con->query("select * from noticias where id = 5");
$obj = $query->fetchObject();
if($obj->titulo != $noticia->titulo) {
    echo "Falha inserindo noticia.\n";
} else {
    echo "Sucesso inserindo noticia.\n";
}

if($noticia->id != 5) {
    echo "Falha atualizando Index.\n";
} else {
    echo "Sucesso atualizando Index.\n";
}


$noticia->texto = "Reinserindo";
$db->update($noticia);

$query = $con->query("select * from noticias where id = 5");
$obj = $query->fetchObject();
if($obj->texto != $noticia->texto) {
    echo "Falha updating noticia.\n";
} else {
    echo "Sucesso updating noticia.\n";
}

$db->delete($noticia);
$query = $con->query("select * from noticias where id = 5");
$obj = $query->fetchObject();
if($obj != false) {
    echo "Falha deleting noticia.\n";
} else {
    echo "Sucesso deleting noticia.\n";
}
if($noticia != null) {
    echo "Falha dereferenciando noticia\n";
} else {
    echo "Sucesso dereferenciando noticia\n"   ;
}


$not = $db->getById(4);

if($not->titulo != "debora") {
    echo "Falha getById\n";
} else {
    echo "Sucesso getById\n";
}

$nots = $db->get("titulo","deb%");
if($nots->get(0)->texto != "esta dormindo") {
    echo "Falha no get \n";
} else {
    echo "Sucesso no get \n";
}

$nots = $db->select("titulo like 'deb%'");
if($nots[0]->texto != "esta dormindo") {
    echo "Falha no select where \n";
} else {
    echo "Sucesso no select where \n";
}

$nots = $db->select(null,"titulo");
if($nots[0]->titulo != "aff") {
    echo "Falha no Select Order\n";
} else {
    echo "Sucesso no select order\n";
}

$nots = $db->select(null,null,"1");
if(count($nots) > 1) {
    echo "Falha no select limit\n";
} else {
    echo "Sucesso no select limit\n";
}

$num = $db->count("texto like '%e%'");
if($num != 4) {
    echo "Falha no Count\n";
} else {
    echo "Sucesso no Count\n";
}

$objs = $db->get("titulo","debora","titulo","1");
if($objs[0]->texto != "esta dormindo") {
    echo "Falha no Search simples.\n";
} else {
    echo "Sucesso no Search simples.\n";
}

$objs = $db->get("titulo,id","debora,4","titulo","1");
if($objs[0]->texto != "esta dormindo") {
    echo "Falha no Search com virgula.\n";
} else {
    echo "Sucesso no Search com virgula.\n";
}

$objs = $db->get(array("titulo","id"),array("debora","4"),"titulo","1");
if($objs[0]->texto != "esta dormindo") {
    echo "Falha no Search com array.\n";
} else {
    echo "Sucesso no Search com array.\n";
}

/* /
$objs = $db->get(array("titulo","id"),array( array("aff","debora"),array(4,2) ),"titulo DESC");
if($objs[0]->texto != "esta dormindo" and $objs[1]->texto = "ninguem merece") {
    echo "Falha no complex Search com array.\n";
} else {
    echo "Sucesso no complex Search com array.\n";
}
/* */

$nots = $db->get(null,null,"titulo ASC");
if($nots[0]->titulo != "aff") {
    echo "Falha no get Order\n";
} else {
    echo "Sucesso no get order\n";
}

$nots = $db->get(null,null,null,"1");
if(count($nots) > 1) {
    echo "Falha no get limit\n";
} else {
    echo "Sucesso no get limit\n";
}

echo "\n";

$rep  = new MagicRepository($con);

$con->query("delete noticias;");
$con->query("delete autores;");
$con->query("delete destaques;");

$con->query("insert into autores (id , nome) values ( 1, 'diogo');");
$con->query("insert into autores (id , nome) values ( 2, 'debora');");

$con->query("insert into noticias (id , titulo, texto,id_autores) values ( 6 , 'test', 'testando',1) ;");
$con->query("insert into noticias (id , titulo, texto,id_autores) values ( 7 , 'aff', 'ninguem merece',2);");
$con->query("insert into noticias (id , titulo, texto,id_autores) values ( 8 , 'diogo', 'esta entediado',1);");
$con->query("insert into noticias (id , titulo, texto,id_autores) values ( 9 , 'debora', 'esta dormindo',2);");

$con->query("insert into destaques (id , titulo, nome_autores) values ( 1 , 'Rock', 'diogo') ;");
$con->query("insert into destaques (id , titulo, nome_autores) values ( 2 , 'Roll', 'diogo');");

$con->query("insert into comm (id , com, id_autores) values ( 1 , 'Foobar', 2);");

$diogo = $rep->table("autores")->get("nome","diogo")->get(0);

if($diogo->nome !== "diogo") {
    echo "Falha no get.\n";
} else {
    echo "Sucesso no get.\n";
}

if($diogo->noticias[0]->titulo !== "test" or $diogo->noticias[1]->titulo !== "diogo") {
    echo "Falha no Join.\n";
} else {
    echo "Sucesso no Join.\n";
}

if(!isset($diogo->noticias) and $diogo->noticias[0]->autores[0] !== $diogo) {
    echo "Falha no Join Recursivo.\n";
} else {
    echo "Sucesso no Join Recursivo.\n";
}

if($diogo->destaques[0]->titulo !== "Rock") {
    echo "Falha no Multi Join .\n";
} else {
    echo "Sucesso no Multi Join.\n";
}

$noticia = $rep->table("noticias")->get(9);
if(!isset($noticia->autores) and count($noticia->autores[0]) < 1 and $noticia->autores[0]->nome != "debora"){
    echo "Falha no join reverso\n";
    var_dump($noticia);
} else {
    echo "Sucesso no join reverso\n";
    $di = $noticia->autores[0];
    if(!isset($di->comm) and count($di->comm) < 1 and $di->comm->com != "foobar") {
        echo "Falha no deep join reverso\n";
        var_dump($noticia->autores[0]);
    } else {
        echo "Sucesso no deep join reverso\n";
    }
}


if(!function_exists("xdebug_time_index")) return ;

echo "\n\n";
echo "It took ".round(xdebug_time_index(),5)." seconds \n";
echo "Used ".round(xdebug_memory_usage()/1024,5)."Kb of Memory\n";
echo "Used at peak ".round(xdebug_peak_memory_usage()/1024,5)."Kb of Memory\n";

?>
